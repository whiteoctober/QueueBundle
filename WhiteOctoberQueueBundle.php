<?php

namespace WhiteOctober\QueueBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\HttpKernel\Bundle\Bundle;

use WhiteOctober\QueueBundle\DependencyInjection\Compiler\ProcessorCompilerPass;

/**
 * WhiteOctoberQueueBundle class
 */
class WhiteOctoberQueueBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ProcessorCompilerPass());
    }
}
