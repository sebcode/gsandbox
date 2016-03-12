<?php

namespace Gsandbox\Action;

use Gsandbox\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteMultipartUploadAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];
    $multipartID = $args['multipartID'];

    if (!($vault = Vault::get($vaultName))) {
      return $res->withStatus(404);
    }

    if ($m = $vault->getMultipart($multipartID)) {
      $m->delete();
    }

    return $res->withStatus(204);
  }

}

