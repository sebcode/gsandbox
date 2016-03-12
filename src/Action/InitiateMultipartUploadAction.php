<?php

namespace Gsandbox\Action;

use Gsandbox\Vault;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class InitiateMultipartUploadAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];

    if (!($vault = Vault::get($vaultName))) {
      return $res->withStatus(404);
    }

    if (empty($_SERVER['HTTP_X_AMZ_PART_SIZE'])) {
      return $res->withStatus(400)->write('Part size missing.');
    }

    $partSize = $_SERVER['HTTP_X_AMZ_PART_SIZE'];

    $desc = '';
    if (!empty($_SERVER['HTTP_X_AMZ_ARCHIVE_DESCRIPTION'])) {
      $desc = $_SERVER['HTTP_X_AMZ_ARCHIVE_DESCRIPTION'];
    }

    $m = $vault->createMultipart($partSize, $desc);

    return $res->withStatus(201)
      ->withHeader('x-amz-multipart-upload-id', $m->id);
  }

}

