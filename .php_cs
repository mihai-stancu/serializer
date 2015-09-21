<?php

$header = <<<EOF
Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>

This source file is subject to the license that is bundled with this source
code in the LICENSE.md file.
EOF;
Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(array(__DIR__.'/'))
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(array(
        'psr2',
        'symfony',
        'header_comment',

        'phpdoc_order',
        'ordered_use',
    ))
    ->setUsingCache(false)
    ->finder($finder)
;
