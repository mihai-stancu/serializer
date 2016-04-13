<?php

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use MS\SerializerBundle\Serializer\Normalizer\BinaryNormalizer;

class BinaryNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  BinaryNormalizer */
    protected $normalizer;

    public function setUp()
    {
        $this->normalizer = new BinaryNormalizer();
    }

    public function validData()
    {
        return [
            [
                "The quick brown fox\xB3",
                'data:,The+quick+brown+fox%B3',
                ['binary_mime' => '', 'binary_urlencode' => true],
            ],
            [
                "The quick brown fox\xB3",
                'data:;base64,VGhlIHF1aWNrIGJyb3duIGZveLM=',
                ['binary_mime' => '', 'binary_base64' => true],
            ],
            [
                "The quick brown fox\xB3",
                'data:text/plain,The+quick+brown+fox%B3',
                ['binary_mime' => 'text/plain'],
            ],
            [
                "The quick brown fox\xB3",
                'data:text/plain;charset=ASCII,The+quick+brown+fox%B3',
                ['binary_mime' => 'text/plain', 'binary_charset' => 'ASCII'],
            ],
            [
                "The quick brown fox\xB3",
                'data:application/octet-stream;base64,VGhlIHF1aWNrIGJyb3duIGZveLM=',
                ['binary_mime' => 'application/octet-stream'],
            ],
            [
                "The quick brown fox\xB3",
                'data:application/x-gzip;base64,H4sIAAAAAAAAAwvJSFUoLM1MzlZIKsovz1NIy6/YDAAct7bhFAAAAA==',
                ['binary_mime' => 'application/octet-stream', 'binary_gzip' => true],
            ],
        ];
    }

    /**
     * @dataProvider validData
     *
     * @param string $binary
     * @param string $dataUri
     * @param array  $context
     */
    public function testNormalization($binary, $dataUri, $context)
    {
        $supports = $this->normalizer->supportsNormalization($binary);
        $this->assertTrue($supports);

        $actualDataUri = $this->normalizer->normalize($binary, null, $context);
        $this->assertEquals($dataUri, $actualDataUri);
    }

    /**
     * @dataProvider validData
     *
     * @param string $binary
     * @param string $dataUri
     * @param array  $context
     */
    public function testDenormalization($binary, $dataUri, $context)
    {
        $supports = $this->normalizer->supportsDenormalization($dataUri, BinaryNormalizer::TYPE);
        $this->assertTrue($supports);

        $actualBinary = $this->normalizer->denormalize($dataUri, BinaryNormalizer::TYPE, null, $context);
        $this->assertEquals($binary, $actualBinary);
    }

    public function invalidData()
    {
        return [
            [
                "\0\0\0",
                'data:base64,AAAA',
                [],
            ],
            [
                "\0\0\0",
                'data:charset=ASCII;base64,AAAA',
                [],
            ],
            [
                "###",
                'data:,###',
                [],
            ],
        ];
    }

    /**
     * @dataProvider invalidData
     *
     * @param string $binary
     * @param string $dataUri
     * @param array  $context
     *
     * @expectedException \InvalidArgumentException
     */
    public function testDenormalizationException($binary, $dataUri, $context)
    {
        $this->normalizer->normalize($binary, null, $context);
        $this->normalizer->denormalize($dataUri, BinaryNormalizer::TYPE, null, $context);
    }
}