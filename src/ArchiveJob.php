<?php

namespace Gsandbox;

use Aws\Common\Hash\TreeHash;

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

    $bufSize = 8192;
    $readBytes = $to - $from + 1;
    $contentLength = $readBytes;
    $bytesWritten = 0;
    $hash = new TreeHash;

    while ($readBytes > 0 && !feof($f)) {
      $buf = fread($f, max($bufSize, $readBytes));
      if ($buf === false) {
        return false;
      }
      $readBytes -= strlen($buf);
      $hash->addData($buf);
    }

    $treeHash = $hash->getHash();
    header("x-amz-sha256-tree-hash: {$treeHash}");
    header("Content-Length: $contentLength");

    if (fseek($f, $from) === -1) {
      return false;
    }

    $readBytes = $to - $from + 1;

    $dumped = 0;
    while ($readBytes > 0 && !feof($f)) {
      $buf = fread($f, max($bufSize, $readBytes));
      if ($buf === false) {
        return false;
      }
      $readBytes -= strlen($buf);
      $dumped += strlen($buf);
      echo $buf;
      if (isset($GLOBALS['config']['downloadThrottle'])) {
        $GLOBALS['config']['downloadThrottle']();
      }
    }

    fclose($f);
    return true;
  }

}

