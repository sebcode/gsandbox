<?php

$I = new ApiTester($scenario);
$I->wantTo('Set tags for a vault.');
$I->haveAuth();

$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(201);

$I->sendGET('/-/vaults/testvault/tags');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([ 'Tags' => [] ]);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/-/vaults/testvault/tags?operation=add', [
  'Tags' => [
    'Tag1' => 'Value1',
    'Tag2' => 'Value2',
  ],
]);
$I->seeResponseCodeIs(204);
$I->seeResponseEquals('');

$I->sendGET('/-/vaults/testvault/tags');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([ 'Tags' => [
  'Tag1' => 'Value1',
  'Tag2' => 'Value2',
] ]);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/-/vaults/testvault/tags?operation=remove', [
  'TagKeys' => [ 'Tag1' ],
]);
$I->seeResponseCodeIs(204);
$I->seeResponseEquals('');

$I->sendGET('/-/vaults/testvault/tags');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([ 'Tags' => [
  'Tag2' => 'Value2',
] ]);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/-/vaults/testvault/tags?operation=add', [
  'Tags' => [
    'Tag1' => 'Value1x',
    'Tag2' => 'Value2x',
  ],
]);
$I->seeResponseCodeIs(204);
$I->seeResponseEquals('');

$I->sendGET('/-/vaults/testvault/tags');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([ 'Tags' => [
  'Tag1' => 'Value1x',
  'Tag2' => 'Value2x',
] ]);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/-/vaults/testvault/tags?operation=add', [
  'Tags' => [
    'Tag1' => '1',
    'Tag2' => '1',
    'Tag3' => '1',
    'Tag4' => '1',
    'Tag5' => '1',
    'Tag6' => '1',
    'Tag7' => '1',
    'Tag8' => '1',
    'Tag9' => '1',
    'Tag10' => '1',
    'Tag11' => '1',
  ],
]);
$I->seeResponseCodeIs(400);
$I->seeResponseContainsJson([
  "code" => "LimitExceededException",
  "message" => "The quota for the number of tags that can be assigned to this resource has been reached.",
  "type" => "Client",
]);

$I->sendDELETE('/-/vaults/testvault');
$I->seeResponseCodeIs(204);

