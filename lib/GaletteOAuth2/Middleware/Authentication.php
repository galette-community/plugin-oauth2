<?php

declare(strict_types=1);

/**
 * Plugin OAuth2 for Galette Project
 *
 *  PHP version 7
 *
 *  This file is part of 'Plugin OAuth2 for Galette Project'.
 *
 *  Plugin OAuth2 for Galette Project is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Plugin OAuth2 for Galette Project is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Plugin OAuth2 for Galette Project. If not, see <http://www.gnu.org/licenses/>.
 *
 *  @category Plugins
 *  @package  Plugin OAuth2 for Galette Project
 *
 *  @author    Manuel Hervouet <manuelh78dev@ik.me>
 *  @copyright Manuel Hervouet (c) 2021
 *  @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0
 */

namespace GaletteOAuth2\Middleware;

use GaletteOAuth2\Tools\Debug;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use DI\Container;
use RKA\Session;
use Slim\Routing\RouteParser;

final class Authentication
{
    private Container $container;
    private RouteParser $routeparser;
    private Session $session;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->routeparser = $container->get(RouteParser::class);
        $this->session = $container->get('session');
    }

    /**
     * Middleware invokable class
     *
     * @param Request        $request PSR7 request
     * @param RequestHandler $handler PSR7 request handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $loggedIn = $this->session->isLoggedIn ?? '';

        if ('yes' !== $loggedIn) {
            $url = $this->routeparser->urlFor(
                OAUTH2_PREFIX . '_login',
                [],
                ['redirect_url' => $_SERVER['REQUEST_URI']],
            );
            Debug::log("Redirect to {$url}");

            $response = new \Slim\Psr7\Response();
            // If the user is not logged in, redirect them to login
            return $response->withHeader('Location', $url)
                ->withStatus(302);
        }

        // The user must be logged in, so pass this request
        // down the middleware chain
        $response = $handler->handle($request);

        // And pass the request back up the middleware chain.
        return $response;
    }
}
