<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const TYPE = '@datetime';

    const CONTEXT_FORMAT = 'datetime_format';

    const REGEX = '
        /^
            (?P<date>\d{4}-\d{2}-\d{2}      )?
            (?:
                (?P<tsep>[T ]               )?
                (?P<time>\d{2}:\d{2}:\d{2}? )
                (?P<usec>\.\d++             )?
                (?P<zsep>[ ]                )?
                (?P<zone>[+-]\d{2}:?\d{2}   )?
            )?
        $/x
    ';

    protected static $classes = [
        \DateTimeInterface::class,
        \DateTime::class,
        \DateTimeImmutable::class,
    ];

    protected static $types = [
        self::TYPE,
        MixedDenormalizer::TYPE,
    ];

    /** @var  string */
    protected $format;

    /**
     * @param string $format
     */
    public function __construct($format = \DateTime::ISO8601)
    {
        $this->format = $format;
    }

    /**
     * @param object $object
     * @param string $format
     * @param array  $context
     *
     * @return string
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof \DateTimeInterface) {
            throw new InvalidArgumentException('The object must implement the "\DateTimeInterface".');
        }

        $format = isset($context[static::CONTEXT_FORMAT]) ? $context[self::CONTEXT_FORMAT] : $this->format;

        return $object->format($format);
    }

    /**
     * @param object $data
     * @param string $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof \DateTimeInterface;
    }

    /**
     * @param string $data
     * @param string $class
     * @param string $format
     * @param array  $context
     *
     * @return \DateTimeInterface
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        preg_match(static::REGEX, $data, $parts);

        $format = '';
        $format .= !empty($parts['date']) ? 'Y-m-d' : '';
        $format .= !empty($parts['tsep']) ? str_replace('T', '\T', $parts['tsep']) : '';
        $format .= !empty($parts['time']) ? 'H:i:s' : '';
        $format .= !empty($parts['usec']) ? '.u' : '';
        $format .= !empty($parts['zsep']) ? ' ' : '';
        $format .= !empty($parts['zone']) ? (($parts['zone'][3] === ':') ? 'P' : 'O') : '';

        $object = \DateTime::createFromFormat($format, $data);
        if ($object === false) {
            throw new \InvalidArgumentException('The provided data or format are not valid.');
        }

        return $object;
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
        return in_array($type, static::$classes) or in_array($type, static::$types)
           and is_string($data) and preg_match(static::REGEX, $data) > 0;
    }
}
