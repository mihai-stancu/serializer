<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures;

class RecursiveNormalizerClassDummy
{
    /** @var  RecursiveNormalizerSubclassDummy  */
    protected $x;

    /**
     * @return RecursiveNormalizerSubclassDummy
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @param RecursiveNormalizerSubclassDummy $x
     */
    public function setX(RecursiveNormalizerSubclassDummy $x)
    {
        $this->x = $x;
    }
}
