<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use MS\SerializerBundle\Serializer\Normalizer\DateTimeNormalizer;

class DateTimeNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  DateTimeNormalizer */
    protected $normalizer;

    public function setUp()
    {
        $this->normalizer = new DateTimeNormalizer();
    }

    public function validData()
    {
        return [
            [
                new \DateTime('2001-01-01 01:01:01'),
                '2001-01-01 01:01:01',
                ['datetime_format' => 'Y-m-d H:i:s'],
            ],
            [
                new \DateTime('2001-01-01T01:01:01'),
                '2001-01-01T01:01:01',
                ['datetime_format' => 'Y-m-d\TH:i:s'],
            ],
            [
                new \DateTime('2001-01-01T01:01:01+02:00'),
                '2001-01-01T01:01:01+02:00',
                ['datetime_format' => 'Y-m-d\TH:i:sP'],
            ],
            [
                new \DateTime('2001-01-01 01:01:01.000001'),
                '2001-01-01 01:01:01.000001',
                ['datetime_format' => 'Y-m-d H:i:s.u'],
            ],
            [
                new \DateTime('2001-01-01T01:01:01.000001'),
                '2001-01-01T01:01:01.000001',
                ['datetime_format' => 'Y-m-d\TH:i:s.u'],
            ],
            [
                new \DateTime('2001-01-01T01:01:01.000001+02:00'),
                '2001-01-01T01:01:01.000001+02:00',
                ['datetime_format' => 'Y-m-d\TH:i:s.uP'],
            ],
        ];
    }

    /**
     * @dataProvider validData
     *
     * @param \DateTime $dateTime
     * @param string    $string
     * @param array     $context
     */
    public function testNormalization($dateTime, $string, $context)
    {
        $supports = $this->normalizer->supportsNormalization($dateTime, null);
        $this->assertTrue($supports);

        $actualString = $this->normalizer->normalize($dateTime, null, $context);
        $this->assertEquals($string, $actualString);
    }

    /**
     * @dataProvider validData
     *
     * @param \DateTime $dateTime
     * @param string    $string
     * @param array     $context
     */
    public function testDenormalization($dateTime, $string, $context)
    {
        $supports = $this->normalizer->supportsDenormalization($string, DateTimeNormalizer::TYPE);
        $this->assertTrue($supports);

        $actualDateTime = $this->normalizer->denormalize($string, DateTimeNormalizer::TYPE, null, $context);
        $this->assertEquals($dateTime, $actualDateTime);
    }

    public function invalidData()
    {
        return [
            [
                new \stdClass(),
                '200-01-01T01:01:01.000001+02:00',
                ['datetime_format' => 'Y-m-d\TH:i:s.uP'],
            ],
            [
                new \DateTime('200-01-01T01:01:01.000001+02:00'),
                '200-01-01T01:01:01.000001+02:00',
                ['datetime_format' => 'Y-m-d\TH:i:s.uP'],
            ],
        ];
    }

    /**
     * @dataProvider invalidData
     *
     * @param \DateTime $dateTime
     * @param string    $string
     * @param array     $context
     *
     * @expectedException \InvalidArgumentException
     */
    public function testDenormalizationException($dateTime, $string, $context)
    {
        $this->normalizer->normalize($dateTime, DateTimeNormalizer::TYPE, $context);
        $this->normalizer->denormalize($string, DateTimeNormalizer::TYPE, null, $context);
    }
}
