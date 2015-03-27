<?php

namespace Gzfextra\Stdlib\Math;

/**
 * HexConvert, 16进制, 32进制, 64进制转换
 *
 * @author  moln.xie@gmail.com
 */
class HexConvert
{
    private static $arr16 = array(
        '0000', '0001', '0010', '0011', '0100', '0101', '0110', '0111', '1000', '1001', '1010', '1011', '1100', '1101',
        '1110', '1111'
    );
    private static $arr32 = array(
        '00000', '00001', '00010', '00011', '00100', '00101', '00110', '00111', '01000', '01001', '01010', '01011',
        '01100', '01101', '01110', '01111', '10000', '10001', '10010', '10011', '10100', '10101', '10110', '10111',
        '11000', '11001', '11010', '11011', '11100', '11101', '11110', '11111'
    );
    private static $arr64 = array(
        '000000', '000001', '000010', '000011', '000100', '000101', '000110', '000111', '001000', '001001', '001010',
        '001011', '001100', '001101', '001110', '001111', '010000', '010001', '010010', '010011', '010100', '010101',
        '010110', '010111', '011000', '011001', '011010', '011011', '011100', '011101', '011110', '011111', '100000',
        '100001', '100010', '100011', '100100', '100101', '100110', '100111', '101000', '101001', '101010', '101011',
        '101100', '101101', '101110', '101111', '110000', '110001', '110010', '110011', '110100', '110101', '110110',
        '110111', '111000', '111001', '111010', '111011', '111100', '111101', '111110', '111111'
    );
    private static $chars = array(
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
        'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '!', '@'
    );

    const TYPE16 = 16;
    const TYPE32 = 32;
    const TYPE64 = 64;

    public static function hexc32($hex)
    {
        return self::convert(strtolower($hex), self::TYPE16, self::TYPE32);
    }

    public static function hexc64($hex)
    {
        return self::convert(strtolower($hex), self::TYPE16, self::TYPE64);
    }

    public static function c32hex($char32)
    {
        return self::convert(strtolower($char32), self::TYPE32, self::TYPE16);
    }

    public static function c64hex($char64)
    {
        return self::convert($char64, self::TYPE64, self::TYPE16);
    }

    /**
     * @param $string
     * @param $from
     * @param $to
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function convert($string, $from, $to)
    {
        $result = '';
        $s      = '';
        $a      = '';
        switch ($from) {
            case self::TYPE16 :
                $arr = self::$arr16;
                break;
            case self::TYPE32 :
                $arr = self::$arr32;
                break;
            case self::TYPE64 :
                $arr = self::$arr64;
                break;
            default:
                throw new \InvalidArgumentException('Convert(' . $from . ') error.');
        }
        switch ($to) {
            case self::TYPE16 :
                $arrt = self::$arr16;
                $b    = 4;
                break;
            case self::TYPE32 :
                $arrt = self::$arr32;
                $b    = 5;
                break;
            case self::TYPE64 :
                $arrt = self::$arr64;
                $b    = 6;
                break;
            default:
                throw new \InvalidArgumentException('Convert(' . $to . ') error.');
        }
        for ($i = 0; $i < strlen($string); $i++) {
            $index = array_search($string[$i], self::$chars);
            if ($index == -1) {
                throw new \InvalidArgumentException('Convert(' . $from . ') error:' . $string);
            }
            $s .= $arr[$index];
        }
        for ($i = 0; $i < $b - strlen($s) % $b; $i++) {
            $a .= '0';
        }
        $s = $a . $s;
        for ($i = 0; $i < strlen($s); $i += $b) {
            $result .= self::$chars[array_search(substr($s, $i, $b), $arrt)];
        }
        for ($i = 0; $i < strlen($result); $i++) {
            if ($result[$i] != '0') {
                return substr($result, $i);
            }
        }
        return 0;
    }
}