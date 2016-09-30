<?php

namespace Devim\Provider\CorsServiceProvider\EventSubscriber;

use Pimple\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class CorsEventSubscriber
 */
class CorsEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * CorsEventSubscriber constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onResponse'],
            KernelEvents::REQUEST => ['onRequest']
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if($request->getMethod() !== 'OPTIONS') {
            return;
        }

        $methods = $this->getCorsMethodsByRequest($request, $this->container['routes']);

        if (0 === count($methods)) {
            $event->setResponse(
                Response::create('', Response::HTTP_METHOD_NOT_ALLOWED)
            );
        }

        $event->setResponse(
            Response::create('', Response::HTTP_NO_CONTENT, [
                'Allow' => implode(',', $methods)
            ])
        );
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $corsService = $this->container['cors'];

        $corsService->bindHeaders($event->getRequest(), $event->getResponse());
    }

    /**
     * @param Request $request
     * @param RouteCollection $routes
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     */
    private function getCorsMethodsByRequest(Request $request, RouteCollection $routes)
    {
        $methods = [];
        $pathInfo = $request->getPathInfo();

        /** @var \Silex\Route $route */
        foreach ($routes as $route) {
            $compiledRoute = $route->compile();

            if ('' !== $compiledRoute->getStaticPrefix() && 0 !== strpos($pathInfo,
                    $compiledRoute->getStaticPrefix())
            ) {
                continue;
            }

            if (!preg_match($compiledRoute->getRegex(), $pathInfo, $matches)) {
                continue;
            }

            $hostMatches = [];

            if ($compiledRoute->getHostRegex() && !preg_match($compiledRoute->getHostRegex(), $request->getHost(),
                    $hostMatches)
            ) {
                continue;
            }

            foreach ($route->getMethods() as $method) {
                if ($method === 'OPTIONS') {
                    continue;
                }

                $methods[] = $method;
            }
        }

        return $methods;
    }
}
