<?php

namespace TinyPixel\Acorn\CDN\Helpers\Concerns;

trait HashKeys
{
    /**
     * Golden primes
     *
     * Key: next prime greater than 62^n / 1.61803398749894848
     * Value: modular multiplicative inverse
     *
     * @var array
     */
    private static $golden_primes = [
        '1'                  => '1',
        '41'                 => '59',
        '2377'               => '1677',
        '147299'             => '187507',
        '9132313'            => '5952585',
        '566201239'          => '643566407',
        '35104476161'        => '22071637057',
        '2176477521929'      => '294289236153',
        '134941606358731'    => '88879354792675',
        '8366379594239857'   => '7275288500431249',
        '518715534842869223' => '280042546585394647'
    ];

    private static $chars62 = [
        0 => 48,
        1 => 49,
        2 => 50,
        3 => 51,
        4 => 52,
        5 => 53,
        6 => 54,
        7 => 55,
        8 => 56,
        9 => 57,
        10 => 65,
        11 => 66,
        12 => 67,
        13 => 68,
        14 => 69,
        15 => 70,
        16 => 71,
        17 => 72,
        18 => 73,
        19 => 74,
        20 => 75,
        21 => 76,
        22 => 77,
        23 => 78,
        24 => 79,
        25 => 80,
        26 => 81,
        27 => 82,
        28 => 83,
        29 => 84,
        30 => 85,
        31 => 86,
        32 => 87,
        33 => 88,
        34 => 89,
        35 => 90,
        36 => 97,
        37 => 98,
        38 => 99,
        39 => 100,
        40 => 101,
        41 => 102,
        42 => 103,
        43 => 104,
        44 => 105,
        45 => 106,
        46 => 107,
        47 => 108,
        48 => 109,
        49 => 110,
        50 => 111,
        51 => 112,
        52 => 113,
        53 => 114,
        54 => 115,
        55 => 116,
        56 => 117,
        57 => 118,
        58 => 119,
        59 => 120,
        60 => 121,
        61 => 122,
    ];
}