<?php

namespace Gsandbox;

class InventoryJob extends Job {

  public function dumpOutput() {
    $file = $this->getFile('inventory');

    if (!file_exists($file)) {
      $this->generateInventory();
    }

    if (($f = fopen($file, 'r')) === false) {
      return false;
    }
    fpassthru($f);
    fclose($f);

    return true;
  }

  public function generateInventory() {
    if (!$this->getCompleted()) {
      return false;
    }

    $file = $this->getFile('inventory');

    $date = new \DateTime();
    $date->setTimezone(new \DateTimeZone("UTC"));

    $archiveList = [];

    foreach ($this->vault->getArchives() as $archive) {
      $archiveList[] = $archive->serializeArray();
    }

    $ret = [
      "VaultARN" => $this->vault->getARN(),
      "InventoryDate" => $date->format(Vault::DATEFORMAT),
      "ArchiveList" => $archiveList,
    ];

    file_put_contents($file, json_encode($ret, JSON_PRETTY_PRINT));
    return true;
  }

}

