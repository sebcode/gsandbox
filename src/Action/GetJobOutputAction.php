<?php

namespace Gsandbox\Action;

use Gsandbox\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class GetJobOutputAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];
    $jobID = $args['jobID'];

    if (!($vault = Vault::get($vaultName))) {
      return $res->withStatus(404);
    }

    if (!($job = $vault->getJob($jobID))) {
      return $res->withStatus(404);
    }

    if (!$job->hasOutput()) {
      return $res->withStatus(404);
    }

    if (!$job->dumpOutput()) {
      return $res->withStatus(404);
    }

    // XXX refactor
    exit();
  }

}

