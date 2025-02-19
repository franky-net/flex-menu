<?php
namespace FrankyNet\FlexMenuBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuServiceHelper {

    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;
    private RouterInterface $router;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

}