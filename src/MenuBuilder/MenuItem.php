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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MenuItem {

    protected ?MenuItem $parent = null;
    protected Collection $children;
    protected ?string $label = null;
    protected ?string $title = null;
    protected ?string $url = null;
    protected ?string $target = null;

    protected ?string $class = null;
    protected ?string $routename = null;

    protected bool $showWithoutAccess = false;

    protected bool $renderTitleAsText = false;

    protected  bool $renderAsActive = false;

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
        if ($this->renderAsActive) {
            return true;
        }

        if ($this->menuServiceHelper) {
            $request = $this->menuServiceHelper->getRequestStack()->getMainRequest();
            $route = $request->attributes->get('_route');

            if ($route === $this->routename) {
                return true;
            }
        }

        return false;
    }

    public function isBelongsTo(): bool {

        if ($this->menuServiceHelper) {
            $request = $this->menuServiceHelper->getRequestStack()->getMainRequest();
            $route = $request->attributes->get('_route');

            // "Route"-object for current route
            $currentRoute = $this->menuServiceHelper->getRouter()->getRouteCollection()->get($route);
            $controller = $currentRoute->getDefault('_controller');

            // Check method
            try {
                $reflectionMethod = new ReflectionMethod($controller);
            } catch (ReflectionException) {
                return false;
            }
            $attributes = $reflectionMethod->getAttributes(BelongsTo::class);
            if ($this->belongsToMatches($attributes)) {
                return true;
            }

            // Check class
            $parts = explode('::', $controller);
            $class = $parts[0];
            try {
                $reflectionClass = new ReflectionClass($class);
            } catch (ReflectionException) {
                return false;
            }

            $attributes = $reflectionClass->getAttributes(BelongsTo::class);
            if ($this->belongsToMatches($attributes)) {
                return true;
            }

        }

        return false;
    }

    public function hideItem(): bool
    {

        if ($this->isShowWithoutAccess()) {
            return false;
        }

        if ($this->routename) {

            $route = $this->menuServiceHelper->getRouter()->getRouteCollection()->get($this->routename);
            if (!$route) {
                return false;
            }

            $controller = $route->getDefault('_controller');

            // Check IsGrantend from Action
            try {
                $reflectionMethod = new ReflectionMethod($controller);
            } catch (ReflectionException) {
                return false;
            }
            $attributes = $reflectionMethod->getAttributes(IsGranted::class);
            foreach ($attributes as $attribute) {
                foreach ($attribute->getArguments() as $argument) {
                    if (!$this->menuServiceHelper->getAuthorizationChecker()->isGranted($argument)) {
                        return true;
                    }
                }
            }

            // Check firewall
            $roles = $this->getRolesForRoute($route);
            if (is_array($roles)) {
                foreach ($roles as $role) {
                    if (!$this->menuServiceHelper->getAuthorizationChecker()->isGranted($role)) {
                        return true;
                    }
                }
            }

        }

        return false;
    }

    private function getRolesForRoute(Route $route): ?array
    {
        $path = $route->getPath();
        $methods = $route->getMethods();
        $request = Request::create($path, $methods[0] ?? 'GET');
        [$roles] = $this->menuServiceHelper->getAccessMap()->getPatterns($request);

        return $roles ?: null;
    }

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

    public function addChilds(Collection $children): static
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
        return $this;
    }

    public function getChilds(): Collection
    {
        return $this->children;
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

        if ($this->routename && $this->menuServiceHelper) {
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

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function isShowWithoutAccess(): bool
    {
        return $this->showWithoutAccess;
    }

    public function setShowWithoutAccess(bool $showWithoutAccess): static
    {
        $this->showWithoutAccess = $showWithoutAccess;

        return $this;
    }

    public function isRenderTitleAsText(): bool
    {
        return $this->renderTitleAsText;
    }

    public function setRenderTitleAsText(bool $renderTitleAsText): static
    {
        $this->renderTitleAsText = $renderTitleAsText;

        return $this;
    }

    public function isRenderAsActive(): bool
    {
        return $this->renderAsActive;
    }

    public function setRenderAsActive(bool $renderAsActive): static
    {
        $this->renderAsActive = $renderAsActive;
        return $this;
    }

    public function setMenuServiceHelper(MenuServiceHelper $menuServiceHelper): static
    {
        $this->menuServiceHelper = $menuServiceHelper;

        return $this;
    }

    public static function createFromUrl(string $label, ?string $url = null): MenuItem
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