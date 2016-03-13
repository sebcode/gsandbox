<?php

namespace Gsandbox\Model;

class Job extends VaultEntity
{
    public static function get($id, $vault)
    {
        $job = new self($id, $vault);
        if (!$job->exists()) {
            return false;
        }

        switch ($job->getAction()) {
        case 'InventoryRetrieval':
            return new InventoryJob($id, $vault);
        case 'ArchiveRetrieval':
            return new ArchiveJob($id, $vault);
        }

        return false;
    }

    public function getSubdir()
    {
        return 'jobs';
    }

    public function hasOutput()
    {
        return $this->getCompleted();
    }

    public function dumpOutput($res, $range = false)
    {
        error_log('Use subclasses\' ::dumpOutput.');

        return $res->withStatus(500);
    }

    public function getStatus()
    {
        if ($this->getCompleted()) {
            return 'Succeeded';
        } else {
            return 'InProgress';
        }
    }

    public function getAction()
    {
        $type = $this->getParam('Type');
        switch ($type) {
        case 'inventory-retrieval':
            return 'InventoryRetrieval';
        case 'archive-retrieval':
            return 'ArchiveRetrieval';
        }

        return;
    }

    public function hasExpired()
    {
        $expireDate = $this->getCreationDate();
        $expireDate->modify('+24 hour');

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('UTC'));

        return $now >= $expireDate;
    }

    public function getCompletionDate()
    {
        $completeDate = $this->getCreationDate();

        if (!empty($GLOBALS['config']['inventoryComplete'])) {
            $completeDate->modify($GLOBALS['config']['inventoryComplete']);
        }

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('UTC'));

        if ($now >= $completeDate) {
            return $completeDate;
        }

        return;
    }

    public function getCompleted()
    {
        return $this->getCompletionDate() != null;
    }

    public function getDesc()
    {
        return $this->getParam('Description');
    }

    public function serializeArray()
    {
        $ret = [
            'StatusCode' => $this->getStatus(),
            'CreationDate' => $this->getCreationDate()->format(Vault::DATEFORMAT),
            'JobDescription' => $this->getDesc(),
            'JobId' => $this->id,
            'VaultARN' => $this->vault->getARN(),
            'SNSTopic' => null,
            'StatusMessage' => null,
            'ArchiveSHA256TreeHash' => null,
            'Action' => $this->getAction(),
            'ArchiveSizeInBytes' => null,
            'CompletionDate' => null,
            'SHA256TreeHash' => null,
            'ArchiveId' => null,
            'InventorySizeInBytes' => null,
            'Completed' => false,
            'RetrievalByteRange' => null,
            'InventoryRetrievalParameters' => [
                'Limit' => null,
                'Marker' => null,
                'Format' => 'JSON',
                'EndDate' => null,
                'StartDate' => null,
            ],
        ];

        if ($date = $this->getCompletionDate()) {
            $ret['CompletionDate'] = $date->format(Vault::DATEFORMAT);
            $ret['Completed'] = true;
        }

        return $ret;
    }
}
