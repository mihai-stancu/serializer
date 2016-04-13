<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\DoctrineNormalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadata;

class DummyEntity
{
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return ClassMetadata
     */
    public static function getMetadata()
    {
        $metadata = new ClassMetadata(static::class);

        $metadata->setIdentifier(['id']);

        $metadata->mapField(['fieldName' => 'id']);
        $metadata->mapField(['fieldName' => 'a']);
        $metadata->mapField(['fieldName' => 'b']);
        $metadata->mapField(['fieldName' => 'c']);
        $metadata->mapEmbedded([
                'fieldName' => 'd',
                'class' => DummyEmbeddable::class,
                'columnPrefix' => 'd',
        ]);
        $metadata->mapManyToOne([
            'fieldName' => 'parent',
            'targetEntity' => self::class,
            'inversedBy' => 'children',
        ]);
        $metadata->mapOneToMany([
            'fieldName' => 'children',
            'targetEntity' => self::class,
            'mappedBy' => 'parent',
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());

        return $metadata;
    }

    /**
     * @var int
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @var string
     */
    protected $a;

    /**
     * @return string
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @param string $a
     */
    public function setA($a)
    {
        $this->a = $a;
    }

    /**
     * @var string
     */
    protected $b;

    /**
     * @return string
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @param string $b
     */
    public function setB($b)
    {
        $this->b = $b;
    }

    /**
     * @var string
     */
    protected $c;

    /**
     * @return string
     */
    public function getC()
    {
        return $this->c;
    }

    /**
     * @param string $c
     */
    public function setC($c)
    {
        $this->c = $c;
    }

    /**
     * @var DummyEmbeddable
     */
    protected $d;

    /**
     * @return DummyEmbeddable
     */
    public function getD()
    {
        return $this->d;
    }

    /**
     * @param DummyEmbeddable $d
     */
    public function setD($d)
    {
        $this->d = $d;
    }

    /**
     * @var DummyEntity
     */
    protected $parent;

    /**
     * @return DummyEntity
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param DummyEntity $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @var ArrayCollection|DummyEntity[]
     */
    protected $children;

    /**
     * @return ArrayCollection|DummyEntity[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param ArrayCollection|DummyEntity[] $children
     */
    public function setChildren($children)
    {
        if (is_array($children)) {
            $this->children = new ArrayCollection($children);

            return;
        }

        $this->children = $children;
    }

    /**
     * @param DummyEntity $child
     */
    public function addChild(self $child)
    {
        $this->children->add($child);
        $child->setParent($this);
    }
}
