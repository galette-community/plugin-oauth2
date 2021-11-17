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

use GaletteOAuth2\Tools\Debug as Debug;
//use Psr\Http\Server\RequestHandlerInterface as RequestHandler; //Slim 4
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;

final class Authentication
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    //public function __invoke(Request $request, RequestHandler $handler) //slim v4
    public function __invoke($request, $response, $next)
    {
        $loggedIn = $_SESSION['isLoggedIn'] ?? '';

        if ('yes' !== $loggedIn) {
            $url = $this->container->get('router')->pathFor(
                OAUTH2_PREFIX . '_login',
                [],
                ['redirect_url' => $_SERVER['REQUEST_URI']],
            );
            Debug::log("Redirect to {$url}");

            //$response = new Response();
            // If the user is not logged in, redirect them to login
            return $response->withHeader('Location', $url)
                ->withStatus(302);
        }

        return $next($request, $response);
        /* Slim v4
        // The user must be logged in, so pass this request
        // down the middleware chain
        $response = $handler->handle($request);

        // And pass the request back up the middleware chain.
        return $response;
         */
    }
}
