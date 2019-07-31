<?php

namespace TinyPixel\Acorn\CDN\Helpers;

use TinyPixel\Acorn\CDN\Helpers\Concerns\HashKeys;

/**
 * HashIt
 *
 * Based on PseudoCrypt by KevBurns
 * @see http://blog.kevburnsjr.com/php-unique-hash
 */
class HashIt
{
    use HashKeys;

    /**
     * Base 62
     *
     * @param  string int
     * @return string
     */
    public static function base62($int)
    {
        $key = "";

        while (bccomp($int, 0) > 0) {
            $mod = bcmod($int, 62);
            $key .= chr(self::$chars62[$mod]);
            $int = bcdiv($int, 62);
        }
        return strrev($key);
    }

    /**
     * Hash
     *
     * @param  int    $num
     * @param  int    $length
     * @return string
     */
    public static function hash($num, $length = 5)
    {
        $ceil = bcpow(62, $len);
        $primes = array_keys(self::$golden_primes);
        $prime = $primes[$length];
        $dec = bcmod(bcmul($num, $prime), $ceil);
        $hash = self::base62($dec);

        return str_pad($hash, $len, "0", STR_PAD_LEFT);
    }

    /**
     * Unbase 62
     *
     * @param  string $key
     * @return int
     */
    public static function unbase62($key)
    {
        $int = 0;

        foreach (str_split(strrev($key)) as $i => $char) {
            $dec = array_search(ord($char), self::$chars62);
            $int = bcadd(bcmul($dec, bcpow(62, $i)), $int);
        }

        return $int;
    }

    /**
     * Unhash
     *
     * @param  string $hash
     * @return string
     */
    public static function unhash($hash)
    {
        $len = strlen($hash);
        $ceil = bcpow(62, $len);
        $mmiprimes = array_values(self::$golden_primes);
        $mmi = $mmiprimes[$len];
        $num = self::unbase62($hash);
        $dec = bcmod(bcmul($num, $mmi), $ceil);

        return $dec;
    }
}
