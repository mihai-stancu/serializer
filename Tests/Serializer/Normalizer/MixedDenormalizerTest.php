<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use MS\SerializerBundle\Serializer\Normalizer\MixedDenormalizer;
use Symfony\Component\Serializer\Serializer;

class MixedDenormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  MixedDenormalizer */
    protected $normalizer;

    public function setUp()
    {
        $this->normalizer = new MixedDenormalizer();
        $serializer = new Serializer([$this->normalizer]);
        $this->normalizer->setSerializer($serializer);
    }

    public function validData()
    {
        return [
            [null],
            [null, null, false],
            [[null, true, false, 1, 1.1, 'string', fopen('data:,', 'r')]],
        ];
    }

    /**
     * @dataProvider validData
     *
     * @param array  $value
     * @param string $type
     * @param bool   $shouldSupport
     */
    public function testDenormalization($value, $type = MixedDenormalizer::TYPE, $shouldSupport = true)
    {
        $supports = $this->normalizer->supportsDenormalization($value, $type);

        if (!$shouldSupport) {
            $this->assertFalse($supports);

            return;
        }

        $this->assertTrue($supports);

        $actualValue = $this->normalizer->denormalize($value, $type);
        $this->assertEquals($value, $actualValue);
    }

    public function invalidData()
    {
        return [
            [[new \stdClass()], MixedDenormalizer::TYPE],
        ];
    }

    /**
     * @dataProvider invalidData
     *
     * @param string $value
     * @param string $type
     *
     * @expectedException \InvalidArgumentException
     */
    public function testDenormalizationException($value, $type)
    {
        $supports = $this->normalizer->supportsDenormalization($value, $type);
        if ($supports) {
            $this->normalizer->denormalize($value, $type);
        }
    }
}
