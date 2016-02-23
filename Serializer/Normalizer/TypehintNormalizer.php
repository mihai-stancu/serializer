<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class TypehintNormalizer extends RecursiveNormalizer
{
    /**
     * @var array Type names mapped to classes
     */
    protected $types = [];

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param array $types
     */
    public function setTypes(array $types)
    {
        $this->types = $types;
    }

    /**
     * @param string $type
     * @param string $class
     */
    public function addType($type, $class)
    {
        $this->types[$type] = $class;
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
        $data = parent::normalize($object, $format, $context);

        if (!is_array($data)) {
            return $data;
        }

        return array_merge($this->getHintFromObject($object), $data);
    }

    /**
     * @param mixed  $data
     * @param string $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        if (is_object($data)) {
            return parent::supportsNormalization($data, $format);
        }

        return false;
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
        if (!($_class = $this->getClassFromArray($data))) {
            return parent::denormalize($data, $class, $format, $context);
        }

        $class = $_class;

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * @param mixed  $data
     * @param string $class
     * @param string $name
     * @param string $format
     * @param array  $context
     *
     * @throws RuntimeException
     *
     * @return mixed|object
     */
    protected function denormalizeProperty($data, $class, $name, $format = null, array $context = array())
    {
        if (!($_class = $this->getClassFromArray($data))) {
            return parent::denormalizeProperty($data, $class, $name, $format, $context);
        }

        $class = $_class;
        if (!$this->serializer instanceof DenormalizerInterface) {
            $message = 'Cannot denormalize attribute "%s" because injected serializer is not a denormalizer';
            throw new RuntimeException(sprintf($message, $name));
        }

        return $this->serializer->denormalize($data, $class, $format, $context);
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
        if (!$this->getClassFromArray($data)) {
            return parent::supportsDenormalization($data, $type, $format);
        }

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    protected function getClassFromArray($data)
    {
        if (!is_array($data)) {
            return;
        }

        if (isset($data['@type']) and $type = $data['@type'] and isset($this->types[$type])) {
            $class = $this->types[$type];
        }

        if (isset($data['@class'])) {
            $class = $data['@class'];
        }

        if (isset($class) and class_exists($class)) {
            return $class;
        }
    }

    /**
     * @param object $object
     *
     * @return array
     */
    protected function getHintFromObject($object)
    {
        if (!is_object($object)) {
            return array();
        }

        $class = get_class($object);
        $type = array_search($class, $this->types);

        if ($type) {
            return array('@type' => $type);
        }

        return  array('@class' => $class);
    }
}
