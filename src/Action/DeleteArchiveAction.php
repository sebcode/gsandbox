<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteArchiveAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];
        $archiveID = $args['archiveID'];

        if (!($v = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        if ($archive = $v->getArchive($archiveID)) {
            $archive->delete();
        }

        $v->invalidateInventory();

        return $res->withStatus(204);
    }
}
