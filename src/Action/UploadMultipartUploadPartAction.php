<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Aws\Glacier\TreeHash;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UploadMultipartUploadPartAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        if (!empty($GLOBALS['config']['throwThrottlingExceptionForUpload'])) {
            return $res->throttlingException();
        }

        $vaultName = $args['vaultName'];
        $multipartID = $args['multipartID'];

        if (!($vault = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        if (!($m = $vault->getMultipart($multipartID))) {
            return $res->uploadIdNotFoundException($multipartID);
        }

        $contentHash = $req->getHeaderLine('x-amz-content-sha256');
        $treeHash = $req->getHeaderLine('x-amz-sha256-tree-hash');
        $contentRange = $req->getHeaderLine('Content-Range');
        $contentLength = $req->getHeaderLine('Content-Length');

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

        $hash = new TreeHash();
        $hash->update($putData);
        $actualTreeHash = bin2hex($hash->complete());

        if ($treeHash !== $actualTreeHash) {
            return $res->invalidHashException($actualTreeHash, $treeHash);
        }

        if (isset($GLOBALS['config']['uploadThrottle'])) {
            $GLOBALS['config']['uploadThrottle']();
        }

        if (!$m->putPart($rangeFrom, $rangeTo, $contentLength, $putData, $treeHash)) {
            return $res->withStatus(400)->write('putPart failed');
        }

        return $res->withStatus(204)
            ->withHeader('x-amz-sha256-tree-hash', $treeHash);
    }
}
