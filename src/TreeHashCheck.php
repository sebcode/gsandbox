<?php

namespace Gsandbox;

class TreeHashCheck {

    public static function isTreeHashAligned($archiveSize, $startByte, $endByte) {
        $kb = 1024;
        $archiveSize = ceil($archiveSize / 1024 / 1024) * $kb;
        $startByte = $startByte / $kb;
        $endByte = (ceil(($endByte + 1) / 1024 / 1024) * $kb) - 1;

        $hashes = range(0, $archiveSize);
        while (count($hashes) > 1) {
            $sets = array_chunk($hashes, 2);
            $hashes = [];
            foreach ($sets as $set) {
                $nhash = (count($set) === 1)
                    ? [ $set[0] ]
                    : static::getb($set[0], $set[1]);
                if (static::eq($nhash, $startByte, $endByte)) {
                    return true;
                }
                $hashes[] = $nhash;
            }
        }

        return false;
    }

    private static function eq($nhash, $a, $b) {
        if (is_array($nhash) && isset($nhash[1])) {
            return $nhash[0] == $a && $nhash[1] == $b;
        }

        return $nhash == $a;
    }

    private static function getb($a, $b) {
        $a = static::getfirst($a);
        $b = static::getlast($b);
        return [ $a, $b ];
    }

    private static function getfirst($a) {
        if (is_array($a)) {
            return reset($a);
        }

        return $a;
    }

    private static function getlast($a) {
        if (is_array($a)) {
            return end($a);
        }

        return $a;
    }

    private static function isEven($n) {
        return $n % 2 === 0;
    }

}

