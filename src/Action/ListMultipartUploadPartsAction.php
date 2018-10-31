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

        $limit = min((int)$req->getParam('limit', 50), 50);
        $marker = (int)$req->getParam('marker', 0);

        if (isset($GLOBALS['config']['throwThrottlingExceptionForListMultiparts'])) {
            if ($GLOBALS['config']['throwThrottlingExceptionForListMultiparts']()) {
                return $res->throttlingException();
            }
        }

        if (!($vault = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        if (!($m = $vault->getMultipart($multipartID))) {
            return $res->uploadIdNotFoundException($multipartID);
        }

        return $res->withJson(
            $m->serializeArray(true, $limit, $marker), 200, JSON_PRETTY_PRINT
        );
    }
}
