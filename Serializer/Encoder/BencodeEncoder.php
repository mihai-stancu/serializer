<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Encoder;

use Rych\Bencode\Decoder;
use Rych\Bencode\Encoder;

class BencodeEncoder extends AbstractEncoder
{
    const FORMAT = 'bencode';

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        return class_exists('Rych\Bencode\Encoder') and class_exists('Rych\Bencode\Decoder');
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
        return Encoder::encode($data);
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
        return Decoder::decode($data);
    }
}
