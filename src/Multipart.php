<?php

namespace Gsandbox;

class Multipart extends VaultEntity {

  public function getSubdir() {
    return 'multiparts';
  }

  public function initiate($partSize, $desc) {
    $this->setParam('PartSize', $partSize);
    $this->setParam('Description', $desc);
  }

  public function serializeArray($withParts = false) {
    $ret = [
      "MultipartUploadId" => $this->id,
      "CreationDate" => $this->getCreationDate()->format(Vault::DATEFORMAT),
      "ArchiveDescription" => $this->getParam('Description'),
      "PartSizeInBytes" => (int)$this->getParam('PartSize'),
      "VaultARN" => $this->vault->getARN(),
    ];

    if ($withParts) {
      $parts = [];
      foreach ($this->getParts() as $range => $hash) {
        $parts[] = [
          'RangeInBytes' => $range,
          'SHA256TreeHash' => $hash,
        ];
      }
      $ret['Parts'] = $parts;
    }

    return $ret;
  }

  public function getParts() {
    $metaPartsFile = $this->getFile('parts');
    if (!file_exists($metaPartsFile)) {
      return [];
    }

    return include($metaPartsFile);
  }

  public function finalize($totalSize, $treeHash) {
    $file = $this->getFile('data');

    if (($f = fopen($file, 'r+')) === false) {
      return false;
    }

    ftruncate($f, $totalSize);
    fclose($f);

    $a = new Archive(true, $this->vault);
    $a->setParam('SHA256TreeHash', $treeHash);
    $a->setParam('Size', $totalSize);
    $a->setParam('Description', $this->getParam('Description'));

    rename($this->getFile('data'), $a->getFile('data'));
    $this->delete();
    return $a;
  }

  public function putPart($rangeFrom, $rangeTo, $contentLength, $putData, $treeHash) {
    $requiredTargetSize = $rangeFrom + $contentLength;
    $file = $this->getFile('data');

    if (!file_exists($file)) {
      touch($file);
    }
    clearstatcache();
    if (filesize($file) < $requiredTargetSize) {
      while (filesize($file) < $requiredTargetSize) {
        clearstatcache();
        $data = str_repeat('0', 1024 * 1024);
        if (file_put_contents($file, $data, FILE_APPEND) === false) {
          return false;
        }
      }
    }
    clearstatcache();

    if (($f = fopen($file, 'r+')) === false) {
      return false;
    }

    fseek($f, $rangeFrom);

    $total = 0;
    while (strlen($putData) > 0) {
      $bytesWritten = fwrite($f, substr($putData, 0, 1024 * 1024));
      $total += $bytesWritten;
      $putData = substr($putData, $bytesWritten);
    }
    fclose($f);

    $metaPartsFile = $this->getFile('parts');
    $parts = $this->getParts();
    $parts["{$rangeFrom}-{$rangeTo}"] = $treeHash;
    file_put_contents($metaPartsFile, "<?php return " . var_export($parts, true) . ';');
    if (function_exists('opcache_invalidate')) {
      opcache_invalidate($metaPartsFile);
    }

    return true;
  }

}

