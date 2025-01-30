<?php
namespace FrankyNet\FlexMenuBundle\MenuBuilder;

use FrankyNet\FlexMenuBundle\Attribute\BelongsTo;
use FrankyNet\FlexMenuBundle\Service\MenuServiceHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MenuItem {

    protected ?MenuItem $parent = null;
    protected Collection $children;
    protected ?string $label = null;
    protected ?string $title = null;
    protected ?string $url = null;
    protected ?string $target = null;
    protected ?string $routename = null;

    private ?MenuServiceHelper $menuServiceHelper = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    public function getLevel(): int
    {
        if ($this->isRoot()) {
            return 1;
        } else {
            return $this->parent->getLevel() + 1;
        }
    }

    public function isActive(): bool
    {
        if ($this->menuServiceHelper) {
            $request = $this->menuServiceHelper->getRequestStack()->getMainRequest();
            $route = $request->attributes->get('_route');

            if ($route === $this->routename) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    public function isBelongsTo(): bool {

        if ($this->menuServiceHelper) {
            $request = $this->menuServiceHelper->getRequestStack()->getMainRequest();
            $route = $request->attributes->get('_route');

            // "Route"-object for current route
            $currentRoute = $this->menuServiceHelper->getRouter()->getRouteCollection()->get($route);
            $controller = $currentRoute->getDefault('_controller');

            // Check method
            $reflectionMethod = new ReflectionMethod($controller);
            $attributes = $reflectionMethod->getAttributes(BelongsTo::class);
            if ($this->belongsToMatches($attributes)) {
                return true;
            }

            // Check class
            $parts = explode('::', $controller);
            $class = $parts[0];
            $reflectionClass = new ReflectionClass($class);
            $attributes = $reflectionClass->getAttributes(BelongsTo::class);
            if ($this->belongsToMatches($attributes)) {
                return true;
            }

        }

        return false;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    public function isActivePath(): bool
    {

        if ($this->isActive()) {
            return false;
        }

        if ($this->isBelongsTo()) {
            return true;
        }

        return $this->hasActiveChildren($this);
    }

    private function hasActiveChildren(MenuItem $menuItem): bool {

        // check direct childs
        foreach ($menuItem->children as $child) {
            if ($child->isActive($menuItem) || $child->isBelongsTo($menuItem)) {
                return true;
            }
        }

        // check subchilds
        foreach ($menuItem->children as $child) {
            $hasActiveChilds = $this->hasActiveChildren($child);
            if ($hasActiveChilds) {
                return true;
            }
        }
        return false;
    }

    public function getParent(): ?MenuItem
    {
        return $this->parent;
    }

    public function setParent(?MenuItem $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function addChild(MenuItem $child): static
    {
        $child->setParent($this);
        $this->children->add($child);

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->routename && $this->menuServiceHelper && $this->menuServiceHelper->getUrlGenerator() instanceof UrlGeneratorInterface) {
            try {
                return $this->menuServiceHelper->getUrlGenerator()->generate($this->routename);
            } catch (Exception) {
            }
        }

        return null;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(?string $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function getRoutename(): ?string
    {
        return $this->routename;
    }

    public function setRoutename(?string $routename): static
    {
        $this->routename = $routename;

        return $this;
    }

    public function setMenuServiceHelper(MenuServiceHelper $menuServiceHelper): static
    {
        $this->menuServiceHelper = $menuServiceHelper;

        return $this;
    }

    public static function createFromUrl(string $label, string $url = null): MenuItem
    {
        return (new MenuItem())->setLabel($label)->setUrl($url);
    }

    public static function createFromRouteName(string $label, string $routeName): MenuItem {
        return (new MenuItem())->setLabel($label)->setRoutename($routeName);
    }

    /**
     * @param array $attributes
     * @return bool
     */
    private function belongsToMatches(array $attributes): bool
    {
        if (count($attributes) > 0) {
            $belongsTo = current($attributes);
            if ($belongsTo instanceof ReflectionAttribute) {
                $arguments = $belongsTo->getArguments();
                if (count($arguments) > 0) {
                    $argument = current($arguments);

                    if ($argument === $this->routename) {
                        return true;
                    }
                }

            }
        }
        return false;
    }

}