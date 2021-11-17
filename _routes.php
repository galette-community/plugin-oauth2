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

use GaletteOAuth2\Controllers\ApiController;
use GaletteOAuth2\Controllers\AuthorizationController;
use GaletteOAuth2\Controllers\LoginController;
use GaletteOAuth2\Middleware\Authentication;

//Include specific classes (league/oauth2_server and tools)
require_once 'vendor/autoload.php';

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

require_once '_dependencies.php';

//login is always called by a http_redirect
$this->map(['GET', 'POST'], '/login', [LoginController::class, 'login'])->setName(OAUTH2_PREFIX . '_login');
$this->map(['GET', 'POST'], '/logout', [LoginController::class, 'logout'])->setName(OAUTH2_PREFIX . '_logout');

$this->map(['GET', 'POST'], '/authorize', [AuthorizationController::class, 'authorize'])
    ->setName(OAUTH2_PREFIX . '_authorize')->add(Authentication::class);
$this->post('/access_token', [AuthorizationController::class, 'token']);

$this->get('/user', [ApiController::class, 'user'])->setName(OAUTH2_PREFIX . '_user');
