<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppKernel extends Kernel
{

    // http header admin value
    const ADMIN_HEADER = 'test';

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new AppBundle\AppBundle(),
            new AutoRoutingBundle\AutoRoutingBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }

    /**
     * Rewriting parent function, for enabling auto routing
     *
     * @param Request $request
     * @param int $type
     * @param bool $catch
     * @return Response
     */
//    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
//    {
//        if (false === $this->booted) {
//            $this->boot();
//        }
//
//        // Get the response from parent, this is need to be done to check the route if it's one of defined routes
//        $response = parent::handle($request);
//
//        // We will return content if statusCode is 200
//        if ($response->getStatusCode() === 200) {
//            return $response;
//        }
//
//        //Getting path info from the request and checking if bundle, controller and action exists
//        $route = $this->loadRoute($request->getPathInfo(), $request);
//
//        if (false === $route) {
//            $response = new Response('Not Found', 404);
//            $response->send();
//        } else {
//            $request->attributes->set('_controller', $route);
//            return parent::handle($request);
//        }
//
//    }

    /**
     * Function to check if bundle, controller and action provided in the request are valid
     * if it's valid we will return string to create route else return false
     * @param $pathInfo
     * @return bool|string
     */
    protected function loadRoute($pathInfo, Request $request)
    {
        $pathInfo = substr($pathInfo, 1);
        $pathInfoArr = explode('/', $pathInfo);

        if (count($pathInfoArr) !== 3) {
            return false;
        }

        list($bundle, $controller, $action) = $pathInfoArr;
        $ucfirstBundle = ucfirst(strtolower($bundle));
        $ucfirstController = ucfirst(strtolower($controller));

        if($ucfirstBundle === 'Admin') {
            $isAdmin = $this->checkAdminHeaders($request);
            if (!$isAdmin) {
                return false;
            }
        }

        $className = "{$ucfirstBundle}Bundle\\Controller\\{$ucfirstController}Controller";

        if (is_a($className, 'Symfony\Bundle\FrameworkBundle\Controller\Controller', true)
            && in_array("{$action}Action", get_class_methods($className), true)) {
            return "{$className}::{$action}Action";
        }
        return false;
    }

    /**
     * Function to check headers for request to AdminBundle controllers
     * @param Request $request
     */
    protected function checkAdminHeaders(Request $request)
    {
        $headers = $request->headers->all();

        if (isset($headers['admin']) && $headers['admin'][0] == self::ADMIN_HEADER) {
            return true;
        }

        return false;
    }
}
