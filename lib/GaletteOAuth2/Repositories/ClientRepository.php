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

final class ClientRepository implements ClientRepositoryInterface
{
    private Container $container;
    private Config $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->get(Config::class);
    }

    public function getClientEntity($clientIdentifier)
    {
        $client = new ClientEntity();
        $client->setIdentifier($this->config->get("{$clientIdentifier}.id", $clientIdentifier));
        $client->setName($clientIdentifier);
        $client->setRedirectUri($this->config->get("{$clientIdentifier}.redirect_uri"));
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
