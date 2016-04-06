<?php

namespace Gsandbox\Model;

class InventoryJob extends Job
{
    public function dumpOutput($res, $range = false)
    {
        $inventoryFile = $this->vault->getInventoryCacheFile();
        $jobInventoryFile = $this->getFile('inventory');

        if (!file_exists($jobInventoryFile)) {
            if (!$this->vault->hasCachedInventory()) {
                $this->vault->generateInventory();
            }

            if (!copy($inventoryFile, $jobInventoryFile)) {
                throw new Exception("Could not copy $inventoryFile to $jobInventoryFile");
            }
        }

        $contentLength = filesize($jobInventoryFile);
        $res = $res->withHeader('Content-Type', 'application/json');
        $res = $res->withHeader('Content-Length', $contentLength);

        if (($f = fopen($jobInventoryFile, 'r')) === false) {
            return $res->withStatus(404);
        }
        fpassthru($f);
        fclose($f);

        return $res->withStatus(200);
    }
}
