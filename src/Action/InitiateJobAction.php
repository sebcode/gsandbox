<?php

namespace Gsandbox\Action;

use Gsandbox\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class InitiateJobAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];

    if (!($v = Vault::get($vaultName))) {
      return $res->withStatus(404);
    }

    $postData = file_get_contents('php://input');
    $params = json_decode($postData, true);
    $job = $v->createJob($params);

    return $res->withStatus(202)
      ->withHeader("x-amz-job-id", $job->id);
  }

}

