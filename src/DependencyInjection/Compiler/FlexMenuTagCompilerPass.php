<?php
namespace LinkingYou\FlexMenuBundle\DependencyInjection\Compiler;

use LinkingYou\FlexMenuBundle\MenuBuilder\MenuInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FlexMenuTagCompilerPass implements CompilerPassInterface {
    public function process(ContainerBuilder $container): void
    {
        $tagName = 'linking_you.flex_menu';

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            if ($class && is_a($class, MenuInterface::class, true)) {
                $definition->addTag($tagName);
            }
        }
    }

}