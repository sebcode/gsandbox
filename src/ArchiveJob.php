<?php

namespace Gsandbox;

use Aws\Glacier\TreeHash;

class ArchiveJob extends Job {

  public function getArchive() {
    return $this->vault->getArchive($this->getParam('ArchiveId'));
  }

  public function serializeArray() {
    $ret = parent::serializeArray();

    if (($archive = $this->getArchive()) === false) {
      $ret['Status'] = 'Failed';
      return $ret;
    }

    if ($this->getCompleted()) {
      $ret['ArchiveId'] = $archive->id;
      $ret['ArchiveSizeInBytes'] = (int)$archive->getParam('Size');
      $ret['ArchiveSHA256TreeHash'] = $archive->getParam('SHA256TreeHash');
      $ret['SHA256TreeHash'] = $archive->getParam('SHA256TreeHash');
    }

    return $ret;
  }

  public function dumpOutput() {
    $request = $GLOBALS['request'];

    if (($archive = $this->getArchive()) === false) {
      return false;
    }

    if (($range = $request->getRange()) === false) {
      return false;
    }

    list($from, $to) = $range;

    if (($f = fopen($archive->getFile('data'), 'r')) === false) {
      return false;
    }

    if (fseek($f, $from) === -1) {
      return false;
    }

    $bufSize = 1024 * 1024 * 1;
    $readBytes = $to - $from + 1;
    $contentLength = $readBytes;
    $bytesWritten = 0;
    $hash = new TreeHash;
    $data = '';

    while ($readBytes > 0 && !feof($f)) {
      $buf = fread($f, max($bufSize, $readBytes));
      if ($buf === false) {
        return false;
      }
      $readBytes -= strlen($buf);
      $data .= $buf;
    }

    $hash->update($data);
    $treeHash = bin2hex($hash->complete());
    header("Content-Type: application/octet-stream");
    header("Content-Length: $contentLength");
    if (static::validPartSize($contentLength)) {
      header("x-amz-sha256-tree-hash: {$treeHash}");
    }

    if (fseek($f, $from) === -1) {
      return false;
    }

    $readBytes = $to - $from + 1;

    $dumped = 0;
    $dumpBufSize = (1024 * 1024) / 2;
    while ($readBytes > 0 && !feof($f)) {
      $buf = fread($f, max($bufSize, $readBytes));
      if ($buf === false) {
        return false;
      }
      while (strlen($buf)) {
        $dump = substr($buf, 0, $dumpBufSize);
        $buf = substr($buf, strlen($dump));
        echo $dump;
        flush();
        ob_flush();
        $dumped += strlen($dump);
        $readBytes -= strlen($dump);
      }
      if (isset($GLOBALS['config']['downloadThrottle'])) {
        $GLOBALS['config']['downloadThrottle']();
      }
    }

    fclose($f);
    return true;
  }

  public static function validPartSize($size) {
    $validPartSizes = array_map(function ($value) { return pow(2, $value) * (1024 * 1024); }, range(0, 12));
    return in_array($size, $validPartSizes);
  }

}

