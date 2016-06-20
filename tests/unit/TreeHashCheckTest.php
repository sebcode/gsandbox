<?php

use Gsandbox\TreeHashCheck;

class TreeHashCheckTest extends \PHPUnit_Framework_TestCase
{
    public function testIsTreeHashAligned()
    {
        $mb = 1024 * 1024;

        $size = 10 * $mb;
        $this->assertTrue(TreeHashCheck::isTreeHashAligned($size, $mb * 0, ($mb * 1) - 1));
        $this->assertTrue(TreeHashCheck::isTreeHashAligned($size, $mb * 0, ($mb * 2) - 1));
        $this->assertFalse(TreeHashCheck::isTreeHashAligned($size, $mb * 0, ($mb * 3) - 1));
        $this->assertFalse(TreeHashCheck::isTreeHashAligned($size, $mb * 3, ($mb * 5) - 1));
        $this->assertFalse(TreeHashCheck::isTreeHashAligned($size, $mb * 4, ($mb * 7) - 1));

        $size = 10 * $mb;
        $this->assertTrue(TreeHashCheck::isTreeHashAligned($size, $mb * 6, ($mb * 7) - 1));

        $size = (6 * $mb) + ($mb / 2);
        $this->assertTrue(TreeHashCheck::isTreeHashAligned($size, $mb * 6, $size - 1));
        $this->assertFalse(TreeHashCheck::isTreeHashAligned($size, $mb * 7, ($mb * 8) - 1));

        $size = 100 * $mb;
        $this->assertTrue(TreeHashCheck::isTreeHashAligned($size, 16777216, 33554431));
    }
}
