<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Encoder;

use Sereal\Decoder;
use Sereal\Encoder;

class SerealEncoder extends AbstractEncoder
{
    const FORMAT = 'sereal';

    /**
     * @var Encoder
     */
    protected $encoder;

    /**
     * @var Decoder
     */
    protected $decoder;

    public function __construct()
    {
        $this->encoder = new Encoder();
        $this->decoder = new Decoder();
    }

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        return class_exists('Sereal\Decoder') and class_exists('Sereal\Encoder');
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
        return $this->encoder->encode($data);
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
        return $this->decoder->decode($data);
    }
}
