<?php

$app->get('/', function () { echo 'gsandbox'; });

// Reset Sandbox data.
$app->get('/sandbox/reset/{accessID}', \Gsandbox\Action\ResetSandboxAction::class);

$app->group('/-', function () use ($app) {

    /********************/
    /* Vault Operations */
    /********************/

    // Create vault.
    $app->put('/vaults/{vaultName}', \Gsandbox\Action\CreateVaultAction::class);

    // List vaults.
    $app->get('/vaults', \Gsandbox\Action\ListVaultsAction::class);

    // Delete vault.
    $app->delete('/vaults/{vaultName}', \Gsandbox\Action\DeleteVaultAction::class);

    // Describe Vault.
    $app->get('/vaults/{vaultName}', \Gsandbox\Action\DescribeVaultAction::class);

    // Add/remove tags.
    $app->post('/vaults/{vaultName}/tags', \Gsandbox\Action\SetTagsAction::class);

    // List tags.
    $app->get('/vaults/{vaultName}/tags', \Gsandbox\Action\ListTagsAction::class);

    /******************/
    /* Job Operations */
    /******************/

    // Initiate job.
    $app->post('/vaults/{vaultName}/jobs', \Gsandbox\Action\InitiateJobAction::class);

    // List jobs.
    $app->get('/vaults/{vaultName}/jobs', \Gsandbox\Action\ListJobsAction::class);

    // Describe job.
    $app->get('/vaults/{vaultName}/jobs/{jobID}', \Gsandbox\Action\DescribeJobAction::class);

    // Get job output.
    $app->get('/vaults/{vaultName}/jobs/{jobID}/output', \Gsandbox\Action\GetJobOutputAction::class);

    // For tests: Force completion.
    $app->post('/vaults/{vaultName}/jobs/{jobID}/force-complete', \Gsandbox\Action\ForceJobCompletionAction::class);

    /**********************/
    /* Archive Operations */
    /**********************/

    // Upload archive.
    $app->post('/vaults/{vaultName}/archives', \Gsandbox\Action\UploadArchiveAction::class);

    // Delete archive.
    $app->delete('/vaults/{vaultName}/archives/{archiveID}', \Gsandbox\Action\DeleteArchiveAction::class);

    /*******************************/
    /* Multipart Upload Operations */
    /*******************************/

    // Initiate multipart upload.
    $app->post('/vaults/{vaultName}/multipart-uploads', \Gsandbox\Action\InitiateMultipartUploadAction::class);

    // List multipart uploads.
    $app->get('/vaults/{vaultName}/multipart-uploads', \Gsandbox\Action\ListMultipartUploadsAction::class);

    // Upload multipart part.
    $app->put('/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\UploadMultipartUploadPartAction::class);

    // List multipart upload parts.
    $app->get('/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\ListMultipartUploadPartsAction::class);

    // Finalize multipart upload.
    $app->post('/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\FinalizeMultipartUploadAction::class);

    // Abort multipart upload.
    $app->delete('/vaults/{vaultName}/multipart-uploads/{multipartID}', \Gsandbox\Action\DeleteMultipartUploadAction::class);

    /************************************/
    /* Data Retrieval Policy Operations */
    /************************************/

    // Get Data Retrieval Policy.
    $app->get('/policies/data-retrieval', \Gsandbox\Action\GetDataRetrievalPolicyAction::class);

    // Set Data Retrieval Policy.
    $app->put('/policies/data-retrieval', \Gsandbox\Action\SetDataRetrievalPolicyAction::class);

})->add(new \Gsandbox\Middleware\Auth());
