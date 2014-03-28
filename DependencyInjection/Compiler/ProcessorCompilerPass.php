<?php
namespace WhiteOctober\QueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface,
    Symfony\Component\DependencyInjection\Reference;

class ProcessorCompilerPass implements CompilerPassInterface
{
    /**
     * Processes any items with the whiteoctober.queue.processor tag
     *
     * @param  \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @return mixed
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition("whiteoctober.queue.collector")) {
            return;
        }

        $definition = $container->getDefinition("whiteoctober.queue.collector");

        foreach ($container->findTaggedServiceIds("whiteoctober.queue.processor") as $id => $attributes) {
            $definition->addMethodCall("addProcessor", array(new Reference($id), $id));
        }
    }
}
