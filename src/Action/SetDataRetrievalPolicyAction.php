<?php

namespace Gsandbox\Action;

use Gsandbox\DataRetrievalPolicy;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class SetDataRetrievalPolicyAction {

  public function __invoke(Request $req, Response $res, $args = []) {
    $putData = file_get_contents('php://input');
    $actualContentLength = strlen($putData);
    if ($actualContentLength != $contentLength) {
      return $res->withStatus(400)
        ->write("invalid content length (expected: $contentLength actual: $actualContentLength)");
    }

    if (!($ruleset = json_decode($putData, true))) {
      return $res->withStatus(400)
        ->write("Cannot decode JSON data.");
    }

    try {
      $policy = new DataRetrievalPolicy;
      $ruleset = $policy->set($ruleset);
      return $res->withStatus(204);
    } catch (Gsandbox\InvalidPolicyException $e) {
      return $res->withStatus(400)->write($e->getMessage());
    }
  }

}

