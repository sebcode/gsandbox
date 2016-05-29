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

    public function limitExceededException()
    {
        return $this->withJson([
            'code' => 'LimitExceededException',
            'message' => 'The quota for the number of tags that can be assigned to this resource has been reached.',
            'type' => 'Client',
        ], 400, JSON_PRETTY_PRINT);
    }
}

