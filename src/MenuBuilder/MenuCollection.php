<?php
namespace LinkingYou\FlexMenuBundle\MenuBuilder;
class MenuCollection {

    protected array $items = [];

    public function __construct(iterable $menus = [])
    {
        foreach ($menus as $item) {
            $this->items[$item->getName()] = $item;
        }
    }

    public function getItems(): array {
        return $this->items;
    }

}