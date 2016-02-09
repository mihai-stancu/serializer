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
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class BinaryNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    const TYPE = '@binary';

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
                (?P<mime>
                    [-.+\w]++
                    \/
                    [-.+\w]++
                )
                (?:
                    ;
                    charset=(?P<charset>[-.+\w]++)
                )?
            )?
            (?:
                ;
                (?P<base64>base64)
            )?
            ,
            (?P<data>
                [a-z0-9\!\$\&\\\\\'\,\(\)\*\+\,\;\=\-\.\_\~\:\@\/\?\%\s]*+\s*+
            )
        /xi
    ';

    protected static $types = [
        self::TYPE,
        MixedDenormalizer::TYPE,
    ];

    /** @var  Serializer */
    protected $serializer;

    /**
     * @param object|string $string
     * @param string        $format
     * @param array         $context
     *
     * @return array|bool|float|int|null|string
     */
    public function normalize($string, $format = null, array $context = array())
    {
        $mime = !empty($context['mime']) ? $context['mime'] : 'application/octet-stream';
        $charset = !empty($context['charset']) ? $context['charset'] : null;
        $gzip = !empty($context['gzip']) ? $context['gzip'] : null;
        $base64 = !empty($context['base64']) ? $context['base64'] : null;
        $urlencode = !empty($context['urlencode']) ? $context['urlencode'] : null;

        if ($gzip) {
            $string = gzencode($string);
            $mime = 'application/x-gzip';
            $base64 = true;
        }
        if ($base64) {
            $string = base64_encode($string);
            $base64 = 'base64';
        }
        if ($urlencode or (strpos($mime, 'text/') === 0 and !$base64)) {
            $string = urlencode($string);
        }

        $options = [$mime, $charset, $base64];
        $options = array_filter($options);

        return sprintf('data:%s,%s', implode(';', $options), $string);
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

        $mime = !empty($matches['mime']) ? $matches['mime'] : 'application/octet-stream';
        $charset = !empty($matches['charset']) ? $matches['charset'] : null;
        $gzip = ($mime === 'application/gzip');
        $base64 = !empty($matches['base64']) ? $matches['base64'] : null;
        $urldecode = (strpos($mime, 'text/') === 0 and !$base64);

        $string = $matches['data'];

        if ($base64) {
            $string = base64_decode($string);
        }
        if ($gzip) {
            $string = gzdecode($string);
        }
        if ($urldecode) {
            $string = urlencode($string);
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

    /**
     * @param SerializerInterface $serializer
     *
     * @throws \InvalidArgumentException
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$serializer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('Expected a serializer that also implements DenormalizerInterface.');
        }

        $this->serializer = $serializer;
    }
}
