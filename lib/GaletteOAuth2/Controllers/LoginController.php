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

use DI\Attribute\Inject;
use DI\Container;
use Galette\Controllers\AbstractPluginController;
use GaletteOAuth2\Authorization\UserAuthorizationException;
use GaletteOAuth2\Authorization\UserHelper;
//use Slim\Views\Twig;
//use Slim\Routing\RouteContext;
use GaletteOAuth2\Tools\Config;
use GaletteOAuth2\Tools\Debug;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

final class LoginController extends AbstractPluginController
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
        $this->config = $this->container->get(Config::class);
        parent::__construct($container);
    }

    public function login(Request $request, Response $response): Response
    {
        Debug::logRequest('login()', $request);

        if ($request->getMethod() === 'GET') {
            $redirect_url = $request->getQueryParams()['redirect_url'] ?? false;

            if ($redirect_url) {
                $url = urldecode($redirect_url);
                $url_query = parse_url($url, PHP_URL_QUERY);
                parse_str($url_query, $url_args);
                $_SESSION['request_args'] = $url_args;
            }

            if (OAUTH2_DEBUGSESSION) {
                Debug::log('GET _SESSION = ' . Debug::printVar($_SESSION));
            }

            // display page
            $this->view->render(
                $response,
                $this->getTemplate(OAUTH2_PREFIX . '_login'),
                $this->prepareVarsForm()
            );
            return $response;
        }

        if (OAUTH2_DEBUGSESSION) {
            Debug::log('POST _SESSION = ' . Debug::printVar($_SESSION));
        }

        // Get all POST parameters
        $params = (array) $request->getParsedBody();

        //Try login
        $_SESSION['isLoggedIn'] = 'no';
        $_SESSION['user_id'] = $uid = UserHelper::login($this->container, $params['login'], $params['password']);
        //if($params['login'] == 'manuel') $loginSuccessful = true;
        Debug::log("UserHelper::login({$params['login']}) return '{$uid}'");

        if (false === $uid) {
            $this->flash->addMessage(
                'error_detected',
                _T('Check your login / email or password.', 'oauth2')
            );
            return $response
                ->withStatus(301)
                ->withHeader(
                    'Location',
                    $this->routeparser->urlFor(OAUTH2_PREFIX . '_login')
                );
        }

        //check rights with scopes
        $options = UserHelper::mergeOptions(
            $this->config,
            $_SESSION['request_args']['client_id'],
            explode(' ', $_SESSION['request_args']['scope'])
        );

        try {
            UserHelper::getUserData($this->container, $uid, $options);
        } catch (UserAuthorizationException $e) {
            UserHelper::logout($this->container);
            Debug::log('login() check rights error ' . $e->getMessage());

            $this->flash->addMessage(
                'error_detected',
                $e->getMessage()
            );
            return $response
                ->withStatus(301)
                ->withHeader(
                    'Location',
                    $this->routeparser->urlFor(OAUTH2_PREFIX . '_login')
                );
        }

        $_SESSION['isLoggedIn'] = 'yes';

        // User is logged in, redirect them to authorize
        $url_params = [
            'response_type' => $_SESSION['request_args']['response_type'],
            'client_id' => $_SESSION['request_args']['client_id'],
            'scope' => $_SESSION['request_args']['scope'],
            'state' => $_SESSION['request_args']['state'],
            'redirect_uri' => $_SESSION['request_args']['redirect_uri'],
        ];

        //$url = $this->routeparser->pathFor(OAUTH2_PREFIX . '_authorize', [], $url_params);
        $url = $this->routeparser->urlFor(OAUTH2_PREFIX . '_authorize', [], $url_params);

        $response = new Response();

        return $response->withHeader('Location', $url)
            ->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        Debug::logRequest('logout()', $request);
        UserHelper::logout($this->container);

        $_SESSION['user_id'] = null;
        $_SESSION['isLoggedIn'] = 'no';
        $client_id = $_SESSION['request_args']['client_id'];
        $_SESSION['request_args'] = [];

        //By default : client_id.redirect_logout else '/'
        $redirect_logout = '/';

        if ($client_id) {
            $redirect_logout = $this->config->get("{$client_id}.redirect_logout", $redirect_logout);
        }

        Debug::log("logout():url_logout for client:'{$client_id}' = '{$redirect_logout}'");

        //Add an url redirection in config.yml : $client_id:   redirect_logout:"https:\\xxx");
        return $response->withHeader('Location', $redirect_logout)->withStatus(302);
    }

    private function prepareVarsForm()
    {
        $client_id = $_SESSION['request_args']['client_id'];
        $application = $this->config->get("{$client_id}.title", 'noname');
        $page_title = _T('Please sign in for', OAUTH2_PREFIX) . " '{$application}'";

        return [
            'page_title' => $page_title,
            'application' => $application,
            'prefix' => OAUTH2_PREFIX,
            //TODO:
            'path_css' => $this->routeparser->pathFor('slash') . '../plugins/plugin-oauth2/webroot/',
        ];
    }
}
