<?php

namespace Gsandbox;

// http://stackoverflow.com/questions/37629472/tree-hash-how-to-verify-if-a-range-is-tree-hash-aligned

class TreeHashCheck
{
    private static function max_k($x) {
        if ($x % 2 == 0) {
            return 1 + static::max_k($x / 2);
        }

        return 0;
    }

    public static function isTreeHashAligned($archiveSize, $from, $to) {
        if ($to < $from) {
            return false;
        }

        if ($from % 2 == 1) {
            return $from == $to;
        }

        $ilen = $to - $from + 1;

        if (!((($ilen & ($ilen - 1)) == 0) && $ilen != 0)) {
            return false;
        }

        if ($from == 0) {
            return true;
        }

        $k = static::max_k($from);
        $i = log($ilen, 2);
        return $i <= $k;
    }
}

