<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DescribeJobAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];
        $jobID = $args['jobID'];

        if (!($vault = Vault::get($vaultName))) {
            return $res->resourceNotFoundException();
        }

        if (!($job = $vault->getJob($jobID))) {
            return $res->resourceNotFoundException();
        }

        return $res->withJson($job->serializeArray(true), 200, JSON_PRETTY_PRINT);
    }
}
