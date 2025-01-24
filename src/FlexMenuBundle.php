<?php
namespace LinkingYou\FlexMenuBundle;

use LinkingYou\FlexMenuBundle\DependencyInjection\Compiler\FlexMenuTagCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FlexMenuBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new FlexMenuTagCompilerPass());
    }
}