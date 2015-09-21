<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Yaml\Dumper as Encoder;
use Symfony\Component\Yaml\Parser as Decoder;

class YamlEncoder implements EncoderInterface, DecoderInterface
{
    const FORMAT = 'yaml';

    /** @var  Encoder */
    protected $encoder;

    /** @var  Decoder */
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
        return class_exists('Symfony\Component\Yaml\Dumper') and class_exists('Symfony\Component\Yaml\Parser');
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
        return $this->encoder->dump($data);
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsEncoding($format)
    {
        return static::FORMAT === $format;
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
        $value = $this->decoder->parse($data);

        if ($value === false) {
            return;
        }

        return $value;
    }

    /**
     * @param string $format
     *
     * @return bool
     */
    public function supportsDecoding($format)
    {
        return static::FORMAT === $format;
    }
}
