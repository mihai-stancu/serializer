<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer as SymfonyArrayDenormalizer;

class MixedArrayDenormalizer extends SymfonyArrayDenormalizer
{
    const TYPE = '@mixed[]';

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
           and is_array($data)
           and parent::supportsDenormalization($data, $type, $format);
    }
}
