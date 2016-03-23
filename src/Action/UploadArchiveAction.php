<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Gsandbox\Model\Archive;
use Aws\Glacier\TreeHash;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UploadArchiveAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];

        if (!($vault = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        if (!($contentLength = $req->getHeaderLine('Content-Length'))) {
            return $res->withStatus(400)->write('Content-Length header missing.');
        }
        $contentLength = (int) $contentLength;

        if (!($desc = $req->getHeaderLine('x-amz-archive-description'))) {
            $desc = '';
        }

        if (!($contentHash = $req->getHeaderLine('x-amz-content-sha256'))) {
            return $res->withStatus(400)->write('Header x-amz-content-sha256 missing.');
        }

        if (!($treeHash = $req->getHeaderLine('x-amz-sha256-tree-hash'))) {
            return $res->withStatus(400)->write('Header x-amz-sha256-tree-hash missing.');
        }

        $putData = file_get_contents('php://input');
        $actualContentLength = strlen($putData);
        if ($actualContentLength != $contentLength) {
            return $res->withStatus(400)
                ->write("invalid content length (expected: $contentLength actual: $actualContentLength)");
        }

        $hash = new TreeHash();
        $hash->update($putData);
        $actualTreeHash = bin2hex($hash->complete());

        if ($treeHash !== $actualTreeHash) {
            return $res->withStatus(400)
                ->write("tree hash mismatch actual:$actualTreeHash exp:$treeHash");
        }

        if (isset($GLOBALS['config']['uploadThrottle'])) {
            $GLOBALS['config']['uploadThrottle']();
        }

        $a = new Archive(true, $vault);
        $a->setParam('SHA256TreeHash', $treeHash);
        $a->setParam('Size', (int)$contentLength);
        $a->setParam('Description', $desc);

        if (($ret = file_put_contents($a->getFile('data'), $putData)) !== $contentLength) {
            return $res->withStatus(500)
                ->write("Could not write archive data: ". $a->getFile('data'));
        }

        return $res->withStatus(201)
            ->withHeader('x-amz-multipart-upload-id', $a->getId());
    }
}
