<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Encoder;

use Zend\Config\Reader\Ini as Decoder;
use Zend\Config\Writer\Ini as Encoder;

class IniEncoder extends AbstractEncoder
{
    const FORMAT = 'ini';

    /** @var  Encoder */
    protected $encoder;

    /** @var  Decoder */
    protected $decoder;

    public function __construct()
    {
        $this->decoder = new Decoder();
        $this->encoder = new Encoder();
    }

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        return class_exists('Zend\Config\Writer\Ini') and class_exists('Zend\Config\Reader\Ini');
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
        return $this->encoder->toString($data);
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
        return $this->decoder->fromString($data);
    }
}
