<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class InitiateMultipartUploadAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];

        if (!($vault = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        if (!($partSize = $req->getHeaderLine('x-amz-part-size'))) {
            return $res->withStatus(400)->write('Part size missing.');
        }

        if (!($desc = $req->getHeaderLine('x-amz-archive-description'))) {
            $desc = '';
        }

        $m = $vault->createMultipart($partSize, $desc);

        return $res->withStatus(201)
            ->withHeader('x-amz-multipart-upload-id', $m->getId());
    }
}
