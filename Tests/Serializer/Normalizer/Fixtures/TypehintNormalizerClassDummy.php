<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\Tests\Serializer\Normalizer\Fixtures;

class TypehintNormalizerClassDummy extends RecursiveNormalizerClassDummy
{
    /** @var  TypehintNormalizerSubclassDummy  */
    protected $y;

    /**
     * @return TypehintNormalizerSubclassDummy
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param TypehintNormalizerSubclassDummy $y
     */
    public function setY($y)
    {
        $this->y = $y;
    }
}
