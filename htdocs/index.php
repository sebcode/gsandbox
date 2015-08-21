<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Aws\Common\Hash\TreeHash;
use Gsandbox\Request;
use Gsandbox\Vault;
use Gsandbox\Multipart;
use Gsandbox\Job;

$config = include(__DIR__ . '/../config.php');

$request = new Request();

$app = new \Slim\Slim([
  'debug' => false,
  #'log.level' => \Slim\Log::DEBUG,
  #'log.enabled' => true,
]);

$app->error(function (\Exception $e) use ($app) {
  error_log(var_export($e->getMessage(), true));//XXX
});

if ($request->accessKey === false) {
  $app->get('/', function () {
    echo 'gsandbox';
    return200();
  });

  $app->get('/-/sandbox/reset/:accessID', function ($accessID) {
    $GLOBALS['config']['storePath'] .= $accessID . '/';
    foreach (Vault::all() as $vault) {
      $vault->delete();
    }
    return200();
  });

  $app->run();
  badRequest();
}

if (isset($GLOBALS['config']['responseDelay'])) {
  $GLOBALS['config']['responseDelay']();
}

$GLOBALS['config']['storePath'] .= $request->accessKey . '/';

// Delete archive.
$app->delete('/-/vaults/:vaultName/archives/:archiveID', function ($vaultName, $archiveID) {
  $vault = getVault($vaultName);

  if ($archive = $vault->getArchive($archiveID)) {
    $archive->delete();
  }

  return204();
});

// Initiate job.
$app->post('/-/vaults/:vaultName/jobs', function ($vaultName) {
  $vault = getVault($vaultName);

  $postData = file_get_contents('php://input');
  $params = json_decode($postData, true);
  $job = $vault->createJob($params);

  header("x-amz-job-id: {$job->id}");
  return201();
});

// Get job output.
$app->get('/-/vaults/:vaultName/jobs/:jobID/output', function ($vaultName, $jobID) {
  $job = getJob($vaultName, $jobID);

  if (!$job->hasOutput()) {
    notFound();
  }

  if (!$job->dumpOutput()) {
    notFound();
  }

  exit();
});

// Describe job.
$app->get('/-/vaults/:vaultName/jobs/:jobID', function ($vaultName, $jobID) {
  $job = getJob($vaultName, $jobID);
  response($job->serializeArray(true));
});

// Get list of jobs.
$app->get('/-/vaults/:vaultName/jobs', function ($vaultName) {
  $vault = getVault($vaultName);
  $jobs = [];

  foreach ($vault->getJobs() as $job) {
    $jobs[] = $job->serializeArray();
  }

  $res = [ 'JobList' => $jobs ];
  response($res);
});

// Finalize multipart upload.
$app->post('/-/vaults/:vaultName/multipart-uploads/:multipartID', function ($vaultName, $multipartID) {
  $m = getMultipart($vaultName, $multipartID);

  $treeHash = $_SERVER['HTTP_X_AMZ_SHA256_TREE_HASH'];
  $archiveSize = $_SERVER['HTTP_X_AMZ_ARCHIVE_SIZE'];

  if (($a = $m->finalize($archiveSize, $treeHash)) === false) {
    badRequest('finalize failed');
  }

  header("x-amz-archive-id: {$a->id}");

  return201();
});

// Upload multipart part
$app->put('/-/vaults/:vaultName/multipart-uploads/:multipartID', function ($vaultName, $multipartID) {
  $m = getMultipart($vaultName, $multipartID);

  $contentHash = $_SERVER['HTTP_X_AMZ_CONTENT_SHA256'];
  $treeHash = $_SERVER['HTTP_X_AMZ_SHA256_TREE_HASH'];
  $contentRange = $_SERVER['HTTP_CONTENT_RANGE'];
  $contentLength = $_SERVER['CONTENT_LENGTH'];

  // 'bytes 0-1048575/*'
  if (!preg_match('@(\d+)-(\d+)@', $contentRange, $match)) {
    badRequest('invalid range');
  }
  $rangeFrom = $match[1];
  $rangeTo = $match[2];

  $putData = file_get_contents('php://input');
  $actualContentLength = strlen($putData);
  if ($actualContentLength != $contentLength) {
    badRequest("invalid content length (expected: $contentLength actual: $actualContentLength)");
  }

  $hash = TreeHash::fromContent($putData);
  $actualTreeHash = $hash->getHash();

  if ($treeHash !== $actualTreeHash) {
    badRequest('tree hash mismatch');
  }

  if (isset($GLOBALS['config']['uploadThrottle'])) {
    $GLOBALS['config']['uploadThrottle']();
  }
  if (!$m->putPart($rangeFrom, $rangeTo, $contentLength, $putData, $treeHash)) {
    badRequest('putPart failed');
  }

  header("x-amz-sha256-tree-hash: $treeHash");
  return201();
});

