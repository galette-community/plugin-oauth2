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

namespace GaletteOAuth2\Tools;

use Monolog\Logger;

final class Debug
{
    private static $logger;

    public static function init()
    {
        self::$logger = new \Monolog\Logger('OAuth2');
        $stream = new \Monolog\Handler\StreamHandler(__DIR__ . '/../../../logs/app.log', Logger::DEBUG);
        $dateFormat = 'Y-m-d H:i:s';
        //$output = "[%datetime%] %channel% %level_name%: %message% \n"; // %context% %extra%\n";
        $output = "[%datetime%] : %message% \n"; // %context% %extra%\n";
        $formatter = new \Monolog\Formatter\LineFormatter($output, $dateFormat);
        $stream->setFormatter($formatter);
        self::$logger->pushHandler($stream);

        return self::$logger;
    }

    public static function printVar($expression, $return = true)
    {
        $export = \print_r($expression, true);
        $patterns = [
            '/array \\(/' => '[',
            '/^([ ]*)\\)(,?)$/m' => '$1]$2',
            "/=>[ ]?\n[ ]+\\[/" => '=> [',
            "/([ ]*)(\\'[^\\']+\\') => ([\\[\\'])/" => '$1$2 => $3',
        ];
        $export = \preg_replace(\array_keys($patterns), \array_values($patterns), $export);

        if ((bool) $return) {
            return $export;
        }
        echo $export;
    }

    public static function log(string $txt): void
    {
        if (null !== self::$logger) {
            self::$logger->info($txt);
        }
    }

    public static function logRequest($fct, $request): void
    {
        self::log("{$fct} :");
        self::log('URI : ' . $request->getUri());
        self::log('GET dump :' . self::printVar($request->getQueryParams()));
        self::log('POST dump :' . self::printVar((array) $request->getParsedBody()));
    }
}
