<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use MS\SerializerBundle\Serializer\Normalizer\DoctrineNormalizer;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\DoctrineNormalizer\DummyEmbeddable;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\DoctrineNormalizer\DummyEntity;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class DoctrineNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  DoctrineNormalizer */
    protected $normalizer;

    /** @var  ClassMetadataFactory */
    protected $metadataFactory;

    /** @var  object */
    protected $entity;

    public function setUp()
    {
        $this->metadataFactory = $this->getMock(ClassMetadataFactory::class);

        $this->metadataFactory
            ->method('hasMetadataFor')
            ->will(
                $this->returnValueMap([
                    [DummyEntity::class, true],
                    [DummyEmbeddable::class, true]
                ])
            );

        $this->metadataFactory
            ->method('getMetadataFor')
            ->will(
                $this->returnValueMap([
                    [DummyEntity::class, DummyEntity::getMetadata()],
                    [DummyEmbeddable::class, DummyEmbeddable::getMetadata()]
                ])
            );

        $em = $this->getMock(EntityManagerInterface::class);
        $em
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->entity = new DummyEntity();
        $this->entity->setId(1);
        $this->entity->setA('1a');
        $this->entity->setB('1b');
        $this->entity->setC('1c');
        $d = new DummyEmbeddable();
        $d->setX(1);
        $d->setY(2);
        $d->setZ(3);
        $this->entity->setD($d);

        $child = new DummyEntity();
        $child->setId(2);
        $child->setA('2a');
        $child->setB('2b');
        $child->setC('2c');
        $d = new DummyEmbeddable();
        $d->setX(21);
        $d->setY(22);
        $d->setZ(23);
        $child->setD($d);
        $this->entity->addChild($child);

        $child = new DummyEntity();
        $child->setId(3);
        $child->setA('3a');
        $child->setB('3b');
        $child->setC('3c');
        $d = new DummyEmbeddable();
        $d->setX(31);
        $d->setY(32);
        $d->setZ(33);
        $child->setD($d);
        $this->entity->addChild($child);

        $subchild = new DummyEntity();
        $subchild->setId(4);
        $subchild->setA('4a');
        $subchild->setB('4b');
        $subchild->setC('4c');
        $d = new DummyEmbeddable();
        $d->setX(41);
        $d->setY(42);
        $d->setZ(43);
        $subchild->setD($d);
        $child->addChild($subchild);


        $this->normalizer = new DoctrineNormalizer(null, null, null, $em);
        $propertyNormalizer = new PropertyNormalizer();
        $serializer = new Serializer([$this->normalizer, $propertyNormalizer]);
        $this->normalizer->setSerializer($serializer);
    }

    public function validData()
    {
        return [
            [
                [
                    '@class' => DummyEntity::class,
                    'id' => 1,
                    'a' => '1a',
                    'b' => '1b',
                    'c' => '1c',
                    'd' => [
                        '@class' => DummyEmbeddable::class,
                        'x' => 1,
                        'y' => 2,
                        'z' => 3,
                    ],
                    'parent' => null,
                    'children' => [
                        ['@class' => DummyEntity::class, 'id' => 2],
                        ['@class' => DummyEntity::class, 'id' => 3],
                    ],
                    '@references' => [
                        DummyEntity::class.'#{"id":2}' => [
                            '@class' => DummyEntity::class,
                            'id' => 2,
                            'a' => '2a',
                            'b' => '2b',
                            'c' => '2c',
                            'd' => [
                                '@class' => DummyEmbeddable::class,
                                'x' => 21,
                                'y' => 22,
                                'z' => 23,
                            ],
                            'parent' => ['@class' => DummyEntity::class, 'id' => 1],
                            'children' => [],
                        ],
                        DummyEntity::class.'#{"id":3}' => [
                            '@class' => DummyEntity::class,
                            'id' => 3,
                            'a' => '3a',
                            'b' => '3b',
                            'c' => '3c',
                            'd' => [
                                '@class' => DummyEmbeddable::class,
                                'x' => 31,
                                'y' => 32,
                                'z' => 33,
                            ],
                            'parent' => ['@class' => DummyEntity::class, 'id' => 1],
                            'children' => [
                                ['@class' => DummyEntity::class, 'id' => 4],
                            ],
                        ],
                        DummyEntity::class.'#{"id":4}' => [
                            '@class' => DummyEntity::class,
                            'id' => 4,
                            'a' => '4a',
                            'b' => '4b',
                            'c' => '4c',
                            'd' => [
                                '@class' => DummyEmbeddable::class,
                                'x' => 41,
                                'y' => 42,
                                'z' => 43,
                            ],
                            'parent' => ['@class' => DummyEntity::class, 'id' => 3],
                            'children' => [],
                        ],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider validData
     *
     * @param string $output
     * @param array  $context
     */
    public function testNormalization($output, $context)
    {
        $supports = $this->normalizer->supportsNormalization($this->entity);
        $this->assertTrue($supports);

        $actualOutput = $this->normalizer->normalize($this->entity, null, $context);
        print_r($actualOutput);
        $this->assertEquals($output, $actualOutput);
    }

    /**
     * @dataProvider validData
     *
     * @param string $input
     * @param array  $context
     */
    public function testDenormalization($input, $context)
    {
        $supports = $this->normalizer->supportsDenormalization($input, null);
        $this->assertTrue($supports);

        $actualOutput = $this->normalizer->denormalize($input, DummyEntity::class, null, $context);
        $this->assertEquals($this->entity, $actualOutput);
    }

    public function invalidData()
    {
        return [
        ];
    }

    /*
     * @dataProvider invalidData
     *
     * @param string $input
     * @param string $output
     * @param array  $context
     *
     * @expectedException \RuntimeException
     */
    /*public function testDenormalizationException($input, $output, $context)
    {
        $context = array_merge($context, ['params' => $this->params]);
        $this->normalizer->denormalize($input, null, null, $context);
    }*/
}
