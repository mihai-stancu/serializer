<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory as EntityMetadataFactory;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerMetadataFactory;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DoctrineEntityNormalizer extends TypehintNormalizer
{
    const FORMAT = '@entity';

    /** @var  EntityMetadataFactory */
    protected $entityMetadataFactory;

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
     * @param object $object
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $class = get_class($object);
        $meta = $this->entityMetadataFactory->getMetadataFor($class);
        $hint = $this->getHintFromObject($object);
        $ids = $this->getIdentifierValues($meta, $object);
        $id = $this->getIdentifiersAsString($ids);

        $associations = array();
        $context['@associations'] = &$associations;
        $associations[current($hint)][$id] = false;

        $data = parent::normalize($object, DoctrineFieldNormalizer::FORMAT, $context);

        if (!isset($data['@associations'])) {
            $data['@associations'] = array();
        }
        if (!empty($context['@associations'])) {
            $data['@associations'] = array_merge_recursive($data['@associations'], $context['@associations']);
            $data['@associations'] = array_map('array_filter', $data['@associations']);
            $data['@associations'] = array_filter($data['@associations']);
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
        if ($format === DoctrineFieldNormalizer::FORMAT or !is_object($data)) {
            return false;
        }

        $class = get_class($data);
        if (!$this->entityMetadataFactory->hasMetadataFor($class)
        or $this->entityMetadataFactory->getMetadataFor($class)->isEmbeddedClass) {
            return false;
        }

        return parent::supportsNormalization($data, $format);
    }

    /**
     * @param array  $data
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $associationsByClass = $data['@associations'];
        unset($data['@associations']);
        $context['@associations'] = &$associationsByClass;

        foreach ($associationsByClass as $associationClass => &$associationsById) {
            foreach ($associationsById as &$association) {
                $association = parent::denormalize($association, $associationClass, $format, $context);
            }
        }

        return $this->serializer->denormalize($data, $class, DoctrineFieldNormalizer::FORMAT, $context);
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
        var_dump(__METHOD__.' '.__LINE__.' '.$type.' '.$format);
        if (empty($data['@associations'])) {
            return false;
        }

        var_dump(__METHOD__.' '.__LINE__.' '.$type.' '.$format);
        $class = $this->getClassFromArray($data);
        if (!$this->entityMetadataFactory->hasMetadataFor($class)
        or $this->entityMetadataFactory->getMetadataFor($class)->isEmbeddedClass) {
            return false;
        }

        var_dump(__METHOD__.' '.__LINE__.' '.$type.' '.$format);

        return parent::supportsDenormalization($data, $type, $format);
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
