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

namespace GaletteOAuth2\Repositories;

use DI\Container;
use GaletteOAuth2\Entities\ClientEntity;
use GaletteOAuth2\Tools\Config;
use GaletteOAuth2\Tools\Debug;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use RKA\Session;

final class ClientRepository implements ClientRepositoryInterface
{
    private Container $container;
    private Config $config;
    private Session $session;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->get(Config::class);
        $this->session = $this->container->get('session');
    }

    public function getClientEntity($client_id)
    {
        $client = new ClientEntity();
        $client->setIdentifier($this->config->get("{$client_id}.id", $client_id));
        $client->setName($client_id);
        if (isset($this->session->$client_id)) {
            $redirect_uri = $this->session->$client_id->redirect_uri;
        } else {
            $filename = OAUTH2_PREFIX . '_' . $client_id . '.redirect_uri.txt';
            $redirect_uri = file_get_contents(GALETTE_CACHE_DIR . '/' . $filename);
        }
        $cid = $this->config->get("{$client_id}.redirect_uri");
        /*$redirect_uri = $this->config->get("{$clientIdentifier}.redirect_uri");
        if (empty($redirect_uri)) {
            $filename = OAUTH2_PREFIX . '_' . $clientIdentifier . '.redirect_uri.txt';
            $redirect_uri = file_get_contents(GALETTE_CACHE_DIR . '/' . $filename);
        }*/
        $client->setRedirectUri($redirect_uri);
        $client->setConfidential();

        Debug::log('getClientEntity() ' . Debug::printVar($client));

        return $client;
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        if (!preg_match('/galette_/', $clientIdentifier)) {
            Debug::log("validateClient({$clientIdentifier}) denied");

            return false;
        }

        $pwd = password_hash($this->config->get('global.password'), PASSWORD_BCRYPT);

        if (password_verify($clientSecret, $pwd) === false) {
            return false;
        }

        return true;
    }
}
