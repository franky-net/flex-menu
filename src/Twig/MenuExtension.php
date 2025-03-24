<?php
namespace FrankyNet\FlexMenuBundle\Twig;

use FrankyNet\FlexMenuBundle\MenuBuilder\MenuCollection;
use FrankyNet\FlexMenuBundle\MenuBuilder\MenuInterface;
use FrankyNet\FlexMenuBundle\MenuBuilder\MenuItem;
use Nette\Utils\Html;
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
            new TwigFunction('menu_render', $this->menu_render(...), ['is_safe' => ['html']]),
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

        // create <ul>
        $class = [
            'level_' . $item->getLevel()
        ];
        $htmlUl = Html::el('ul');
        if ($item->isActive()) {
            $class[] = 'active';
        }
        if ($item->isActivePath()) {
            $class[] = 'active-path';
        }
        $htmlUl->class(implode(' ', $class));

        if (!$skipLevel) {
            $tmp .= $htmlUl->startTag();
        }

        foreach ($item->getChildren() as $child) {

            if ($child instanceof MenuItem) {

                if (!$skipLevel && !$child->hideItem()) {
                    $class = [];

                    if ($child->isActive()) {
                        $class[] = 'active';
                    }

                    if ($child->isActivePath()) {
                        $class[] = 'active-path';
                    }

                    // create <li>
                    $htmlLi = Html::el('li');
                    if (count($class) > 0) {
                        $htmlLi->class(implode(' ', $class));
                    }
                    $tmp .= $htmlLi->startTag();

                    // create <a> or <span>
                    if ($child->getUrl()) {
                        $htmlLink = Html::el('a')
                            ->href($child->getUrl());
                    } else {
                        $htmlLink = Html::el('span');
                    }
                    if ($child->getTarget()) {
                        $htmlLink->target($child->getTarget());
                    }
                    if ($child->getTitle()) {
                        $htmlLink->title($child->getTitle());
                    }
                    if ($child->getClass()) {
                        $htmlLink->class($child->getClass());
                    }
                    $htmlLink->setText($child->getLabel());

                    $tmp .= $htmlLink;

                }

                // Next level
                if ($child->getChildren()->count() > 0) {
                    $tmp .= $this->renderElements($child);
                }

                if (!$skipLevel && !$child->hideItem()) {
                    if (isset($htmlLi)) {

                        // Render title as text
                        if ($child->isRenderTitleAsText()) {
                            $titleDiv = Html::el('div');
                            $titleDiv->class = 'title';
                            $titleDiv->setText($child->getTitle());
                            $tmp .= $titleDiv;
                        }

                        $tmp .= $htmlLi->endTag();
                    }

                }

            }
        }

        if (!$skipLevel) {
            $tmp .= $htmlUl->endTag();
        }

        return $tmp;
    }

}