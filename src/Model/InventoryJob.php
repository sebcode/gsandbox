<?php

namespace Gsandbox\Model;

class InventoryJob extends Job
{
    public function dumpOutput($res, $range = false)
    {
        $file = $this->getFile('inventory');

        if (!file_exists($file)) {
            $this->generateInventory();
        }

        $contentLength = filesize($file);
        $res = $res->withHeader('Content-Type', 'application/json');
        $res = $res->withHeader('Content-Length', $contentLength);

        if (($f = fopen($file, 'r')) === false) {
            return $res->withStatus(404);
        }
        fpassthru($f);
        fclose($f);

        return $res->withStatus(200);
    }

    public function generateInventory()
    {
        if (!$this->getCompleted()) {
            return false;
        }

        $file = $this->getFile('inventory');

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('UTC'));

        $archiveList = [];

        foreach ($this->vault->getArchives() as $archive) {
            $archiveList[] = $archive->serializeArray();
        }

        $ret = [
            'VaultARN' => $this->vault->getARN(),
            'InventoryDate' => $date->format(Vault::DATEFORMAT),
            'ArchiveList' => $archiveList,
        ];

        file_put_contents($file, json_encode($ret, JSON_PRETTY_PRINT));

        return true;
    }
}
