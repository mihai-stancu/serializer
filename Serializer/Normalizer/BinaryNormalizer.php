<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BinaryNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const TYPE = '@binary';

    const CONTEXT_MIME = 'binary_mime';
    const CONTEXT_CHARSET = 'binary_charset';
    const CONTEXT_GZIP = 'binary_gzip';
    const CONTEXT_BASE64 = 'binary_base64';
    const CONTEXT_URLENCODE = 'binary_urlencode';
    const CONTEXT_DATA = 'binary_data';

    const SIMPLE_REGEX = '
        /^
            data:
            (?:
                (?P<mime>
                    [\.\w-]++
                    \/
                    [\.\w-]++
                )
                (?:
                    ;
                    charset=(?P<charset>[\w-]++)
                )?
            )?
            (?:
                ;
                (?P<base64>base64)
            )?
            ,
        /x
    ';

    const FULL_REGEX = '
        /^
            data:
            (?:
                (?P<binary_mime>
                    [-.+\w]++
                    \/
                    [-.+\w]++
                )
                (?:
                    ;
                    charset=(?P<binary_charset>[-.+\w]++)
                )?
            )?
            (?:
                ;
                (?P<binary_base64>base64)
            )?
            ,
            (?P<binary_data>
                [a-z0-9\!\$\&\\\\\'\,\(\)\*\+\,\;\=\-\.\_\~\:\@\/\?\%\s]*+\s*+
            )
            \Z
        /xi
    ';

    protected static $types = [
        self::TYPE,
        MixedDenormalizer::TYPE,
    ];

    /**
     * @param object|string $string
     * @param string        $format
     * @param array         $context
     *
     * @return array|bool|float|int|null|string
     */
    public function normalize($string, $format = null, array $context = array())
    {
        $mime = isset($context[static::CONTEXT_MIME]) ? $context[static::CONTEXT_MIME] : 'application/octet-stream';
        $charset = isset($context[static::CONTEXT_CHARSET]) ? $context[static::CONTEXT_CHARSET] : null;
        $gzip = isset($context[static::CONTEXT_GZIP]) ? $context[static::CONTEXT_GZIP] : null;
        $base64 = isset($context[static::CONTEXT_BASE64]) ? $context[static::CONTEXT_BASE64] : null;
        $base64 = ($base64 !== null) ? $base64 : strpos($mime, '/octet-stream') !== false;
        $urlencode = isset($context[static::CONTEXT_URLENCODE]) ? $context[static::CONTEXT_URLENCODE] : null;
        $urlencode = ($urlencode !== null) ? $urlencode : strpos($mime, 'text/') !== false;

        if ($gzip) {
            $string = gzencode($string);
            $mime = 'application/x-gzip';
        }
        if ($urlencode and !$base64) {
            $string = urlencode($string);
        }
        if ($base64 or !$urlencode) {
            $string = base64_encode($string);
        }

        $options = $mime;
        if ($charset and $mime) {
            $options .= ';charset='.$charset;
        }
        if ($base64) {
            $options .= ';base64';
        }

        return sprintf('data:%s,%s', $options, $string);
    }

    /**
     * @param mixed  $data
     * @param string $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_string($data) and !mb_detect_encoding($data, ['ASCII', 'UTF-8'], true);
    }

    /**
     * @param string $string
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return \SplFileObject
     */
    public function denormalize($string, $class, $format = null, array $context = array())
    {
        if (!preg_match(static::FULL_REGEX, $string, $matches)) {
            throw new \InvalidArgumentException('The provided "data:" URI is not valid.');
        }

        $mime = !empty($matches[static::CONTEXT_MIME]) ? $matches[static::CONTEXT_MIME] : 'application/octet-stream';
        $charset = !empty($matches[static::CONTEXT_CHARSET]) ? $matches[static::CONTEXT_CHARSET] : null;
        $gzip = strpos($mime, 'gzip') !== false;
        $base64 = !empty($matches[static::CONTEXT_BASE64]) ? $matches[static::CONTEXT_BASE64] : null;
        $urldecode = strpos($mime, 'text/') !== false;

        $string = $matches[static::CONTEXT_DATA];

        if ($base64) {
            $string = base64_decode($string);
        }
        if ($gzip) {
            $string = gzdecode($string);
        }
        if ($urldecode or !$base64) {
            $string = urldecode($string);
        }

        return $string;
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
           and is_string($data) and preg_match(static::SIMPLE_REGEX, $data) > 0;
    }
}
