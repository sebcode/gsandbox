<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ListJobsAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];

        if (!($vault = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        $jsonResponse = ['JobList' => []];

        foreach ($vault->getJobs() as $job) {
            $jsonResponse['JobList'][] = $job->serializeArray();
        }

        return $res->withJson($jsonResponse, 200, JSON_PRETTY_PRINT);
    }
}
