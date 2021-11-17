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

namespace GaletteOAuth2\Controllers;

use Galette\Controllers\AbstractPluginController;
use GaletteOAuth2\Authorization\UserAuthorizationException;
use GaletteOAuth2\Authorization\UserHelper;
use GaletteOAuth2\Tools\Config;
use GaletteOAuth2\Tools\Debug;
use League\OAuth2\Server\ResourceServer;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

final class ApiController extends AbstractPluginController
{
    /**
     * @Inject("Plugin Galette OAuth2")
     */
    protected $module_info;
    protected $container;
    protected $config;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(Config::class);
    }

    public function user(Request $request, Response $response): Response
    {
        Debug::logRequest('api/user()', $request);

        $server = $this->container->get(ResourceServer::class);
        $rep = $server->validateAuthenticatedRequest($request);

        $oauth_user_id = (int) $rep->getAttribute('oauth_user_id'); //SESSION is empty, use decrypted data
        $options = UserHelper::mergeOptions($this->config, $rep->getAttribute('oauth_client_id'), $rep->getAttribute('oauth_scopes'));

        Debug::log("api/user() load {$oauth_user_id} - " . Debug::printVar($options));

        try {
            $data = UserHelper::getUserData($this->container, $oauth_user_id, $options);
        } catch (UserAuthorizationException $e) {
            $r2 = new Response();
            $r2->getBody()->write($e->getMessage());
            Debug::log('api/user() error : ' . $e->getMessage());

            return $r2->withStatus(200);
        }

        Debug::log('api/user() return data = ' . Debug::printVar($data));

        $response->getBody()->write(\json_encode($data));
        Debug::log('api/user() exit.');

        return $response->withStatus(200);
    }
}
