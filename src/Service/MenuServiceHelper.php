<?php
namespace FrankyNet\FlexMenuBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class MenuServiceHelper {

    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;
    private RouterInterface $router;

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack, RouterInterface $router)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->router = $router;
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

}