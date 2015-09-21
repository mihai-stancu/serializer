<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class EncoderTest extends \PHPUnit_Framework_TestCase
{
    protected static $encoders = array(
        //'bencode' => 'MS\SerializerBundle\Serializer\Encoder\BencodeEncoder',
        'bson' => 'MS\SerializerBundle\Serializer\Encoder\BsonEncoder',
        'cbor' => 'MS\SerializerBundle\Serializer\Encoder\CborEncoder',
        'igbinary' => 'MS\SerializerBundle\Serializer\Encoder\IgbinaryEncoder',
        'msgpack' => 'MS\SerializerBundle\Serializer\Encoder\MsgpackEncoder',
        'php' => 'MS\SerializerBundle\Serializer\Encoder\PhpEncoder',
        //'sereal' => 'MS\SerializerBundle\Serializer\Encoder\SerealEncoder',
        'smile' => 'MS\SerializerBundle\Serializer\Encoder\SmileEncoder',
        'tnetstring' => 'MS\SerializerBundle\Serializer\Encoder\TNetstringEncoder',
        'ubjson' => 'MS\SerializerBundle\Serializer\Encoder\UbjsonEncoder',
        'var_export' => 'MS\SerializerBundle\Serializer\Encoder\VarexportEncoder',
        'yaml' => 'MS\SerializerBundle\Serializer\Encoder\YamlEncoder',
    );

    /**
     * @return array
     */
    public function dataProviderEncoders()
    {
        $tests = array();

        $cases = array(
            '_null' => null,
            '_true' => true,
            '_false' => false,
            '_int' => 1,
            '_float' => 1.23,
            '_string' => 'The quick brown fox jumps over the lazy dog.',
            '_text' => str_repeat('The quick brown fox jumps over the lazy dog.', 2000),
            '_vector' => array(1, 2, 3, 4, 5),
            '_map' => array('a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd', 'e' => 'e'),
        );

        foreach (static::$encoders as $protocol => $encoder) {
            foreach ($cases as $case) {
                $tests[] = array(
                    'encoder' => $encoder,
                    'format' => $protocol,
                    'expected' => $case,
                );
            }

            $tests[] = array(
                'encoder' => $encoder,
                'format' => $protocol,
                'expected' => $cases,
            );
        }

        return $tests;
    }

    /**
     * @dataProvider dataProviderEncoders
     *
     * @param string $encoder
     * @param string $format
     * @param array  $expected
     */
    public function testEncoder($encoder, $format, $expected)
    {
        if (method_exists($encoder, 'isInstalled') and !$encoder::isInstalled()) {
            $this->markTestSkipped('Test skipped due to missing dependency.');

            return;
        }

        if ($format === 'bencode' and is_float($expected)) {
            $this->markTestSkipped('Test skipped. Benode does not support encoding floats.');

            return;
        }

        /** @var EncoderInterface|DecoderInterface $encoder */
        $encoder = new $encoder();

        $string = (string) $encoder->encode($expected, $format);
        $actual = $encoder->decode($string, $format);

        if ($format === 'var_export') {
            //var_dump($string);
        }

        $this->assertEquals(
            $expected,
            $actual,
            $format.' ('.get_class($encoder).') '.
            'failed to encode/decode a(n) '.gettype($expected).' identically.'
             ."\n".print_r(array($expected, $string, $actual), true),
            0.0001
        );
    }
}
