<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Doctrine\DBAL\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class MixedDenormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    const TYPE = '@mixed';

    /** @var  SerializerInterface|DenormalizerInterface */
    protected $serializer;

    /**
     * @param array  $values
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return array|object
     */
    public function denormalize($values, $class, $format = null, array $context = array())
    {
        if (!is_array($values)) {
            return $values;
        }

        $filtered = array_filter(
            $values,
            function ($value) use ($class, $format) {
                return $this->serializer->supportsDenormalization($value, $class, $format);
            }
        );

        if (count($filtered) !== count($values)) {
            throw new \InvalidArgumentException('Not all values within the array can be denormalized.');
        }

        return array_map(
            function ($value) use ($class, $format, $context) {
                return $this->serializer->denormalize($value, $class, $format, $context);
            },
            $filtered
        );
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
        if ($type !== static::TYPE) {
            return false;
        }

        if (!is_null($data) and !is_scalar($data) and !is_resource($data) and !is_array($data)) {
            return false;
        }

        return true;
    }

    /**
     * @param SerializerInterface $serializer
     *
     * @throws InvalidArgumentException
     *
     * @codeCoverageIgnore
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$serializer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('Expected a serializer that also implements DenormalizerInterface.');
        }

        $this->serializer = $serializer;
    }
}
