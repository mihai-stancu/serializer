<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Encoder;

class SmileEncoder extends AbstractEncoder
{
    const FORMAT = 'smile';

    /**
     * @return bool
     */
    public static function isInstalled()
    {
        return extension_loaded('libsmile')
           and function_exists('libsmile_encode')
           and function_exists('libsmile_decode');
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
        return libsmile_encode($data);
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
        return libsmile_decode($data);
    }
}
