<?php

$I = new MyApiTester($scenario);
$I->wantTo('Create a new vault.');

$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(404);

$I->haveAuth();
$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(201);
$I->seeResponseEquals('');

$I->haveAuth();
$I->sendDELETE('/-/vaults/testvault');
$I->seeResponseCodeIs(204);
$I->seeResponseEquals('');

