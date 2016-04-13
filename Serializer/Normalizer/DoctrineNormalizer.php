<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory as EntityMetadataFactory;
use MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\DoctrineNormalizer\DummyEmbeddable;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerMetadataFactory;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DoctrineNormalizer extends TypehintNormalizer
{
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
     * @param object $object
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $context['depth'] = isset($context['depth']) ? $context['depth']+1 : 0;

        if (!isset($context['@references'])) {
            $references = array();
            $context['@references'] = &$references;
        }

        if (!isset($context['cache_key'])) {
            $context['cache_key'] = md5(serialize($context));
        }

        $class = get_class($object);

        /* @var ClassMetadata $meta */
        $context['entityMeta'] = $this->entityMetadataFactory->getMetadataFor($class);

        $data = array();
        $attributes = $this->getAttributes($object, $format, $context);
        foreach ($attributes as $attribute) {
            $value = $this->getAttributeValue($object, $attribute, $format, $context);

            $data[$attribute] = $this->normalizeProperty($value, $attribute, $format, $context);
        }

        $hint = $this->getHintFromObject($object);


        if ($context['depth'] === 0 and !empty($context['@references'])) {
            $ids = $this->getIdentifierFromObject($object);
            $id = $this->getIdentifiersAsString($ids);
            $objectId = current($hint).'#'.$id;

            unset($context['@references'][$objectId]);

            ksort($context['@references']);
            $data['@references'] = array_filter($context['@references']);
        }

        return array_merge($hint, $data);
    }

    /**
     * @param object $value
     * @param string $name
     * @param string $format
     * @param array  $context
     *
     * @return mixed|object
     */
    protected function normalizeProperty($value, $name, $format = null, array $context = array())
    {
        /** @var ClassMetadata $meta */
        $meta = $context['entityMeta'];

        if ($meta->hasField($name)) {
            return $value;
        }

        if (isset($meta->embeddedClasses[$name])) {
            return parent::normalize($value, $format, $context);
        }

        switch ($meta->associationMappings[$name]['type']) {
            case ClassMetadata::ONE_TO_ONE:
            case ClassMetadata::MANY_TO_ONE:
                return $this->normalizeAssociation($value, $name, $format, $context);

            case ClassMetadata::ONE_TO_MANY:
            case ClassMetadata::MANY_TO_MANY:
                return $this->normalizeCollection($value, $name, $format, $context);
        }
    }

    /**
     * @param object $association
     * @param string $name
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    protected function normalizeAssociation($association, $name, $format = null, array $context = array())
    {
        if ($association === null) {
            return;
        }

        $hint = $this->getHintFromObject($association);
        $ids = $this->getIdentifierFromObject($association);
        $id = $this->getIdentifiersAsString($ids);
        $data = array_merge($hint, $ids);
        $objectId = current($hint).'#'.$id;

        if (isset($context['@references'][$objectId])) {
            return $data;
        }

        $context['@references'][$objectId] = false;
        if ($association instanceof Proxy and !$association->__isInitialized()) {
            return $data;
        }

        $normalized = $this->serializer->normalize($association, $format, $context);

        $context['@references'][$objectId] = $normalized;

        return $data;
    }

    /**
     * @param object $collection
     * @param string $name
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    protected function normalizeCollection($collection, $name, $format = null, array $context = array())
    {
        if (empty($collection)) {
            return array();
        }

        if ($collection instanceof AbstractLazyCollection and !$collection->isInitialized()) {
            return array();
        }

        $data = array();
        foreach ($collection as $key => $element) {
            $data[$key] = $this->normalizeAssociation($element, $name, $format, $context);
        }

        return $data;
    }

    /**
     * @param object|string $objectOrClass
     * @param string        $format
     * @param array         $context
     *
     * @return array
     */
    public function getAttributes($objectOrClass, $format = null, array $context)
    {
        $class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;

        /** @var ClassMetadata $meta */
        $meta = $this->entityMetadataFactory->getMetadataFor($class);

        $fields = array_keys($meta->fieldMappings);
        $fields = array_filter($fields, function ($name) { return strpos($name, '.') === false; });
        $embedded = array_keys($meta->embeddedClasses);
        $associations = array_keys($meta->associationMappings);

        return array_merge($fields, $embedded, $associations);
    }

    /**
     * @param object $data
     * @param string $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        if (!is_object($data)) {
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
        if (isset($data['@references'])) {
            $context['@references'] = &$data['@references'];

            $ids = $this->getIdentifierFromArray($class, $data);
            $objectId = $class.'#'.$this->getIdentifiersAsString($ids);

            $context['@references'][$objectId] = $data;
            unset($context['@references'][$objectId]['@references']);
        }

        if (!isset($context['cache_key'])) {
            $context['cache_key'] = md5(serialize($context));
        }
        $class = $class ?: $this->getClassFromArray($data);
        $context['entityMeta'] = $this->entityMetadataFactory->getMetadataFor($class);
        $rc = new \ReflectionClass($class);
        $object = $this->instantiateObject($data, $class, $context, $rc, false);

        return $this->hydrateObject($object, $data, $class, $format, $context);
    }

    /**
     * @param object $object
     * @param array  $data
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return object
     */
    public function hydrateObject($object, $data, $class, $format, $context)
    {
        $attributes = $this->getAttributes($class, $format, $context);
        foreach ($attributes as $attribute) {
            $value = isset($data[$attribute]) ? $data[$attribute] : null;
            $value = $this->denormalizeProperty($value, null, $attribute, $format, $context);

            $this->setAttributeValue($object, $attribute, $value, $format, $context);
        }

        return $object;
    }

    /**
     * @param mixed  $value
     * @param string $class
     * @param string $name
     * @param string $format
     * @param array  $context
     *
     * @return mixed|object
     */
    protected function denormalizeProperty($value, $class, $name, $format = null, array $context = array())
    {
        /** @var ClassMetadata $meta */
        $meta = $context['entityMeta'];

        if ($meta->hasField($name)) {
            return $value;
        }

        if (isset($meta->embeddedClasses[$name])) {
            return parent::denormalize($value, null, $format, $context);
        }

        if (!isset($meta->associationMappings[$name])) {
            return parent::denormalizeProperty($value, $class, $name, $format, $context);
        }

        switch ($meta->associationMappings[$name]['type']) {
            case ClassMetadata::ONE_TO_ONE:
            case ClassMetadata::MANY_TO_ONE:
                return $this->denormalizeAssociation($value, null, $name, $format, $context);

            case ClassMetadata::ONE_TO_MANY:
            case ClassMetadata::MANY_TO_MANY:
                return $this->denormalizeCollection($value, null, $name, $format, $context);
        }
    }

    /**
     * @param mixed  $value
     * @param string $class
     * @param string $name
     * @param string $format
     * @param array  $context
     *
     * @return mixed|object
     */
    protected function denormalizeAssociation($value, $class, $name, $format = null, array $context = array())
    {
        if (empty($value)) {
            return $value;
        }

        /** @var ClassMetadata $meta */
        $meta = $context['entityMeta'];
        $class = $class ?: $meta->associationMappings[$name]['targetEntity'];
        $ids = $this->getIdentifierFromArray($class, $value);
        $objectId = $class.'#'.$this->getIdentifiersAsString($ids);

        if (!isset($context['@references'][$objectId])) {
            return null;
        }

        if ($context['@references'][$objectId] instanceof $class) {
            return $context['@references'][$objectId];
        }

        $value = $context['@references'][$objectId];
        $object = $this->instantiateObject($value, $class, $context, new \ReflectionClass($class), false);
        $context['@references'][$objectId] = &$object;
        $object = $this->hydrateObject($object, $value, $class, $format, $context);

        return $object;
    }

    /**
     * @param mixed  $collection
     * @param string $class
     * @param string $name
     * @param string $format
     * @param array  $context
     *
     * @return mixed|object
     */
    protected function denormalizeCollection($collection, $class, $name, $format = null, array $context = array())
    {
        /** @var ClassMetadata $meta */
        $meta = $context['entityMeta'];

        $targetEntity = $meta->associationMappings[$name]['targetEntity'];

        $result = array();
        foreach ($collection as $key => $element) {
            $class = $class ?: $targetEntity;

            $result[$key] = $this->denormalizeAssociation($element, $class, $name, $format, $context);
        }

        return $result;
    }

    /**
     * @param array  $data
     * @param string $type
     * @param string $format
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        $class = $type ?: $this->getClassFromArray($data);
        if (!$this->entityMetadataFactory->hasMetadataFor($class)
        or $this->entityMetadataFactory->getMetadataFor($class)->isEmbeddedClass) {
            return false;
        }

        return parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * @param array $ids
     *
     * @return string
     */
    protected function getIdentifiersAsString(array $ids = array())
    {
        return json_encode($ids, 0);
    }

    /**
     * @param object $object
     *
     * @return array
     */
    protected function getIdentifierFromObject($object)
    {
        $class = get_class($object);
        $meta = $this->entityMetadataFactory->getMetadataFor($class);
        $values = $meta->getIdentifierValues($object);

        $values = array_map(
            function ($value) {
                if (is_object($value)) {
                    return $this->getIdentifierFromObject($value);
                }

                return $value;
            },
            $values
        );

        return $values;
    }

    /**
     * @param string $class
     * @param array  $array
     *
     * @return array
     */
    protected function getIdentifierFromArray($class, $array)
    {
        $meta = $this->entityMetadataFactory->getMetadataFor($class);
        $fields = $meta->getIdentifierFieldNames();

        $ids = array();
        foreach ($fields as $field) {
            if (isset($array[$field])) {
                $ids[$field] = $array[$field];
            } else {
                $tmp = $array;
                $parts = explode('.', $field);
                foreach ($parts as $part) {
                    if (isset($tmp[$part])) {
                        if (is_array($tmp[$part])) {
                            $tmp = $tmp[$part];
                        } else {
                            $ids[$field] = $tmp[$part];

                            break;
                        }
                    }
                }
            }
        }

        return $ids;
    }
}
