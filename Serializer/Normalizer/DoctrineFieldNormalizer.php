<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory as EntityMetadataFactory;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerMetadataFactory;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DoctrineFieldNormalizer extends TypehintNormalizer
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
     * @param object $object
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $class = get_class($object);
        /** @var ClassMetadata $meta */
        $meta = $this->entityMetadataFactory->getMetadataFor($class);

        $hint = $this->getHintFromObject($object);
        $ids = $this->getIdentifierValues($meta, $object);
        $id = $this->getIdentifiersAsString($ids);

        if (!isset($context['@associations'][current($hint)][$id])) {
            $context['@associations'][current($hint)][$id] = false;

            if (!($object instanceof Proxy) or $object->__isInitialized()) {
                $context['@associations'][current($hint)][$id] = $this->serializer->normalize(
                    $object,
                    DoctrineEntityNormalizer::FORMAT,
                    $context
                );
            }
        }

        return array_merge($hint, $ids);
    }

    /**
     * @param mixed  $data
     * @param string $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        if ($format !== static::FORMAT or !is_object($data)) {
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
        $meta = $this->entityMetadataFactory->getMetadataFor($class);
        /*var_dump($meta->getIdentifierFieldNames());
        var_dump($data);*/

        return parent::denormalize($data, $class, $format, $context);
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
        if ($format !== static::FORMAT) {
            return false;
        }

        var_dump(__METHOD__.' '.__LINE__.' '.$type.' '.$format);
        $class = $this->getClassFromArray($data);
        if (!$this->entityMetadataFactory->hasMetadataFor($class)
        or $this->entityMetadataFactory->getMetadataFor($class)->isEmbeddedClass) {
            return true;
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
