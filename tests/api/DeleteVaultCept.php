<?php

$I = new MyApiTester($scenario);
$I->wantTo('Delete a vault.');

$this->assertFalse(is_dir($GLOBALS['storePath'] . '/vaults/testvault'));

$I->haveAuth();
$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(201);
$I->seeResponseEquals('');

$this->assertTrue(is_dir($GLOBALS['storePath'] . '/vaults/testvault'));

$I->sendDELETE('/-/vaults/testvault');
$I->seeResponseCodeIs(204);
$I->seeResponseEquals('');

$this->assertFalse(is_dir($GLOBALS['storePath'] . '/vaults/testvault'));
