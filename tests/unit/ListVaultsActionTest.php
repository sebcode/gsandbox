<?php

use Gsandbox\Action\ListVaultsAction;
use Gsandbox\Model\Vault;
use Slim\Http\Response;

class ListVaultsActionTest extends ActionTestBase
{
    public function testEmptyResponse()
    {
        $action = new ListVaultsAction;

        $exp = ['Marker' => null, 'VaultList' => []];

        $res = $action->__invoke($this->getRequest('GET'), new Response(), []);

        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals($exp, json_decode($res->getBody(), true));
    }

    public function testMultipleVaults() {
        $this->assertTrue(Vault::create('testvault') instanceof Vault);

        $action = new ListVaultsAction;

        $exp = ['Marker' => null, 'VaultList' => [[
            "VaultName" => "testvault",
            "CreationDate" => "XXX",
            "LastInventoryDate" => null,
            "NumberOfArchives" => 0,
            "SizeInBytes" => 0,
            "VaultARN" => "FAKEARN/testvault",
        ]]];

        $res = $action->__invoke($this->getRequest('GET'), new Response(), []);

        $this->assertEquals(200, $res->getStatusCode());

        $actual = json_decode($res->getBody(), true);
        $actual['VaultList'][0]['CreationDate'] = 'XXX';
        $this->assertEquals($exp, $actual);

        $this->assertTrue(Vault::create('testvault2') instanceof Vault);

        $exp['VaultList'][] = [
            "VaultName" => "testvault2",
            "CreationDate" => "XXX",
            "LastInventoryDate" => null,
            "NumberOfArchives" => 0,
            "SizeInBytes" => 0,
            "VaultARN" => "FAKEARN/testvault2",
        ];

        $res = $action->__invoke($this->getRequest('GET'), new Response(), []);

        $this->assertEquals(200, $res->getStatusCode());

        $actual = json_decode($res->getBody(), true);
        $actual['VaultList'][0]['CreationDate'] = 'XXX';
        $actual['VaultList'][1]['CreationDate'] = 'XXX';
        $this->assertEquals($exp, $actual);
    }
}
