<?php

namespace Gsandbox\Action;

use Gsandbox\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ListVaultsAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $ret = [];

    foreach (Vault::all() as $vault) {
      $ret[] = $vault->serializeArray();
    }

    $jsonResponse = [ 'VaultList' => $ret ];

    return $res->withJson($jsonResponse, 200, JSON_PRETTY_PRINT);
  }

}

