<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use MS\SerializerBundle\Serializer\Normalizer\StreamNormalizer;

class StreamNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  StreamNormalizer */
    protected $normalizer;

    public function setUp()
    {
        $this->normalizer = new StreamNormalizer();
    }

    public function validData()
    {
        return [
            [
                "The quick brown fox\xB3",
                'data:text/plain,The+quick+brown+fox%B3',
                ['binary_mime' => 'text/plain'],
            ],
            [
                "The quick brown fox\xB3",
                'data:text/plain,The+quick+brown+fox%B3',
                ['binary_mime' => 'text/plain'],
            ],
            [
                "The quick brown fox\xB3",
                'data:text/plain;base64,VGhlIHF1aWNrIGJyb3duIGZveLM=',
                ['binary_mime' => 'text/plain', 'binary_base64' => true],
            ],
        ];
    }

    /**
     * @dataProvider validData
     *
     * @param string $dataUri
     * @param array  $context
     */
    public function testNormalization($string, $dataUri, $context)
    {
        $stream = fopen('data:,'.$string, 'r');

        $supports = $this->normalizer->supportsNormalization($stream);
        $this->assertTrue($supports);

        $actualDataUri = $this->normalizer->normalize($stream, null, $context);
        $this->assertEquals($dataUri, $actualDataUri);
    }

    /**
     * @dataProvider validData
     *
     * @param string $dataUri
     * @param array  $context
     */
    public function testDenormalization($string, $dataUri, $context)
    {
        $stream = fopen($dataUri, 'r');

        $supports = $this->normalizer->supportsDenormalization($dataUri, StreamNormalizer::TYPE);
        $this->assertTrue($supports);

        $actualStream = $this->normalizer->denormalize($dataUri, StreamNormalizer::TYPE, null, $context);
        $this->assertEquals(stream_get_contents($stream), stream_get_contents($actualStream));
    }
}
