<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class FinalizeMultipartUploadAction
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

        $treeHash = $req->getHeaderLine('x-amz-sha256-tree-hash');
        $archiveSize = $req->getHeaderLine('x-amz-archive-size');

        if (($a = $m->finalize($archiveSize, $treeHash)) === false) {
            return $res->withStatus(400)->write('Finalize failed');
        }

        return $res->withStatus(201)
            ->withHeader('x-amz-archive-id', $a->getId());
    }
}
