<?php

$I = new MyApiTester($scenario);
$I->wantTo('List vaults.');
$I->haveAuth();

$I->sendPUT('/-/vaults/testvault1');
$I->seeResponseCodeIs(201);
$I->sendPUT('/-/vaults/testvault2');
$I->seeResponseCodeIs(201);
$I->sendPUT('/-/vaults/testvault3');
$I->seeResponseCodeIs(201);

$I->sendGET('/-/vaults');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseJsonMatchesJsonPath('$.VaultList');
$I->seeResponseJsonMatchesJsonPath('$.VaultList[*].VaultName');
$I->seeResponseJsonMatchesJsonPath('$.VaultList[*].CreationDate');
$I->seeResponseJsonMatchesJsonPath('$.VaultList[*].LastInventoryDate');
$I->seeResponseJsonMatchesJsonPath('$.VaultList[*].NumberOfArchives');
$I->seeResponseJsonMatchesJsonPath('$.VaultList[*].SizeInBytes');
$I->seeResponseJsonMatchesJsonPath('$.VaultList[*].VaultARN');

$I->sendDELETE('/-/vaults/testvault1');
$I->seeResponseCodeIs(204);
$I->sendDELETE('/-/vaults/testvault2');
$I->seeResponseCodeIs(204);
$I->sendDELETE('/-/vaults/testvault3');
$I->seeResponseCodeIs(204);

