<?php

namespace Devim\Provider\CorsServiceProvider;

use Devim\Provider\CorsServiceProvider\EventSubscriber\CorsEventSubscriber;
use Devim\Provider\CorsServiceProvider\Service\CorsService;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CorsServiceProvider.
 */
class CorsServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface, BootableProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register(Container $container)
    {
        $container['cors.allowOrigin'] = '*';
        $container['cors.allowMethods'] = [];
        $container['cors.maxAge'] = 15;
        $container['cors.allowCredentials'] = false;
        $container['cors.exposeHeaders'] = '';

        $container['cors'] = function () use ($container) {
            return new CorsService(
                $container['cors.exposeHeaders'],
                $container['cors.allowOrigin'],
                $container['cors.allowMethods'],
                $container['cors.allowCredentials'],
                $container['cors.maxAge']
            );
        };
    }

    /**
     * @param Container                $app
     * @param EventDispatcherInterface $dispatcher
     */
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber(new CorsEventSubscriber($app));
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for 'dynamic' configuration (whenever
     * a service must be requested).
     *
     * @param Application $app
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    public function boot(Application $app)
    {
        $app->match('{url}', function (Request $request) use ($app) {
        })->assert('url', '.*')->method('OPTIONS');
    }

}
