<?php

namespace Gsandbox;

class Response extends \Slim\Http\Response
{
    public function resourceNotFoundException()
    {
        return $this->withJson([
            'code' => 'ResourceNotFoundException',
            'message' => 'ResourceNotFoundException.',
            'type' => 'Client',
        ], 404, JSON_PRETTY_PRINT);
    }

    public function policyEnforcedException()
    {
        return $this->withJson([
            'code' => 'PolicyEnforcedException',
            'message' => 'InitiateJob request denied by current data retrieval policy.',
            'type' => 'Client',
        ], 400, JSON_PRETTY_PRINT);
    }

    public function throttlingException()
    {
        return $this->withJson([
            'code' => 'ThrottlingException',
            'message' => '...',
            'type' => 'Client',
        ], 400, JSON_PRETTY_PRINT);
    }

    public function invalidHashException($computedHash, $expectedHash)
    {
        return $this->withJson([
            'code' => 'InvalidSignatureException',
            'message' => "The value passed in as x-amz-content-sha256 does not match the computed payload hash. Computed digest: $computedHash expected hash: $expectedHash",
            'type' => 'Client',
        ], 403, JSON_PRETTY_PRINT);
    }

    public function limitExceededException()
    {
        return $this->withJson([
            'code' => 'LimitExceededException',
            'message' => 'The quota for the number of tags that can be assigned to this resource has been reached.',
            'type' => 'Client',
        ], 400, JSON_PRETTY_PRINT);
    }

    public function uploadIdNotFoundException($id)
    {
        return $this->withJson([
            'code' => 'ResourceNotFoundException',
            'message' => "The upload ID was not found: $id",
            'type' => 'Client',
        ], 404, JSON_PRETTY_PRINT);
    }
}

