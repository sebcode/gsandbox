<?php

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends \Codeception\Actor
{
    use _generated\ApiTesterActions;

    public function haveAuth() {
      $this->haveHttpHeader('Authorization', "AWS4-HMAC-SHA256 Credential=UNITTEST/20160312/localhost/glacier/aws4_request, SignedHeaders=host;x-amz-glacier-version, Signature=0000000000000000000000000000000000000000000000000000000000000000");
    }
}
