<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory as EntityMetadataFactory;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerMetadataFactory;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DoctrineCollectionNormalizer extends TypehintNormalizer
{
    const FORMAT = '@field';

    /** @var  EntityMetadataFactory */
    protected $entityMetadataFactory;

    /**
     * @param SerializerMetadataFactory|null $serializerMetadataFactory
     * @param NameConverterInterface|null    $nameConverter
     * @param PropertyInfoExtractor|null     $propertyInfoExtractor
     * @param EntityManagerInterface|null    $entityManager
     */
    public function __construct(
        SerializerMetadataFactory $serializerMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyInfoExtractor $propertyInfoExtractor = null,
        EntityManagerInterface $entityManager = null
    ) {
        parent::__construct($serializerMetadataFactory, $nameConverter, $propertyInfoExtractor);

        $this->entityMetadataFactory = $entityManager->getMetadataFactory();
    }

    /**
     * @param object $collection
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    public function normalize($collection, $format = null, array $context = array())
    {
        if (!$collection->isInitialized()) {
            return array();
        }

        $data = array();
        foreach ($collection as $i => $object) {
            $class = get_class($object);
            /** @var ClassMetadata $meta */
            $meta = $this->entityMetadataFactory->getMetadataFor($class);

            $hint = $this->getHintFromObject($object);
            $ids = $this->getIdentifierValues($meta, $object);
            $id = $this->getIdentifiersAsString($ids);

            if (!isset($context['@associations'][current($hint)][$id])) {
                $context['@associations'][current($hint)][$id] = $this->serializer->normalize(
                    $collection,
                    $format,
                    $context
                );
            }

            $data[$i] = array_merge($hint, $ids);
        }

        return $data;
    }

    /**
     * @param mixed  $data
     * @param string $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        if ($format !== static::FORMAT) {
            return false;
        }

        if (/*!is_array($data) and !($data instanceof \Traversable) and*/ !($data instanceof Collection)) {
            return false;
        }

        return true;
    }

    /**
     * @param array  $data
     * @param string $type
     * @param null   $format
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($format === static::FORMAT and $class = $this->getClassFromArray($data)
        and $this->entityMetadataFactory->hasMetadataFor($class)) {
            return parent::supportsDenormalization($data, $type, $format);
        }

        return true;
    }

    /**
     * @param array $ids
     *
     * @return string
     */
    protected function getIdentifiersAsString(array $ids = array())
    {
        $id = '';
        foreach ($ids as $field => $value) {
            $id = '/'.$field.':'.$value;
        }
        $id = ltrim($id, '/');

        return $id;
    }

    /**
     * @param ClassMetadata $meta
     * @param object        $object
     *
     * @return array
     */
    protected function getIdentifierValues($meta, $object)
    {
        return array_map('strval', $meta->getIdentifierValues($object));
    }
}
