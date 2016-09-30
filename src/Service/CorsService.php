<?php

namespace Devim\Provider\CorsServiceProvider\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CorsService
 */
class CorsService
{
    /**
     * @var array
     */
    private $exposeHeaders;

    /**
     * @var string
     */
    private $allowOrigin;

    /**
     * @var array
     */
    private $allowMethods;

    /**
     * @var array
     */
    private $allowCredentials;

    /**
     * @var int
     */
    private $maxAge;

    /**
     * CorsService constructor.
     *
     * @param string $exposeHeaders
     * @param string $allowOrigin
     * @param array $allowMethods
     * @param bool $allowCredentials
     * @param int $maxAge
     */
    public function __construct(
        string $exposeHeaders,
        string $allowOrigin,
        array $allowMethods,
        bool $allowCredentials,
        int $maxAge
    ) {
        $this->exposeHeaders = $exposeHeaders;
        $this->allowOrigin = $allowOrigin;
        $this->allowMethods = $allowMethods;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function bindHeaders(Request $request, Response $response)
    {
        $response->headers->add($this->corsHeaders($request, $response->headers->get('Allow')));
    }

    /**
     * @param Request $request
     * @param $allow
     *
     * @return array
     */
    private function corsHeaders(Request $request, $allow)
    {
        $headers = [];

        if (!$this->isCorsRequest($request)) {
            return [];
        }

        if ($this->isPreFlightRequest($request)) {
            $allowedMethods = $this->allowedMethods($allow);
            $requestMethod = $request->headers->get('Access-Control-Request-Method');

            if (!in_array($requestMethod, preg_split('/\s*,\s*/', $allowedMethods))) {
                return [];
            }

            $headers['Access-Control-Allow-Headers'] = $request->headers->get('Access-Control-Request-Headers');
            $headers['Access-Control-Allow-Methods'] = $allowedMethods;
            $headers['Access-Control-Max-Age'] = $this->maxAge;
        } else {
            $headers['Access-Control-Expose-Headers'] = $this->exposeHeaders;
        }

        $headers['Access-Control-Allow-Origin'] = $this->allowOrigin($request);
        $headers['Access-Control-Allow-Credentials'] = $this->allowCredentials();

        return array_filter($headers);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function isCorsRequest(Request $request)
    {
        return $request->headers->has('Origin');
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function isPreFlightRequest(Request $request)
    {
        return $request->getMethod() === 'OPTIONS' && $request->headers->has('Access-Control-Request-Method');
    }

    /**
     * @param $allow
     *
     * @return mixed
     */
    private function allowedMethods($allow)
    {
        return !empty($this->allowMethods) ? $this->allowMethods : $allow;
    }

    /**
     * @param Request $request
     *
     * @return array|string
     */
    private function allowOrigin(Request $request)
    {
        if ($this->allowOrigin === '*') {
            $this->allowOrigin = null;
        }

        $origin = $request->headers->get('Origin');

        if ($this->allowOrigin === null) {
            $this->allowOrigin = $origin;
        }

        return in_array($origin, preg_split('/\s+/', $this->allowOrigin)) ? $origin : 'null';
    }

    /**
     * @return null|string
     */
    private function allowCredentials()
    {
        return $this->allowCredentials === true ? 'true' : null;
    }
}
