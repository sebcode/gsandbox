<?php

namespace Gsandbox\Action;

use Gsandbox\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ResetSandboxAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $accessID = $args['accessID'];

    $GLOBALS['config']['storePath'] .= $accessID . '/';

    foreach (Vault::all() as $vault) {
      $vault->delete();
    }

    return $res->withStatus(200);
  }

}

