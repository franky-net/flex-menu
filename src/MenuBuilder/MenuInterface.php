<?php
namespace LinkingYou\FlexMenuBundle\MenuBuilder;

interface MenuInterface {

    public function getName(): string;

    public function configureMenu(): void;

    public function getMenu(): MenuItem;

    public function setOptions(array $options): void;

    public function getOptions(): array;

}