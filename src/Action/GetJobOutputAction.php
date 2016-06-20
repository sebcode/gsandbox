<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class GetJobOutputAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];
        $jobID = $args['jobID'];

        if (!empty($GLOBALS['config']['throwResourceNotFoundExceptionForGetJobOutput'])) {
            return $res->resourceNotFoundException();
        }

        if (!($vault = Vault::get($vaultName))) {
            return $res->resourceNotFoundException();
        }

        if (!($job = $vault->getJob($jobID))) {
            return $res->resourceNotFoundException();
        }

        if (!$job->hasOutput()) {
            return $res->resourceNotFoundException();
        }

        $range = false;
        $httpRange = false;

        if ($job->getAction() === 'ArchiveRetrieval') {
            $rangeFrom = 0;
            $rangeTo = filesize($job->getArchive()->getFile('data')) - 1;

            if ($rangeHeader = $job->getParam('RetrievalByteRange')) {
                if (!preg_match('@([0-9]+)?-([0-9]+)@', $rangeHeader, $m)) {
                    return $res->withStatus(400);
                }

                $rangeFrom = $m[1];
                $rangeTo = $m[2];
            }

            if ($rangeHeader = $req->getHeaderLine('Range')) {
                if (!preg_match('@([0-9]+)?-([0-9]+)@', $rangeHeader, $m)) {
                    return $res->withStatus(400);
                }

                $httpRange = [$m[1], $m[2]];

                $oRangeFrom = $rangeFrom;
                $oRangeTo = $rangeTo;

                $rangeLen = $m[2] - $m[1] + 1;
                $rangeFrom = $oRangeFrom + $m[1];
                $rangeTo = $oRangeFrom + (int)$m[2];
            }

            $range = [$rangeFrom, $rangeTo];
        }

        return $job->dumpOutput($res, $range, $httpRange);
    }
}
