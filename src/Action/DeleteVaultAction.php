<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteVaultAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];

    if ($v = Vault::get($vaultName)) {
      $v->delete();
    }

    return $res->withStatus(204);
  }

}

