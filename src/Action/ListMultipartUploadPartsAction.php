<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ListMultipartUploadPartsAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];
        $multipartID = $args['multipartID'];

        if (!($vault = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        if (!($m = $vault->getMultipart($multipartID))) {
            return $res->withStatus(404);
        }

        return $res->withJson($m->serializeArray(true), 200, JSON_PRETTY_PRINT);
    }
}
