<?php

namespace MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\DoctrineNormalizer;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadata;

class DummyEmbeddable
{
    /**
     * @return ClassMetadata
     */
    public static function getMetadata()
    {
        $metadata = new ClassMetadata(static::class);

        $metadata->mapField(['fieldName' => 'x']);
        $metadata->mapField(['fieldName' => 'y']);
        $metadata->mapField(['fieldName' => 'z']);

        $metadata->wakeupReflection(new RuntimeReflectionService());

        return $metadata;
    }

    protected $x;

    /**
     * @return mixed
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @param mixed $x
     */
    public function setX($x)
    {
        $this->x = $x;
    }

    protected $y;

    /**
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param mixed $y
     */
    public function setY($y)
    {
        $this->y = $y;
    }

    protected $z;

    /**
     * @return mixed
     */
    public function getZ()
    {
        return $this->z;
    }

    /**
     * @param mixed $z
     */
    public function setZ($z)
    {
        $this->z = $z;
    }
}
