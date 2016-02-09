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

class StreamNormalizer extends BinaryNormalizer
{
    const TYPE = '@stream';

    /**
     * @param object|string $stream
     * @param string        $format
     * @param array         $context
     *
     * @return array|bool|float|int|null|string
     */
    public function normalize($stream, $format = null, array $context = array())
    {
        $string = stream_get_contents($stream);

        return parent::normalize($string, $format, $context);
    }

    /**
     * @param mixed  $data
     * @param string $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_resource($data) and get_resource_type($data) === 'stream';
    }

    /**
     * @param mixed  $data
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return \SplFileObject
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $string = parent::denormalize($data, \SplFileObject::class, $format, $context);

        return fopen($string, 'r');
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
        return in_array($type, static::$types)
           and is_string($data)
           and preg_match(static::SIMPLE_REGEX, $data, $M) > 0;
    }

    /**
     * @param SerializerInterface $serializer
     *
     * @throws \InvalidArgumentException
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$serializer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('Expected a serializer that also implements DenormalizerInterface.');
        }

        $this->serializer = $serializer;
    }
}
