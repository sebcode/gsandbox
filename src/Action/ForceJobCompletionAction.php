<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ForceJobCompletionAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];
        $jobID = $args['jobID'];

        if (!($v = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        if (!($job = $v->getJob($jobID))) {
            return $res->withStatus(404);
        }

        $job->forceCompletion();

        return $res->withStatus(200);
    }
}
