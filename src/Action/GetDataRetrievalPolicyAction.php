<?php

namespace Gsandbox\Action;

use Gsandbox\Model\DataRetrievalPolicy;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class GetDataRetrievalPolicyAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $policy = new DataRetrievalPolicy();
        $ruleset = $policy->get();

        return $res->withJson($policy->get(), 200, JSON_PRETTY_PRINT);
    }
}
