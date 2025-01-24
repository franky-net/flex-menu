<?php
namespace LinkingYou\FlexMenuBundle\MenuBuilder;

use LinkingYou\FlexMenuBundle\Service\MenuServiceHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractMenu implements MenuInterface {

    protected MenuServiceHelper $menuServiceHelper;

    protected array $options = [];

    protected ?MenuItem $root = null;

    public function __construct(MenuServiceHelper $menuServiceHelper)
    {
        $this->menuServiceHelper = $menuServiceHelper;
        $this->root = new MenuItem();
    }

    public function getName(): string {

        $classname = get_class($this);

        if (preg_match('/([a-zA-Z]*)Menu$/', $classname, $matches)) {
            return $matches[1];
        }

        return $classname;
    }

    public function setOptions(array $options = []): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'start_level' => 1,
            'stop_level' => null,
            'show_inactive_paths' => true,
        ]);

        $this->options = $resolver->resolve($options);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getMenu(): MenuItem
    {
        if ($this->root->getChildren()->count() === 0) {
            $this->configureMenu();
            $this->injectMenuServiceHelper($this->root);
        }

        return $this->root;
    }

    private function injectMenuServiceHelper(MenuItem $menuItem): void
    {
        $menuItem->setMenuServiceHelper($this->menuServiceHelper);
        foreach($menuItem->getChildren() as $child) {
            $this->injectMenuServiceHelper($child);
        }
    }

}