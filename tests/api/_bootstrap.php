<?php

$config = include(__DIR__ . '/../../config.php');
$GLOBALS['storePath'] = $config['storePath'] . '/UNITTEST/';

class MyApiTester extends ApiTester {

  public function haveAuth() {
    $this->haveHttpHeader('Authorization', "AWS4-HMAC-SHA256 Credential=UNITTEST/20160312/localhost/glacier/aws4_request, SignedHeaders=host;x-amz-glacier-version, Signature=0000000000000000000000000000000000000000000000000000000000000000");
  }

}

