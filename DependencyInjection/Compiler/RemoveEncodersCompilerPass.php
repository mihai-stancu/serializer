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
use Symfony\Component\DependencyInjection\Reference;

class RemoveEncodersCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('serializer')) {
            return;
        }

        $serializer = $container->getDefinition('serializer');
        $encoders = $serializer->getArgument(1);

        $services = $container->findTaggedServiceIds('ms.serializer.encoder');
        foreach ($services as $id => $attributes) {
            $this->removeEncoder($container, $encoders, $id);
        }

        $serializer->replaceArgument(1, $encoders);
    }

    /**
     * @param ContainerBuilder $container
     * @param Reference[]      $encoders
     * @param string           $id
     */
    public function removeEncoder(ContainerBuilder $container, &$encoders, $id)
    {
        $definition = $container->findDefinition($id);

        $class = $definition->getClass();
        if (method_exists($class, 'isInstalled') and !$class::isInstalled()) {
            $container->removeDefinition($id);

            $position = array_search(new Reference($id), $encoders);
            unset($encoders[$position]);
        }
    }
}