// Initiate multipart upload
$app->post('/-/vaults/:vaultName/multipart-uploads', function ($vaultName) {
  if (empty($_SERVER['HTTP_X_AMZ_PART_SIZE'])) {
    badRequest("part size missing");
  }

  $vault = getVault($vaultName);
  $partSize = $_SERVER['HTTP_X_AMZ_PART_SIZE'];

  $desc = '';
  if (!empty($_SERVER['HTTP_X_AMZ_ARCHIVE_DESCRIPTION'])) {
    $desc = $_SERVER['HTTP_X_AMZ_ARCHIVE_DESCRIPTION'];
  }

  $m = $vault->createMultipart($partSize, $desc);

  header("x-amz-multipart-upload-id: {$m->id}");
  return201();
});

// Get list of multipart upload parts
$app->get('/-/vaults/:vaultName/multipart-uploads/:multipartID', function ($vaultName, $multipartID) {
  $m = getMultipart($vaultName, $multipartID);
  response($m->serializeArray(true));
});

// Get list of multipart uploads
$app->get('/-/vaults/:vaultName/multipart-uploads', function ($vaultName) {
  $vault = getVault($vaultName);
  $mu = [];

  foreach ($vault->getMultiparts() as $m) {
    $mu[] = $m->serializeArray();
  }

  $res = [ 'UploadsList' => $mu ];
  response($res);
});

// Describe Vault.
$app->get('/-/vaults/:vaultName', function ($vaultName) {
  if ($v = Vault::get($vaultName)) {
    response($v->serializeArray(), 200);
  }
  notFound();
});

// Delete vault.
$app->delete('/-/vaults/:vaultName', function ($vaultName) {
  if ($v = Vault::get($vaultName)) {
    $v->delete();
  }
  return200();
});

// Get list of vaults.
$app->get('/-/vaults', function () {
  $ret = [];
  foreach (Vault::all() as $vault) {
    $ret[] = $vault->serializeArray();
  }
  $res = [ 'VaultList' => $ret ];
  response($res);
});

// Create new vault.
$app->put('/-/vaults/:vaultName', function ($vaultName) {
  if (!Vault::create($vaultName)) {
    return500('could not create vault');
  }

  return201();
});

$app->run();
badRequest();

function badRequest($msg = "") {
  $method = $GLOBALS['request']->method;
  $uri = $GLOBALS['request']->uri;
  $GLOBALS['app']->response->setStatus(400);
  error_log("BAD REQUEST method:$method uri:$uri");
  error_log(var_export("MSG: $msg", true));
  exit();
}

function response($arr, $status = 200) {
  header('Content-type: application/json');
  echo json_encode($arr, JSON_PRETTY_PRINT);
  exit();
}

function notFound() {
  $GLOBALS['app']->response->setStatus(404);
  exit();
}

function return204() {
  $GLOBALS['app']->response->setStatus(204);
  exit();
}

function return201() {
  $GLOBALS['app']->response->setStatus(201);
  exit();
}

function return200() {
  exit();
}

function return500() {
  $GLOBALS['app']->response->setStatus(500);
  exit();
}

function getVault($vaultName) {
  if (($vault = Vault::get($vaultName)) === false) {
    notFound();
  }

  return $vault;
}

function getMultipart($vaultName, $multipartID) {
  $vault = getVault($vaultName);
  if (($m = $vault->getMultipart($multipartID)) === false) {
    notFound();
  }
  return $m;
}

function getJob($vaultName, $jobID) {
  $vault = getVault($vaultName);
  if (($job = $vault->getJob($jobID)) === false) {
    notFound();
  }
  return $job;
}

