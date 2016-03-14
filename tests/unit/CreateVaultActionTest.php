<?php

use Gsandbox\Action\CreateVaultAction;
use Gsandbox\Model\Vault;
use Slim\Http\Response;

class CreateVaultActionTest extends ActionTestBase
{
    public function testInvoke()
    {
        $action = new CreateVaultAction;

        $dir = $GLOBALS['vaultStorePath'] . 'testvault';
        $this->assertFalse(is_dir($dir));
        $this->assertFalse(Vault::get('testvault'));

        $res = $action->__invoke($this->getRequest('GET'), new Response(), [ 'vaultName' => 'testvault' ]);

        $this->assertEquals(201, $res->getStatusCode());
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(Vault::get('testvault') instanceof Vault);
    }
}
