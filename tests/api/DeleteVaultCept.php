<?php

$I = new ApiTester($scenario);
$I->wantTo('Delete a vault.');

$I->haveAuth();
$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(201);
$I->seeResponseEquals('');

$I->sendDELETE('/-/vaults/testvault');
$I->seeResponseCodeIs(204);
$I->seeResponseEquals('');

