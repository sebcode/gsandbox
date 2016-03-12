<?php

namespace Gsandbox\Action;

use Gsandbox\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DescribeVaultAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];

    if (!($v = Vault::get($vaultName))) {
      return $res->withStatus(404);
    }

    return $res->withJson($v->serializeArray(), 200, JSON_PRETTY_PRINT);
  }

}

