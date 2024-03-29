<?php

/**
 * Copyright © 2021-2024 The Galette Team
 *
 * This file is part of Galette OAuth2 plugin (https://galette-community.github.io/plugin-oauth2/).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette OAuth2 plugin. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace GaletteOAuth2\Controllers;

use DI\Attribute\Inject;
use DI\Container;
use Galette\Controllers\AbstractPluginController;
use GaletteOAuth2\Authorization\UserAuthorizationException;
use GaletteOAuth2\Authorization\UserHelper;
use GaletteOAuth2\Tools\Config;
use GaletteOAuth2\Tools\Debug;
use League\OAuth2\Server\ResourceServer;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * Controller for API
 *
 * @author Manuel Hervouet <manuelh78dev@ik.me>
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
final class ApiController extends AbstractPluginController
{
    /**
     * @var array<string, mixed>
     */
    #[Inject("Plugin Galette OAuth2")]
    protected array $module_info;
    protected Container $container;
    protected Config $config;

    // constructor receives container instance
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Config::class);
        parent::__construct($container);
    }

    public function user(Request $request, Response $response): Response
    {
        Debug::logRequest('api/user()', $request);

        $server = $this->container->get(ResourceServer::class);
        $rep = $server->validateAuthenticatedRequest($request);

        $oauth_user_id = (int) $rep->getAttribute('oauth_user_id'); //SESSION is empty, use decrypted data
        $options = UserHelper::mergeOptions($this->config, $rep->getAttribute('oauth_client_id'), $rep->getAttribute('oauth_scopes'));

        Debug::log("api/user() load user #{$oauth_user_id} - " . Debug::printVar($options));

        try {
            $data = UserHelper::getUserData($this->container, $oauth_user_id, $options);
        } catch (UserAuthorizationException $e) {
            throw $e;
            //UserHelper::logout($this->container);
            /*Analog::log(
                'api/user() error : ' . $e->getMessage(),
                Analog::ERROR
            );
            $this->flash->addMessage(
                'error_detected',
                _T('Check your login / email or password.', 'oauth2')
            );
            return $response
                ->withStatus(301)
                ->withHeader(
                    'Location',
                    $this->routeparser->urlFor(OAUTH2_PREFIX . '_login')
                );*/
        }

        Debug::log('api/user() return data = ' . Debug::printVar($data));

        $response->getBody()->write(json_encode($data));
        Debug::log('api/user() exit.');

        return $response->withStatus(200);
    }
}
