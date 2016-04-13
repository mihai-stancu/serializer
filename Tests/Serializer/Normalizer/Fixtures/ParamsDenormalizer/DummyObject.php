<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures\ParamsDenormalizer;

class DummyObject
{
    public $a;

    public $b;

    public function __construct($a = null, $b = null)
    {
        $this->a = $a;
        $this->b = $b;
    }
}
