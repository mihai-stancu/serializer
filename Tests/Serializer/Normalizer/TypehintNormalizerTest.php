<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use MS\SerializerBundle\Serializer\Normalizer\TypehintNormalizer;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\RecursiveNormalizerClassDummy;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\RecursiveNormalizerSubclassDummy;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\TypehintNormalizerClassDummy;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\TypehintNormalizerSubclassDummy;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class TypehintNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  NormalizerInterface|DenormalizerInterface */
    protected $normalizer;

    public function setUp()
    {
        $propertyInfoExtractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
        $this->normalizer = new TypehintNormalizer(null, null, $propertyInfoExtractor);
        $serializer = new Serializer([$this->normalizer]);
        $this->normalizer->setSerializer($serializer);
    }

    protected function getDummy()
    {
        $subdummyX = new RecursiveNormalizerSubclassDummy();
        $subdummyX->setA('xa');
        $subdummyX->setB('xb');

        $subdummyY = new TypehintNormalizerSubclassDummy();
        $subdummyY->setA('ya');
        $subdummyY->setB('yb');

        $dummy = new TypehintNormalizerClassDummy();
        $dummy->setX($subdummyX);
        $dummy->setY($subdummyY);

        return $dummy;
    }

    public function testNormalizationWithClass()
    {
        $dummy = $this->getDummy();

        $expected = array(
            '@class' => TypehintNormalizerClassDummy::class,
            'x' => array(
                '@class' => RecursiveNormalizerSubclassDummy::class,
                'a' => 'xa',
                'b' => 'xb',
            ),
            'y' => array(
                '@class' => TypehintNormalizerSubclassDummy::class,
                'a' => 'ya',
                'b' => 'yb',
            ),
        );
        $this->assertTrue($this->normalizer->supportsNormalization($dummy));
        $actual = $this->normalizer->normalize($dummy, null);

        $this->assertEquals($expected, $actual);
    }

    public function testDenormalizationWithClass()
    {
        $dummy = $this->getDummy();
        $expected = $dummy;

        $normalized = $this->normalizer->normalize($dummy);
        $this->assertTrue($this->normalizer->supportsDenormalization($normalized, TypehintNormalizerClassDummy::class));
        $actual = $this->normalizer->denormalize($normalized, TypehintNormalizerClassDummy::class);

        $this->assertEquals($expected, $actual);
    }

    public function testNormalizationWithType()
    {
        $dummy = $this->getDummy();
        $this->normalizer->addType('dummy', TypehintNormalizerClassDummy::class);
        $this->normalizer->addType('subdummy', TypehintNormalizerSubclassDummy::class);

        $expected = array(
            '@type' => 'dummy',
            'x' => array(
                '@class' => RecursiveNormalizerSubclassDummy::class,
                'a' => 'xa',
                'b' => 'xb',
            ),
            'y' => array(
                '@type' => 'subdummy',
                'a' => 'ya',
                'b' => 'yb',
            ),
        );
        $this->assertTrue($this->normalizer->supportsNormalization($dummy));
        $actual = $this->normalizer->normalize($dummy, null);

        $this->assertEquals($expected, $actual);
    }

    public function testDenormalizationWithType()
    {
        $dummy = $this->getDummy();
        $this->normalizer->addType('dummy', TypehintNormalizerClassDummy::class);
        $this->normalizer->addType('subdummy', TypehintNormalizerSubclassDummy::class);

        $expected = $dummy;
        $normalized = $this->normalizer->normalize($dummy);
        $this->assertTrue($this->normalizer->supportsDenormalization($normalized, RecursiveNormalizerClassDummy::class));
        $actual = $this->normalizer->denormalize($normalized, TypehintNormalizerClassDummy::class);

        $this->assertEquals($expected, $actual);
    }
}
