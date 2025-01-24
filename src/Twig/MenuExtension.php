<?php
namespace LinkingYou\FlexMenuBundle\Twig;

use LinkingYou\FlexMenuBundle\MenuBuilder\MenuCollection;
use LinkingYou\FlexMenuBundle\MenuBuilder\MenuInterface;
use LinkingYou\FlexMenuBundle\MenuBuilder\MenuItem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension {
    private MenuCollection $menuCollection;

    private ?MenuInterface $currentMenu = null;

    private array $renderOptions = [];

    public function __construct(MenuCollection $menuCollection)
    {
        $this->menuCollection = $menuCollection;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu_render', [$this, 'menu_render'], ['is_safe' => ['html']]),
        ];
    }

    public function menu_render(string $menu, array $options = [], array $renderOptions = []): string {

        foreach ($this->menuCollection->getItems() as $item) {
            if ($item instanceof MenuInterface) {

                $this->renderOptions = $this->resolveRenderOptions($renderOptions);

                if (strtolower($menu) === strtolower($item->getName())) {

                    $item->setOptions($options);
                    $this->currentMenu = $item;

                    return $this->render();
                }
            }
        }

        return sprintf('Menu %s not found', $menu);
    }

    private function resolveRenderOptions(array $options): array {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'nav_id' => null,
        ]);

        return $resolver->resolve($options);
    }

    private function render(): string {
        $slugger = new AsciiSlugger();
        $navIdString = $this->renderOptions['nav_id'] ? sprintf(' id="%s"', $this->renderOptions['nav_id']) : '';
        $tmp = sprintf('<nav%s class="%s">', $navIdString, $slugger->slug($this->currentMenu->getName())->lower());
        $tmp .= $this->renderElements($this->currentMenu->getMenu());
        $tmp .= '</nav>';

        return $tmp;
    }

    private function renderElements(MenuItem $item): string {
        $tmp = '';

        $skipLevel = false;

        // handle stop_level
        $stopLevel = $this->currentMenu->getOptions()['stop_level'];
        if ($stopLevel && ($item->getLevel() > $stopLevel)) {
            $skipLevel = true;
        }

        // handle start_level
        $startLevel = $this->currentMenu->getOptions()['start_level'];
        if ($startLevel && ($item->getLevel() < $startLevel)) {
            $skipLevel = true;
        }

        // handle active-path & show_inactive_paths
        $show_inactive_paths = (bool) $this->currentMenu->getOptions()['show_inactive_paths'];
        if (!$show_inactive_paths && (!$item->isActivePath() && !$item->isActive())) {
            $skipLevel = true;
        }

        if (!$skipLevel) {
            $tmp .= sprintf('<ul class="level_%s">', $item->getLevel());
        }
        foreach ($item->getChildren() as $child) {
            if ($child instanceof MenuItem) {

                if (!$skipLevel) {
                    $class = [];

                    if ($child->isActive()) {
                        $class[] = 'active';
                    }

                    if ($child->isActivePath()) {
                        $class[] = 'active-path';
                    }

                    $classString = $class ? ' class="' . implode(' ', $class) . '"' : '';
                    $targetString = $child->getTarget() ? ' target="' . $child->getTarget() . '"' : '';

                    $tmp .= sprintf('<li%s>', $classString);
                    $tmp .= sprintf('<a href="%s"%s>%s</a>', $child->getUrl(), $targetString, $child->getTitle());
                }

                // Next level
                if ($child->getChildren()->count() > 0) {
                    $tmp .= $this->renderElements($child);
                }

                if (!$skipLevel) {
                    $tmp .= '</li>';
                }

            }
        }
        if (!$skipLevel) {
            $tmp .= '</ul>';
        }

        return $tmp;
    }

}