<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures;

class RecursiveNormalizerSubclassDummy
{
    /** @var  string */
    protected $a;

    /**
     * @return string
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @param string $a
     */
    public function setA($a)
    {
        $this->a = $a;
    }

    /** @var  string */
    protected $b;

    /**
     * @return string
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @param string $b
     */
    public function setB($b)
    {
        $this->b = $b;
    }
}
