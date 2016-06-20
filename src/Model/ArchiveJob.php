<?php

namespace Gsandbox\Model;

use Aws\Glacier\TreeHash;

class ArchiveJob extends Job
{
    public function getArchive()
    {
        return $this->vault->getArchive($this->getParam('ArchiveId'));
    }

    public function serializeArray()
    {
        $ret = parent::serializeArray();

        if (($archive = $this->getArchive()) === false) {
            $ret['Status'] = 'Failed';

            return $ret;
        }

        $ret['ArchiveId'] = $archive->id;
        $ret['ArchiveSizeInBytes'] = $this->getArchiveSizeInBytes();
        $ret['ArchiveSHA256TreeHash'] = $archive->getParam('SHA256TreeHash');
        $ret['RetrievalByteRange'] = $this->getRetrievalByteRange();

        if ($this->getCompleted()) {
            $ret['SHA256TreeHash'] = $this->getSHA256TreeHash();
        }

        return $ret;
    }

    public function getArchiveSizeInBytes() {
        if (($archive = $this->getArchive()) === false) {
            return 0;
        }

        return (int) filesize($archive->getFile('data'));
    }

    public function getRetrievalByteRange() {
        $range = $this->getParam('RetrievalByteRange');

        if (empty($range)) {
            $range = "0-" . $this->getArchiveSizeInBytes();
        }

        return $range;
    }

    public function getSHA256TreeHash() {
        if ($hash = $this->getParam('SHA256TreeHash')) {
            return $hash;
        }

        if (($archive = $this->getArchive()) === false) {
            throw new \Exception('Cannot get archive');
        }

        if (($f = fopen($file = $archive->getFile('data'), 'r')) === false) {
            throw new \Exception('fopen failed');
        }

        $range = explode('-', $this->getRetrievalByteRange(), 2);
        list($from, $to) = $range;

        if (fseek($f, $from) === -1) {
            throw new \Exception('fseek failed');
        }

        $bufSize = 1024 * 1024 * 1;
        $readBytes = $to - $from + 1;
        $contentLength = $readBytes;
        $bytesWritten = 0;
        $data = '';

        while ($readBytes > 0 && !feof($f)) {
            $buf = fread($f, max($bufSize, $readBytes));
            if ($buf === false) {
                throw new \Exception('fread failed');
            }
            $readBytes -= strlen($buf);
            $data .= $buf;
        }

        if ($range && $range[0] == 0 && $range[1] == filesize($file) - 1) {
            $computeTreeHash = true;
        } else if ($range) {
            $computeTreeHash = \Gsandbox\TreeHashCheck::isTreeHashAligned($to + 1, $from, $to);
        } else {
            $computeTreeHash = true;
        }

        $treeHash = null;

        if ($computeTreeHash) {
            $hash = new TreeHash();
            $hash->update($data);
            $treeHash = bin2hex($hash->complete());
        }

        $this->setParam('SHA256TreeHash', $treeHash);

        return $treeHash;
    }

    public function dumpOutput($res, $range = false, $httpRange = false)
    {
        if (($archive = $this->getArchive()) === false) {
            return $res->withStatus(500);
        }

        if (($f = fopen($file = $archive->getFile('data'), 'r')) === false) {
            return $res->withStatus(500);
        }

        if ($range) {
            list($from, $to) = $range;
        } else {
            $from = 0;
            $to = filesize($file) - 1;
        }

        if (fseek($f, $from) === -1) {
            return $res->withStatus(500);
        }

        $bufSize = 1024 * 1024 * 1;
        $readBytes = $to - $from + 1;
        $contentLength = $readBytes;
        $bytesWritten = 0;
        $data = '';

        while ($readBytes > 0 && !feof($f)) {
            $buf = fread($f, max($bufSize, $readBytes));
            if ($buf === false) {
                return $res->withStatus(500);
            }
            $readBytes -= strlen($buf);
            $data .= $buf;
        }

        if ($range && $range[0] == 0 && $range[1] == filesize($file) - 1) {
            $computeTreeHash = true;
        } else if ($range) {
            $computeTreeHash = \Gsandbox\TreeHashCheck::isTreeHashAligned($to + 1, $from, $to);
        } else {
            $computeTreeHash = true;
        }

        if ($computeTreeHash) {
            $hash = new TreeHash();
            $hash->update($data);
            $treeHash = bin2hex($hash->complete());
            $res = $res->withHeader('x-amz-sha256-tree-hash', $treeHash);
        }

        $res = $res->withHeader('Content-Type', 'application/octet-stream');
        $res = $res->withHeader('Content-Length', $contentLength);
        if ($httpRange) {
            $res = $res->withHeader('Content-Range', "{$httpRange[0]}-{$httpRange[1]}/$contentLength");
        }

        if (fseek($f, $from) === -1) {
            return $res->withStatus(500);
        }

        $readBytes = $to - $from + 1;

        $dumped = 0;
        $dumpBufSize = (1024 * 1024) / 2;
        while ($readBytes > 0 && !feof($f)) {
            $len = max($bufSize, $readBytes);
            if ($len > $readBytes) {
                $len = $readBytes;
            }
            $buf = fread($f, $len);
            if ($buf === false) {
                return $res->withStatus(500);
            }
            while (strlen($buf)) {
                $dump = substr($buf, 0, $dumpBufSize);
                $buf = substr($buf, strlen($dump));
                $res->getBody()->write($dump);
                $dumped += strlen($dump);
                $readBytes -= strlen($dump);
            }
            if (isset($GLOBALS['config']['downloadThrottle'])) {
                $GLOBALS['config']['downloadThrottle']();
            }
        }

        fclose($f);

        return $res->withStatus($httpRange ? 206 : 200);
    }
}
