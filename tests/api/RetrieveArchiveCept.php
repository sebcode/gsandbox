<?php

use Aws\Glacier\TreeHash;

$I = new ApiTester($scenario);
$I->wantTo('Upload, retrieve and delete an archive.');
$I->haveAuth();

$I->sendPUT('/-/vaults/testvault');
$I->seeResponseCodeIs(201);

$data = '';
$archiveSize = (1024 * 1024) + 10;
for ($i = 0; $i < $archiveSize; $i++) {
    $data .= chr(rand(0, 255));
}
$data[0] = 'A';
$data[1] = 'B';
$data[2] = 'C';
$data[3] = 'D';

$treeHash = new TreeHash();
$treeHash->update($data);
$treeHash = bin2hex($treeHash->complete());

$hash = hash('sha256', $data);
$I->haveHttpHeader('Content-Type', 'application/octet-stream');
$I->haveHttpHeader('x-amz-archive-description', 'test123');
$I->haveHttpHeader('x-amz-sha256-tree-hash', $treeHash);
$I->haveHttpHeader('x-amz-content-sha256', $hash);
$I->sendPOST('/-/vaults/testvault/archives', $data);
$I->seeResponseCodeIs(201);
$I->seeResponseEquals('');
$archiveID = $I->grabHttpHeader('x-amz-archive-id');

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/-/vaults/testvault/jobs', [
  'Type' => 'archive-retrieval',
  'ArchiveId' => $archiveID,
]);
$I->seeResponseCodeIs(202);
$jobID = $I->grabHttpHeader('x-amz-job-id');

$I->sendGET("/-/vaults/testvault/jobs/$jobID");
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson([
  'StatusCode' => 'InProgress',
]);

$I->sendPOST("/-/vaults/testvault/jobs/$jobID/force-complete", []);
$I->seeResponseCodeIs(200);

$I->sendGET("/-/vaults/testvault/jobs/$jobID");
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson([
  'StatusCode' => 'Succeeded',
]);

$I->sendGET("/-/vaults/testvault/jobs/$jobID/output");
$I->seeResponseCodeIs(200);
$this->assertTrue($I->grabResponse() === $data);

$I->haveHttpHeader('Range', 'bytes=0-1');
$I->sendGET("/-/vaults/testvault/jobs/$jobID/output");
$I->seeResponseCodeIs(206);
$this->assertTrue($I->grabResponse() === 'AB');

$I->haveHttpHeader('Range', 'bytes=2-3');
$I->sendGET("/-/vaults/testvault/jobs/$jobID/output");
$I->seeResponseCodeIs(206);
$this->assertTrue($I->grabResponse() === 'CD');

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/-/vaults/testvault/jobs', [
  'Type' => 'archive-retrieval',
  'ArchiveId' => $archiveID,
  'RetrievalByteRange' => '1-1048575',
]);
$I->seeResponseCodeIs(202);
$jobID = $I->grabHttpHeader('x-amz-job-id');

$I->sendPOST("/-/vaults/testvault/jobs/$jobID/force-complete", []);
$I->seeResponseCodeIs(200);

$I->haveHttpHeader('Range', '');
$I->sendGET("/-/vaults/testvault/jobs/$jobID/output");
$I->seeResponseCodeIs(200);
$this->assertTrue($I->grabResponse() === substr($data, 1, (1024 * 1024) - 1));

$I->haveHttpHeader('Range', 'bytes=0-1');
$I->sendGET("/-/vaults/testvault/jobs/$jobID/output");
$I->seeResponseCodeIs(206);
$this->assertTrue($I->grabResponse() === $data[1] . $data[2]);

$I->sendDELETE("/-/vaults/testvault/archives/$archiveID");
$I->seeResponseCodeIs(204);

