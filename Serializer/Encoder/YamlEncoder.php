<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Encoder;

use Symfony\Component\Yaml\Dumper as Encoder;
use Symfony\Component\Yaml\Parser as Decoder;

class YamlEncoder extends AbstractEncoder
{
    const FORMAT = 'yaml';

    /** @var bool  */
    protected $native;

    /** @var  Encoder */
    protected $encoder;

    /** @var  Decoder */
    protected $decoder;

    public function __construct($native = true)
    {
        if ($native
        and function_exists('yaml_emit')
        and function_exists('yaml_parse')) {
            $this->native = true;
        }

        if (!$native
        and class_exists('Symfony\Component\Yaml\Dumper')
        and class_exists('Symfony\Component\Yaml\Parser')) {
            $this->encoder = new Encoder();
            $this->decoder = new Decoder();

            $this->native = false;
        }
    }

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        return (function_exists('yaml_emit') and function_exists('yaml_parse'))
            or (class_exists('Symfony\Component\Yaml\Dumper') and class_exists('Symfony\Component\Yaml\Parser'));
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
        if ($this->native) {
            return yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
        }

        return $this->encoder->dump($data);
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
        if ($this->native) {
            return yaml_parse($data);
        }

        return $this->decoder->parse($data);
    }
}
