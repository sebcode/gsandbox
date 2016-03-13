<?php

$I = new ApiTester($scenario);
$I->wantTo('Create a new vault.');

$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(403);

$I->haveAuth();
$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(201);
$I->seeResponseEquals('');

$I->haveAuth();
$I->sendDELETE('/-/vaults/testvault');
$I->seeResponseCodeIs(204);
$I->seeResponseEquals('');

