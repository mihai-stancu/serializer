<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use MS\SerializerBundle\Serializer\Normalizer\MixedDenormalizer;
use MS\SerializerBundle\Serializer\Normalizer\ParamsDenormalizer;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\ParamsDenormalizer\DummyObject;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\ParamsDenormalizer\DummyService;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class ParamsDenormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ParamsDenormalizer */
    protected $normalizer;

    /** @var  \ReflectionMethod */
    protected $method;

    /** @var  \ReflectionParameter[] */
    protected $params;

    public function setUp()
    {
        $class = DummyService::class;
        $method = 'dummyMethod';
        $this->method = new \ReflectionMethod($class, $method);
        $this->params = $this->method->getParameters();

        $this->normalizer = new ParamsDenormalizer();
        $propertyNormalizer = new PropertyNormalizer();
        $mixedDenormalizer = new MixedDenormalizer();
        $serializer = new Serializer([$this->normalizer, $propertyNormalizer, $mixedDenormalizer]);
        $this->normalizer->setSerializer($serializer);
    }

    public function validData()
    {
        return [
            [
                [['a' => 1, 'b' => 2], null],
                [new DummyObject(1, 2), null, 99],
                ['indexBy' => 'position'],
            ],
            [
                [['a' => 1, 'b' => 2], null, 99],
                [new DummyObject(1, 2), null, 99],
                ['indexBy' => 'position'],
            ],
            [
                ['k' => ['a' => 1, 'b' => 2], 'l' => null, 'm' => 99],
                ['k' => new DummyObject(1, 2), 'l' => null, 'm' => 99],
                ['indexBy' => 'name'],
            ],
        ];
    }

    /**
     * @dataProvider validData
     *
     * @param array|array[]  $input
     * @param array|object[] $output
     */
    public function testDenormalization($input, $output, $context)
    {
        $supports = $this->normalizer->supportsDenormalization($input, ParamsDenormalizer::TYPE);
        $this->assertTrue($supports);

        $context = array_merge($context, ['params' => $this->params]);
        $actualOutput = $this->normalizer->denormalize($input, ParamsDenormalizer::TYPE, null, $context);
        $this->assertEquals($output, $actualOutput);
    }

    public function invalidData()
    {
        return [
            [
                [['a' => 1, 'b' => 2]],
                [new DummyObject(1, 2), null, 99],
                ['indexBy' => 'position'],
            ],
        ];
    }

    /**
     * @dataProvider invalidData
     *
     * @param string $input
     * @param string $output
     * @param array  $context
     *
     * @expectedException \RuntimeException
     */
    public function testDenormalizationException($input, $output, $context)
    {
        $context = array_merge($context, ['params' => $this->params]);
        $this->normalizer->denormalize($input, ParamsDenormalizer::TYPE, null, $context);
    }
}
