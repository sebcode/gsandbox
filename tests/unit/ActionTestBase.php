<?php

class ActionTestBase extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir = '';

    protected function setUp()
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('dir-') . '/';
        $GLOBALS['vaultStorePath'] = $this->tmpDir . 'vaults/UNITTEST/';
        mkdir($this->tmpDir . '', 0777, true);
    }

    protected function tearDown()
    {
        self::deleteDir($this->tmpDir);
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected function getRequest($method)
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        #$req->expects($this->once())->method('getMethod')->will($this->returnValue($method));
        return $req;
    }

    protected static function deleteDir($dir) {
        if (empty($dir) || $dir == '/') {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}

