<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DataUriNormalizer as SymfonyDataUriNormalizer;

class DataUriNormalizer extends SymfonyDataUriNormalizer
{
    const FORMAT = MixedDenormalizer::FORMAT;

    /**
     * @param mixed  $data
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return \DateTimeInterface
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return parent::denormalize($data, \SplFileObject::class, $format, $context);
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
        return $format === static::FORMAT
           and preg_match('/^data:(\w++\/\w++(;\w++=[\w]++)?)?(;base64)/', $data) > 0;
    }
}
