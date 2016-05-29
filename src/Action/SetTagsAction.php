<?php

namespace Gsandbox\Action;

use Gsandbox\Model\Vault;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class SetTagsAction
{
    public function __invoke(Request $req, Response $res, $args = [])
    {
        $vaultName = $args['vaultName'];

        if (!($vault = Vault::get($vaultName))) {
            return $res->withStatus(404);
        }

        $operation = $req->getParam('operation');

        $postData = file_get_contents('php://input');
        $params = json_decode($postData, true);

        try {
            if ($operation === 'add') {
                if (empty($params['Tags'])) {
                    return $res->withStatus(400);
                }

                $vault->addTags($params['Tags']);

                return $res->withStatus(204);
            } elseif ($operation === 'remove') {
                if (empty($params['TagKeys'])) {
                    return $res->withStatus(204);
                }

                $vault->removeTags($params['TagKeys']);

                return $res->withStatus(204);
            }

            return $res->withStatus(400);
        } catch (\Gsandbox\Model\LimitExceededException $e) {
            return $res->limitExceededException();
        }
    }
}
