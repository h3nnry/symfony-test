<?php
/**
 * Created by PhpStorm.
 * User: lunguandrei
 * Date: 26.08.17
 * Time: 19:19
 */

namespace AutoRoutingBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\RequestStack;

class AutoRoutingListener extends RouterListener
{
    /**
     * @var RouteCollection
     */
    protected $routes;
    private $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $router = $this->container->get('router');
        $this->routes = $router->getRouteCollection();
        parent::__construct(
            new UrlMatcher($this->routes, new RequestContext()), new RequestStack()
        );
    }
    protected function loadRoutes($pathInfo)
    {
        if ($logicName = $this->verifyRoute($pathInfo)) {
            $route = str_replace([':', 'bundle'], ['/', ''], strtolower($logicName));
            $this->routes->add($route, new Route($route, array(
                '_controller'   =>  $logicName,
            )));
            return true;
        }
        return false;
    }

    public function verifyRoute($pathInfo) {
        $pathInfo = substr($pathInfo, 1);
        $pathInfoArr = explode('/', $pathInfo);
        if (count($pathInfoArr) !== 3) {
            return false;
        }
        list($bundle, $controller, $action) = $pathInfoArr;
        $ucfirstBundle = ucfirst(strtolower($bundle));
        $ucfirstController = ucfirst(strtolower($controller));

        $class_name = "{$ucfirstBundle}Bundle\\Controller\\{$ucfirstController}Controller";
        if (is_a($class_name, 'Symfony\Bundle\FrameworkBundle\Controller\Controller', true)
            && in_array("{$action}Action", get_class_methods($class_name), true)) {
            return "{$ucfirstBundle}Bundle:$controller:$action";
        }
        return false;
    }
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->loadRoutes($event->getRequest()->getPathInfo());
        parent::onKernelRequest($event);
    }
}