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

namespace GaletteOAuth2\Repositories;

use GaletteOAuth2\Entities\ScopeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

use function array_key_exists;

/**
 * Scope repository
 *
 * @author Manuel Hervouet <manuelh78dev@ik.me>
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
final class ScopeRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier($scopeIdentifier)
    {
        $scopes = [
            'basic' => [
                'description' => 'Basic details about you',
            ],
            'email' => [
                'description' => 'Your email address',
            ],
            'teamonly' => [
                'description' => 'Access to your team',
            ]
        ];

        if (array_key_exists($scopeIdentifier, $scopes) === false) {
            return null;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($scopeIdentifier);

        return $scope;
    }

    /**
     * {@inheritDoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        /*TODO : ?
                // Example of programmatically modifying the final scope of the access token
                if ((int) $userIdentifier === 1) {
                    $scope = new ScopeEntity();
                    $scope->setIdentifier('email');
                    $scopes[] = $scope;
                }
         */
        return $scopes;
    }
}
