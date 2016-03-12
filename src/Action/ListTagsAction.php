<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ListTagsAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];

    if (!($vault = Vault::get($vaultName))) {
      return $res->withStatus(404);
    }

    $jsonResponse = [
      'Tags' => $vault->getTags(),
    ];

    return $res->withJson($jsonResponse, 200, JSON_PRETTY_PRINT);
  }

}

