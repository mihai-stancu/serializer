<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use MS\SerializerBundle\Serializer\Normalizer\RecursiveNormalizer;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\RecursiveNormalizerClassDummy;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\RecursiveNormalizerSubclassDummy;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class RecursiveNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  NormalizerInterface|DenormalizerInterface */
    protected $normalizer;

    public function setUp()
    {
        $propertyInfoExtractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
        $this->normalizer = new RecursiveNormalizer(null, null, $propertyInfoExtractor);
        $serializer = new Serializer([$this->normalizer]);
        $this->normalizer->setSerializer($serializer);
    }

    protected function getDummy()
    {
        $subdummy = new RecursiveNormalizerSubclassDummy();
        $subdummy->setA('a');
        $subdummy->setB('b');

        $dummy = new RecursiveNormalizerClassDummy();
        $dummy->setX($subdummy);

        return $dummy;
    }

    public function testNormalization()
    {
        $dummy = $this->getDummy();

        $expected = array(
            'x' => array(
                'a' => 'a',
                'b' => 'b',
            ),
        );
        $this->assertTrue($this->normalizer->supportsNormalization($dummy));
        $actual = $this->normalizer->normalize($dummy, null);

        $this->assertEquals($expected, $actual);
    }

    public function testDenormalization()
    {
        $dummy = $this->getDummy();
        $expected = $dummy;

        $normalized = $this->normalizer->normalize($dummy);
        $this->assertTrue($this->normalizer->supportsDenormalization($normalized, RecursiveNormalizerClassDummy::class));
        $actual = $this->normalizer->denormalize($normalized, RecursiveNormalizerClassDummy::class);

        $this->assertEquals($expected, $actual);
    }
}
