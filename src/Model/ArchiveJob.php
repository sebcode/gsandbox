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

        if ($this->getCompleted()) {
            $ret['ArchiveId'] = $archive->id;
            $ret['ArchiveSizeInBytes'] = (int) filesize($archive->getFile('data'));
            $ret['ArchiveSHA256TreeHash'] = $archive->getParam('SHA256TreeHash');
            $ret['SHA256TreeHash'] = $archive->getParam('SHA256TreeHash');
        }

        return $ret;
    }

    public function dumpOutput($res, $range = false)
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
            $to = filesize($file);
        }

        if (fseek($f, $from) === -1) {
            return $res->withStatus(500);
        }

        $bufSize = 1024 * 1024 * 1;
        $readBytes = $to - $from + 1;
        $contentLength = $readBytes;
        $bytesWritten = 0;
        $hash = new TreeHash();
        $data = '';

        while ($readBytes > 0 && !feof($f)) {
            $buf = fread($f, max($bufSize, $readBytes));
            if ($buf === false) {
                return $res->withStatus(500);
            }
            $readBytes -= strlen($buf);
            $data .= $buf;
        }

        $hash->update($data);
        $treeHash = bin2hex($hash->complete());
        $res = $res->withHeader('Content-Type', 'application/octet-stream');
        $res = $res->withHeader('Content-Length', $contentLength);
        if (static::validPartSize($contentLength)) {
            $res = $res->withHeader('x-amz-sha256-tree-hash', $treeHash);
        }

        if (fseek($f, $from) === -1) {
            return $res->withStatus(500);
        }

        $readBytes = $to - $from + 1;

        $dumped = 0;
        $dumpBufSize = (1024 * 1024) / 2;
        while ($readBytes > 0 && !feof($f)) {
            $buf = fread($f, max($bufSize, $readBytes));
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

        return $res->withStatus(200);
    }

    public static function validPartSize($size)
    {
        $validPartSizes = array_map(function ($value) { return pow(2, $value) * (1024 * 1024); }, range(0, 12));

        return in_array($size, $validPartSizes);
    }
}
