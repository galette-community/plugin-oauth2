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

use GaletteOAuth2\Entities\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

final class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        // Some logic to persist the auth code to a database
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAuthCode($codeId): void
    {
        // Some logic to revoke the auth code in a database
    }

    /**
     * {@inheritDoc}
     */
    public function isAuthCodeRevoked($codeId)
    {
        return false; // The auth code has not been revoked
    }

    /**
     * {@inheritDoc}
     */
    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }
}
