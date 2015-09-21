<?php

/*
 * Copyright (c) 2015 Mihai Stancu <stancu.t.mihai@gmail.com>
 *
 * This source file is subject to the license that is bundled with this source
 * code in the LICENSE.md file.
 */

namespace MS\SerializerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CheckDependenciesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('ms.serializer.encoder');
        foreach ($taggedServices as $id => $tags) {
            $definition = $container->findDefinition($id);

            $class = $definition->getClass();
            if (method_exists($class, 'checkDependencies') and $class::checkDependencies()) {
                $definition->addTag('serializer.encoder');
            } else {
                $container->removeDefinition($id);
            }
        }
    }
}
