<?php

namespace Gsandbox\Middleware;

class Auth
{
    public function __invoke($req, $res, $next)
    {
        if (!$req->hasHeader('Authorization')) {
            return $res->withStatus(403);
        }

        $auth = $req->getHeaderLine('Authorization');
        if (!preg_match('@Credential=([A-Z0-9]+)@', $auth, $m)) {
            return $res->withStatus(403);
        }

        if (!is_dir($dir = $GLOBALS['config']['storePath'].'/'.$m[1])) {
            return $res->withStatus(403);
        }

        $GLOBALS['vaultStorePath'] = "$dir/vaults/";

        if (isset($GLOBALS['config']['responseDelay'])) {
            $GLOBALS['config']['responseDelay']();
        }

        return $next($req, $res);
    }
}
