<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;

use Aws\Glacier\TreeHash;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UploadMultipartUploadPartAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $vaultName = $args['vaultName'];
    $multipartID = $args['multipartID'];

    if (!($vault = Vault::get($vaultName))) {
      return $res->withStatus(404);
    }

    if (!($m = $vault->getMultipart($multipartID))) {
      return $res->withStatus(404);
    }

    $contentHash = $_SERVER['HTTP_X_AMZ_CONTENT_SHA256'];
    $treeHash = $_SERVER['HTTP_X_AMZ_SHA256_TREE_HASH'];
    $contentRange = $_SERVER['HTTP_CONTENT_RANGE'];
    $contentLength = $_SERVER['CONTENT_LENGTH'];

    // 'bytes 0-1048575/*'
    if (!preg_match('@(\d+)-(\d+)@', $contentRange, $match)) {
      return $res->withStatus(400)->write('Invalid range');
    }

    $rangeFrom = $match[1];
    $rangeTo = $match[2];

    $putData = file_get_contents('php://input');
    $actualContentLength = strlen($putData);
    if ($actualContentLength != $contentLength) {
      return $res->withStatus(400)
        ->write("invalid content length (expected: $contentLength actual: $actualContentLength)");
    }

    $hash = new TreeHash;
    $hash->update($putData);
    $actualTreeHash = bin2hex($hash->complete());

    if ($treeHash !== $actualTreeHash) {
      return $res->withStatus(400)
        ->write("tree hash mismatch actual:$actualTreeHash exp:$treeHash");
    }

    if (isset($GLOBALS['config']['uploadThrottle'])) {
      $GLOBALS['config']['uploadThrottle']();
    }

    if (!$m->putPart($rangeFrom, $rangeTo, $contentLength, $putData, $treeHash)) {
      return $res->withStatus(400)->write("putPart failed");
    }

    return $res->withStatus(204)
      ->withHeader('x-amz-sha256-tree-hash', $treeHash);
  }

}

