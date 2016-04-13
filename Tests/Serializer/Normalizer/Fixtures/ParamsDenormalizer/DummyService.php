<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\ParamsDenormalizer;

class DummyService
{
    public function dummyMethod(DummyObject $k, $l, $m = 99)
    {
    }
}
