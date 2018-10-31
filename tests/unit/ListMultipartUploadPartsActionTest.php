<?php

use Gsandbox\Action\ListMultipartUploadPartsAction;
use Gsandbox\Model\Vault;
use Gsandbox\Model\Multipart;
use Slim\Http\Response;

class ListMultipartUploadParts extends ActionTestBase
{
    public function testMultipart()
    {
        $mb = 1024*1024;
        $vault = Vault::create('testvault');
        $this->assertTrue($vault instanceof Vault);

        $multipart = $vault->createMultipart($mb, 'testing');
        $this->assertTrue($multipart instanceof Multipart);

        $action = new ListMultipartUploadPartsAction;

        $exp = [
            'MultipartUploadId' => $multipart->getId(),
            'CreationDate' => 'XXX',
            'ArchiveDescription' => 'testing',
            'PartSizeInBytes' => 1048576,
            'VaultARN' => 'FAKEARN/testvault',
            'Parts' => [],
            'Marker' => null,
        ];

        $res = $action->__invoke($this->getRequest('GET'), new Response(), [
            'vaultName' => 'testvault',
            'multipartID' => $multipart->getId(),
        ]);

        $this->assertEquals(200, $res->getStatusCode());
        $actual = json_decode($res->getBody(), true);
        $actual['CreationDate'] = 'XXX';
        $this->assertEquals($exp, $actual);

        /* Upload parts */

        $data = str_repeat('0', 1024 * 1024);
        $hash = '3388ef7e17c4724644a1d19168547542733273c2ae4aaefc76c8da35d0827e2e';

        for ($i = 0; $i < 10; $i++) {
            $startByte = $i * $mb;
            $endByte = $startByte + $mb - 1;

            $this->assertTrue(
                $multipart->putPart($startByte, $endByte, $mb, $data, $hash)
            );

            $exp['Parts'][] = [
              'RangeInBytes' => "$startByte-$endByte",
              'SHA256TreeHash' => $hash,
            ];
        }

        /* Test without limit */

        $env = \Slim\Http\Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'QUERY_STRING' => ''
        ]);
        $req = \Slim\Http\Request::createFromEnvironment($env);
        $res = $action->__invoke($req, new Response(), [
            'vaultName' => 'testvault',
            'multipartID' => $multipart->getId(),
        ]);
        $this->assertEquals(200, $res->getStatusCode());
        $actual = json_decode($res->getBody(), true);
        $this->assertEquals(10, count($actual['Parts']));
        $actual['CreationDate'] = 'XXX';
        $this->assertEquals($exp, $actual);

        /* Test with limit */

        $env = \Slim\Http\Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'QUERY_STRING' => 'limit=5&marker=0'
        ]);
        $req = \Slim\Http\Request::createFromEnvironment($env);
        $res = $action->__invoke($req, new Response(), [
            'vaultName' => 'testvault',
            'multipartID' => $multipart->getId(),
        ]);
        $this->assertEquals(200, $res->getStatusCode());
        $actual = json_decode($res->getBody(), true);
        $actual['CreationDate'] = 'XXX';
        $this->assertEquals(5, count($actual['Parts']));
        $exp['Parts'] = array_slice($exp['Parts'], 0, 5);
        $exp['Marker'] = '5';
        $this->assertEquals($exp, $actual);
    }
}
