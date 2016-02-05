<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class GenericEncoderTest extends \PHPUnit_Framework_TestCase
{
    protected static $encoders = array(
        'bencode' => 'MS\SerializerBundle\Serializer\Encoder\BencodeEncoder',
        'bson' => 'MS\SerializerBundle\Serializer\Encoder\BsonEncoder',
        'cbor' => 'MS\SerializerBundle\Serializer\Encoder\CborEncoder',
        'export' => 'MS\SerializerBundle\Serializer\Encoder\ExportEncoder',
        'form' => 'MS\SerializerBundle\Serializer\Encoder\FormEncoder',
        'igbinary' => 'MS\SerializerBundle\Serializer\Encoder\IgbinaryEncoder',
        'ini' => 'MS\SerializerBundle\Serializer\Encoder\IniEncoder',
        'msgpack' => 'MS\SerializerBundle\Serializer\Encoder\MsgpackEncoder',
        'rison' => 'MS\SerializerBundle\Serializer\Encoder\RisonEncoder',
        'serialize' => 'MS\SerializerBundle\Serializer\Encoder\SerializeEncoder',
        'tnetstring' => 'MS\SerializerBundle\Serializer\Encoder\TnetstringEncoder',
        'ubjson' => 'MS\SerializerBundle\Serializer\Encoder\UbjsonEncoder',
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
            '_hashmap' => array('a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd', 'e' => 'e'),
            '_multi_map' => array(
                'a' => array('a' => array('a' => array('a' => 'a'))),
                'b' => array('b' => array('b' => array('b' => 'b'))),
                'c' => array('c' => array('c' => array('c' => 'c'))),
                'd' => array('d' => array('d' => array('d' => 'd'))),
                'e' => array('e' => array('e' => array('e' => 'e'))),
            ),
        );
        $cases['_big_map'] = $cases;

        foreach (static::$encoders as $format => $encoder) {
            foreach ($cases as $case) {
                if (($format === 'bson' or $format === 'form' or $format === 'ini') and !is_array($case)) {
                    continue;
                }

                if ($format === 'form' and (in_array(false, $case, true) or in_array(null, $case, true))) {
                    continue;
                }

                if ($format === 'bencode' and (is_float($case) or (is_array($case) and in_array(1.23, $case, true)))) {
                    continue;
                }

                $tests[] = array(
                    'encoder' => $encoder,
                    'format' => $format,
                    'expected' => $case,
                );

                if ($format === 'yaml') {
                    $tests[] = array(
                        'encoder' => $encoder,
                        'format' => $format,
                        'expected' => $case,
                        'construct' => false,
                    );
                }
            }
        }

        return $tests;
    }

    /**
     * @dataProvider dataProviderEncoders
     *
     * @param string $encoder
     * @param string $format
     * @param array  $expected
     * @param mixed  $construct
     */
    public function testEncoder($encoder, $format, $expected, $construct = null)
    {
        if (method_exists($encoder, 'isInstalled') and !$encoder::isInstalled()) {
            $this->markTestSkipped('Test skipped due to missing dependency.');

            return;
        }

        /* @var EncoderInterface|DecoderInterface $encoder */
        if ($construct !== null) {
            $encoder = new $encoder($construct);
        } else {
            $encoder = new $encoder();
        }

        $this->assertTrue($encoder->supportsEncoding($format));
        $string = (string) $encoder->encode($expected, $format);
        $this->assertTrue($encoder->supportsDecoding($format));
        $actual = $encoder->decode($string, $format);

        $this->assertEquals(
            $expected,
            $actual,
            sprintf(
                '"%s" (%s) failed to encode/decode a(n) %s identically.',
                $format,
                get_class($encoder),
                gettype($expected)
            ),
            0.0001
        );
    }
}
