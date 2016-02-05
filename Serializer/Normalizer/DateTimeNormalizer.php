<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer as SymfonyDateTimeNormalizer;

class DateTimeNormalizer extends SymfonyDateTimeNormalizer
{
    const FORMAT = MixedDenormalizer::FORMAT;

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

    /** @var  string */
    protected $format;

    /**
     * @param mixed  $data
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
        $format .= (!empty($parts['zone']) and $parts['zone'][3] === ':') ? 'P' : 'O';

        return parent::denormalize($data, \DateTime::class, $format, $context);
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
        return $format === static::FORMAT
           and is_string($data)
           and preg_match(static::REGEX, $data) > 0;
    }
}
