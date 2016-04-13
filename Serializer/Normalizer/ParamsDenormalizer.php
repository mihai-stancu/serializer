<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ParamsDenormalizer implements DenormalizerInterface
{
    const TYPE = '@params[]';

    /** @var  Serializer */
    protected $serializer;

    /**
     * @param array  $data
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        /** @var \ReflectionParameter[] $params */
        $params = $context['params'];
        $indexBy = isset($context['indexBy']) ? $context['indexBy'] : 'name';

        $arguments = [];
        foreach ($params as $param) {
            $index = $param->getPosition();

            $arguments[$index] = $this->denormalizeParam($data, $param, $format, $context);
        }
        ksort($arguments);

        $output = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $index = $param->getPosition();
            $key = $indexBy === 'name' ? $name : $index;

            $output[$key] = $arguments[$index];
        }

        return $output;
    }

    /**
     * @param array                $data
     * @param \ReflectionParameter $param
     * @param string               $format
     * @param array                $context
     *
     * @return object
     */
    protected function denormalizeParam($data, $param, $format, $context)
    {
        $name = $param->getName();
        $index = $param->getPosition();

        if (array_key_exists($name, $data)) {
            $value = $data[$name];
        } elseif (array_key_exists($index, $data)) {
            $value = $data[$index];
        } elseif ($param->isDefaultValueAvailable()) {
            $value = $param->getDefaultValue();
        } else {
            $message = sprintf('Missing parameter #%s: %s', $index, $name);
            throw new \RuntimeException($message);
        }

        if ($param->getClass()) {
            $class = $param->getClass()->getName();
        } elseif ($this->serializer->supportsDenormalization($value, MixedDenormalizer::TYPE, $format)) {
            $class = MixedDenormalizer::TYPE;
        }

        if (isset($class)) {
            $value = $this->serializer->denormalize($value, $class, $format, $context);
        }

        return $value;
    }

    /**
     * @param mixed  $data
     * @param string $type
     * @param string $format
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === static::TYPE
           and is_array($data);
    }

    /**
     * @param SerializerInterface $serializer
     *
     * @throws \InvalidArgumentException
     *
     * @codeCoverageIgnore
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$serializer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('Expected a serializer that also implements DenormalizerInterface.');
        }

        $this->serializer = $serializer;
    }
}
