<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class RecursiveNormalizer extends GetSetMethodNormalizer
{
    /**
     * @var PropertyInfoExtractor
     */
    protected $propertyInfoExtractor;

    /**
     * @param ClassMetadataFactoryInterface $classMetadataFactory
     * @param NameConverterInterface        $nameConverter
     * @param PropertyInfoExtractor         $propertyInfoExtractor
     */
    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyInfoExtractor $propertyInfoExtractor = null
    ) {
        parent::__construct($classMetadataFactory, $nameConverter);

        $this->propertyInfoExtractor = $propertyInfoExtractor;
    }

    /**
     * @param array            $data
     * @param string           $class
     * @param array            $context
     * @param \ReflectionClass $reflectionClass
     * @param array|bool       $allowedAttributes
     *
     * @throws RuntimeException
     *
     * @return object
     */
    protected function instantiateObject(array &$data, $class, array &$context, \ReflectionClass $reflectionClass, $allowedAttributes)
    {
        if (
            isset($context['object_to_populate']) &&
            is_object($context['object_to_populate']) &&
            $class === get_class($context['object_to_populate'])
        ) {
            return $context['object_to_populate'];
        }

        $format = null;
        if (isset($context['format'])) {
            $format = $context['format'];
        }

        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return new $class();
        }

        $constructorParameters = $constructor->getParameters();

        $params = array();
        foreach ($constructorParameters as $constructorParameter) {
            $paramName = $constructorParameter->name;
            $key = $this->nameConverter ? $this->nameConverter->normalize($paramName) : $paramName;

            $allowed = $allowedAttributes === false || in_array($paramName, $allowedAttributes);
            $ignored = in_array($paramName, $this->ignoredAttributes);

            if (!$allowed || $ignored) {
                continue;
            }

            $missing = !isset($data[$key]) && !array_key_exists($key, $data);
            $variadic = method_exists($constructorParameter, 'isVariadic') && $constructorParameter->isVariadic();

            if ($variadic && !$missing && !is_array($data[$paramName])) {
                $message = 'Cannot create an instance of %s from serialized data because the variadic parameter %s can only accept an array.';
                throw new RuntimeException(sprintf($message, $class, $constructorParameter->name));
            }

            if ($variadic && !$missing) {
                $params = array_merge($params, $data[$paramName]);

                continue;
            }

            if ($missing && !$variadic && !$constructorParameter->isDefaultValueAvailable()) {
                $message = 'Cannot create an instance of %s from serialized data because its constructor requires parameter "%s" to be present.';
                throw new RuntimeException(sprintf($message, $class, $constructorParameter->name));
            }

            if ($missing && $constructorParameter->isDefaultValueAvailable()) {
                $params[] = $constructorParameter->getDefaultValue();

                continue;
            }

            if (!$missing) {
                $params[] = $this->denormalizeProperty($data[$key], $class, $key, $format, $context);

                unset($data[$key]);
            }
        }

        return $reflectionClass->newInstanceArgs($params);
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
        if (!$this->propertyInfoExtractor) {
            return $data;
        }

        /** @var Type[] $types */
        $types = $this->propertyInfoExtractor->getTypes($class, $name);

        if (empty($types)) {
            return $data;
        }

        foreach ($types as $type) {
            if ($data === null && $type->isNullable()) {
                return $data;
            }

            if (!$this->serializer instanceof DenormalizerInterface) {
                $message = 'Cannot denormalize attribute "%s" because injected serializer is not a denormalizer';
                throw new RuntimeException(sprintf($message, $name));
            }

            return $this->serializer->denormalize($data, $type->getClassName(), $format, $context);
        }
    }

    /**
     * @param mixed  $data
     * @param string $class
     * @param null   $format
     * @param array  $context
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $allowedAttributes = $this->getAllowedAttributes($class, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);

        $reflectionClass = new \ReflectionClass($class);
        $subcontext = array_merge($context, array('format' => $format));
        $object = $this->instantiateObject($normalizedData, $class, $subcontext, $reflectionClass, $allowedAttributes);

        $classMethods = get_class_methods($object);
        foreach ($normalizedData as $attribute => $value) {
            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute);
            }

            $allowed = $allowedAttributes === false || in_array($attribute, $allowedAttributes);
            $ignored = in_array($attribute, $this->ignoredAttributes);

            if (!$allowed || $ignored) {
                continue;
            }

            $setter = 'set'.ucfirst($attribute);
            if (!in_array($setter, $classMethods)) {
                continue;
            }

            $value = $this->denormalizeProperty($value, $class, $attribute, $format, $context);

            $object->$setter($value);
        }

        return $object;
    }
}
