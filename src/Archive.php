<?php

namespace Gsandbox;

class Archive extends VaultEntity {

  public function getSubdir() {
    return 'archives';
  }

  public function serializeArray() {
    $ret = [
      "ArchiveId" => $this->id,
      "ArchiveDescription" => $this->getParam('Description'),
      "CreationDate" => $this->getCreationDate()->format(Vault::DATEFORMAT),
      "Size" => (int)$this->getParam('Size'),
      "SHA256TreeHash" => $this->getParam('SHA256TreeHash'),
    ];

    return $ret;
  }

}

