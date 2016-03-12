<?php

include __DIR__ . '/../c3.php';

require_once(__DIR__ . '/../vendor/autoload.php');

$config = include(__DIR__ . '/../config.php');

$request = new Gsandbox\Request();

$app = new \Slim\App([
  'debug' => false,
  #'log.level' => \Slim\Log::DEBUG,
  #'log.enabled' => true,
]);

#$app->error(function (\Exception $e) use ($app) {
#  error_log(var_export($e->getMessage(), true));//XXX
#});

if ($request->accessKey === false) {
  $app->get('/', function () {
    echo 'gsandbox';
  });

  $app->get('/-/sandbox/reset/{accessID}', \Gsandbox\Action\ResetSandboxAction::class);

  $app->run();
  exit;
}

if (isset($GLOBALS['config']['responseDelay'])) {
  $GLOBALS['config']['responseDelay']();
}

$GLOBALS['config']['storePath'] .= $request->accessKey . '/';

// Get Data Retrieval Policy.
$app->get('/-/policies/data-retrieval', \Gsandbox\Action\GetDataRetrievalPolicyAction::class);

// Set Data Retrieval Policy.
$app->put('/-/policies/data-retrieval', \Gsandbox\Action\SetDataRetrievalPolicyAction::class);

// List tags.
$app->get('/-/vaults/{vaultName}/tags', \Gsandbox\Action\ListTagsAction::class);

// Add/remove tags.
$app->post('/-/vaults/{vaultName}/tags', \Gsandbox\Action\SetTagsAction::class);

// Delete archive.
$app->delete('/-/vaults/{vaultName}/archives/{archiveID}', \Gsandbox\Action\DeleteArchiveAction::class);

// Initiate job.
$app->post('/-/vaults/{vaultName}/jobs', \Gsandbox\Action\InitiateJobAction::class);

// Get job output.
$app->get('/-/vaults/{vaultName}/jobs/{jobID}/output', \Gsandbox\Action\GetJobOutputAction::class);

// Describe job.
$app->get('/-/vaults/{vaultName}/jobs/{jobID}', \Gsandbox\Action\DescribeJobAction::class);

// Get list of jobs.
$app->get('/-/vaults/{vaultName}/jobs', \Gsandbox\Action\ListJobsAction::class);

// Initiate multipart upload
$app->post('/-/vaults/{vaultName}/multipart-uploads', \Gsandbox\Action\InitiateMultipartUploadAction::class);

// Delete multipart upload.
$app->delete('/-/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\DeleteMultipartUploadAction::class);

// Get list of multipart uploads
$app->get('/-/vaults/{vaultName}/multipart-uploads', \Gsandbox\Action\ListMultipartUploadsAction::class);

// Upload multipart upload part
$app->put('/-/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\UploadMultipartUploadPartAction::class);

// Get list of multipart upload parts
$app->get('/-/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\ListMultipartUploadPartsAction::class);

// Finalize multipart upload.
$app->post('/-/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\FinalizeMultipartUploadAction::class);

// Create new vault.
$app->put('/-/vaults/{vaultName}', \Gsandbox\Action\CreateVaultAction::class);

// Get list of vaults.
$app->get('/-/vaults', \Gsandbox\Action\ListVaultsAction::class);

// Describe Vault.
$app->get('/-/vaults/{vaultName}', \Gsandbox\Action\DescribeVaultAction::class);

// Delete vault.
$app->delete('/-/vaults/{vaultName}', \Gsandbox\Action\DeleteVaultAction::class);

$app->run();

