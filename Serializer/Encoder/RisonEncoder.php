<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Encoder;

use Kunststube\Rison\RisonDecoder as Decoder;
use Kunststube\Rison\RisonEncoder as Encoder;

class RisonEncoder extends AbstractEncoder
{
    const FORMAT = 'rison';

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        return class_exists('Kunststube\Rison\RisonEncoder') and class_exists('Kunststube\Rison\RisonDecoder');
    }

    /**
     * @param mixed  $data
     * @param string $format
     * @param array  $context
     *
     * @return string
     */
    public function encode($data, $format, array $context = array())
    {
        $encoder = new Encoder($data);

        return $encoder->encode();
    }

    /**
     * @param string $data
     * @param string $format
     * @param array  $context
     *
     * @return mixed
     */
    public function decode($data, $format, array $context = array())
    {
        $decoder = new Decoder($data);

        return $decoder->decode();
    }
}
