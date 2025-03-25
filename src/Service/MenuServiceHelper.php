<?php
namespace FrankyNet\FlexMenuBundle\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\AccessMapInterface;

class MenuServiceHelper {

    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;
    private RouterInterface $router;
    private AuthorizationCheckerInterface $authorizationChecker;

    private Security $security;
    private AccessMapInterface $accessMap;

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker, Security $security, AccessMapInterface $accessMap)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->security = $security;
        $this->accessMap = $accessMap;
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

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function getAccessMap(): AccessMapInterface
    {
        return $this->accessMap;
    }

}