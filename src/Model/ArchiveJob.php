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
        $ret['ArchiveSHA256TreeHash'] = $archiveTreeHash = $archive->getParam('SHA256TreeHash');
        $ret['RetrievalByteRange'] = $this->getRetrievalByteRange();

        if ($this->getCompleted()) {
            $ret['SHA256TreeHash'] = $this->getSHA256TreeHash($archiveTreeHash);
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
            $range = "0-" . ($this->getArchiveSizeInBytes() - 1);
        }

        return $range;
    }

    // TODO: Handle this case: You request a range to return of the retrieved
    // data that goes to the end of the data, and the start of the range is a
    // multiple of the size of the range to retrieve rounded up to the next power
    // of two but not smaller than one megabyte (1024 KB). For example, if you
    // have 3.1 MB of retrieved data and you specify a range that starts at 2 MB
    // and ends at 3.1 MB (the end of the data), then the x-amz-sha256-tree-hash
    // is returned as a response header.
    private function getSHA256TreeHash($archiveTreeHash) {
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
        if (count($range) !== 2) {
            throw new \Exception('Could not determine retrieval range.');
        }

        list($from, $to) = $range;

        /* Retrieving full range. */
        if ($from == 0 && $to == filesize($file) - 1) {
            $this->setParam('SHA256TreeHash', $archiveTreeHash);
            return $archiveTreeHash;
        }

        /* Only calculate tree hash if range is tree hash aligned. */
        $computeTreeHash = \Gsandbox\TreeHashCheck::isTreeHashAligned($to + 1, $from, $to);
        if (!$computeTreeHash) {
            return null;
        }

        if (fseek($f, $from) === -1) {
            throw new \Exception('fseek failed');
        }

        $bufSize = 1024 * 1024 * 1;
        $readBytes = $to - $from + 1;

        $hash = new TreeHash();

        while ($readBytes > 0 && !feof($f)) {
            $buf = fread($f, min($bufSize, $readBytes));
            if ($buf === false) {
                throw new \Exception('fread failed');
            }
            $readBytes -= strlen($buf);

            $hash->update($buf);
        }

        $treeHash = bin2hex($hash->complete());
        $this->setParam('SHA256TreeHash', $treeHash);
        return $treeHash;
    }

    private static function computeRangeTreeHash($f, $to, $from)
    {
        $bufSize = 1024 * 1024 * 1;
        $readBytes = $to - $from + 1;
        $contentLength = $readBytes;
        $hash = new TreeHash();

        while ($readBytes > 0 && !feof($f)) {
            $buf = fread($f, min($bufSize, $readBytes));
            if ($buf === false) {
                return false;
            }
            $readBytes -= strlen($buf);
            $hash->update($buf);
        }

        $treeHash = bin2hex($hash->complete());
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

        $meta = $this->serializeArray();
        $initiateJobIsTreeHashAligned = !empty($meta['SHA256TreeHash']);
        $partIsTreeHashAligned = \Gsandbox\TreeHashCheck::isTreeHashAligned($to + 1, $from, $to);

        /* x-amz-sha256-tree-hash: this header appears when the retrieved data
        range requested in the Initiate Job request is tree hash aligned and
        the range to download in the Get Job Output is also tree hash aligned.
        */
        if ($initiateJobIsTreeHashAligned
            && $partIsTreeHashAligned
            && ($treeHash = static::computeRangeTreeHash($f, $to, $from))) {

            $res = $res->withHeader('x-amz-sha256-tree-hash', $treeHash);
        }

        $readBytes = $to - $from + 1;
        $contentLength = $readBytes;

        $res = $res->withHeader('Content-Type', 'application/octet-stream');
        $res = $res->withHeader('Content-Length', $contentLength);
        if ($httpRange) {
            $res = $res->withHeader('Content-Range', "{$httpRange[0]}-{$httpRange[1]}/$contentLength");
        }

        if (fseek($f, $from) === -1) {
            return $res->withStatus(500);
        }

        $bufSize = 1024 * 1024 * 1;
        $dumped = 0;
        $dumpBufSize = (1024 * 1024) / 2;
        while ($readBytes > 0 && !feof($f)) {
            $len = min($bufSize, $readBytes);
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
