<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;

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

    $range = false;
    if ($rangeHeader = $req->getHeaderLine('Range')) {
      if (!preg_match('@([0-9]+)?-([0-9]+)@', $rangeHeader, $m)) {
        return $res->withStatus(400);
      }

      $range = [ $m[1], $m[2] ];
    }

    return $job->dumpOutput($res, $range);
  }

}

