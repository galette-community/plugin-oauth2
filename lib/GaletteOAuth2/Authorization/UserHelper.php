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

namespace GaletteOAuth2\Authorization;

use Galette\Entity\Adherent;
use GaletteOAuth2\Tools\Debug as Debug;

final class UserHelper
{
    public static function login($container, $nick, $password): int
    {
        $preferences = $container->get('preferences');
        $login = $container->get('login');
        $history = $container->get('history');
        $session = $container->get('session');

        if (\trim($nick) === '' || \trim($password) === '') {
            return 0;
        }

        if ($nick === $preferences->pref_admin_login) {
            $pw_superadmin = \password_verify(
                $password,
                $preferences->pref_admin_pass,
            );

            if (!$pw_superadmin) {
                $pw_superadmin = (
                    \md5($password) === $preferences->pref_admin_pass
                );
            }

            if ($pw_superadmin) {
                $login->logAdmin($nick, $preferences);
            }
        } else {
            $login->logIn($nick, $password);
        }

        if ($login->isLogged()) {
            $session->login = $login;
            $history->add(_T('Login'));

            return $login->id;
        }
        $history->add(_T('Authentication failed'), $nick);

        return 0;
    }

    public static function logout($container): void
    {
        $login = $container->get('login');
        $history = $container->get('history');
        $session = $container->get('session');

        $login->logout();
        $session->login = $login;
        $history->add(_T('Logout'));
    }

    public static function getUserData($container, $id, array $options)
    {
        $zdb = $container->get('zdb');
        $login = $container->get('login');

        $member = new Adherent($zdb);
        $member->load($id);

        $member->name = \ucwords(\mb_strtolower($member->name));
        $member->surname = \ucwords(\mb_strtolower($member->surname));

        $norm_name = $member->surname . ' ' . \mb_strtoupper($member->name);
        $norm_pseudo = \mb_strtolower($member->nickname);

        $nameExplode = \preg_split('/[\\s,-]+/', $member->name);
        //$surnameExplode = \preg_split('/[\\s,-]+/', $member->surname);

        if (\count($nameExplode) > 0) {
            $nameFPart = $nameExplode[0];
            //too short?
            if (\mb_strlen($nameFPart) < 4 && \count($nameExplode) > 1) {
                $nameFPart .= $nameExplode[1];
            }
        } else {
            $nameFPart = $member->name;
        }

        //Normalized format s.name (example mail usage : s.name@xxxx.xx )
        $norm_login =
        \mb_substr(self::stripAccents(\mb_strtolower($member->surname)), 0, 1) .
        '.' .
        self::stripAccents(\mb_strtolower($nameFPart));

        //strtolower(($prenoms != '' ? static::stripAccents($prenoms[0]) : '') . "." . $member->name[0]);

        $etat_adhesion = ($member->isActive() && $member->isUp2Date()) || $member->isAdmin();

        //for options=
        //teamonly
        if (\in_array('teamonly', $options, true)) {
            if (!$member->isAdmin() && !$member->isStaff() && !$login->isGroupManager()) {
                throw new UserAuthorizationException(_T("Sorry, you can't login because your are not a team member.", 'oauth2'));
            }
        }
        //uptodate
        if (\in_array('uptodate', $options, true)) {
            if (!$member->isUp2Date()) {
                throw new UserAuthorizationException(_T("Sorry, you can't login because your are not a up-to-date members.", 'oauth2'));
            }
        }

        //Groups list for Nextcloud
        $groups = [$member->sstatus]; //first group is the member status

        if ($member->isAdmin()) {
            $groups[] = 'admin';
        }

        if ($member->isStaff()) {
            $groups[] = 'staff';
        }

        if ($login->isGroupManager()) {
            $groups[] = 'groupmanager';
        }

        if ($member->isUp2Date()) {
            $groups[] = 'uptodate';
        }

        if (!$member->isActive()) {
            throw new UserAuthorizationException(_T('You are not an active member.', 'oauth2'));
        }

        //Add externals groups (free text in info_adh)
        //Example #GROUPS:compta;accueil#
        if (\preg_match('/#GROUPS:([^#]*([^#]*))#/mui', $member->others_infos_admin, $matches, \PREG_OFFSET_CAPTURE)) {
            $g = $matches[1][0];
            Debug::log("Groups added {$g}");
            $groups = \array_merge($groups, \explode(';', $g));
        }

        //Reformat group with strtolower, remove whites & slashs
        foreach ($groups as &$group) {
            $group = \trim($group);
            $group = \str_replace([' ', '/', '(', ')'], ['_', '', '', ''], $group);
            $group = \str_replace('__', '_', $group);
            $group = \mb_strtolower(self::stripAccents($group));
        }
        $groups = \implode(',', $groups);

        return [
            'id' => $member->id,
            'identifier' => $member->id, //nextcloud
            'displayName' => $norm_name,
            'username' => $norm_login,
            'userName' => $norm_login,
            'name' => $norm_login,
            'email' => $member->email,
            'mail' => $member->email,
            'language' => $member->language,

            'country' => $member->country,
            'zip' => $member->zipcode,
            'city' => $member->town,
            'phone' => $member->phone . '/' . $member->gsm,

            'status' => $member->status,
            'state' => $etat_adhesion ? 'true' : 'false',
            'groups' => $groups, //nextcloud : set fields Groups claim (optional) = groups
        ];
    }

    //merge oauth_scopes with user config.yml client_id.options
    public static function mergeOptions($config, $client_id, array $oauth_scopes)
    {
        $options = $oauth_scopes;
        $o = $config->get("{$client_id}.options");

        if ($o) {
            $o = \str_replace(';', ' ', $o);
            $o = \explode(' ', $o);
            $options = \array_merge($o, $options);
        }
        $options = \array_unique($options);
        Debug::Log('Options: ' . \implode(';', $options));

        return $options;
    }

    // Nextcloud datas :
    // \DBG = Hybridauth\User\Profile::__set_state(array(
    // 'identifier' => 3992, 'webSiteURL' => NULL, 'profileURL' => NULL,
    // 'photoURL' => NULL,
    // 'displayName' => ' TEST', 'description' => NULL, 'firstName' => NULL, 'lastName' => NULL, 'gender' => NULL,
    // 'language' => NULL,
    // 'age' => NULL, 'birthDay' => NULL, 'birthMonth' => NULL, 'birthYear' => NULL,
    // 'email' => 'uuuu@ik.me', 'emailVerified' => NULL, 'phone' => NULL,
    // 'address' => NULL, 'country' => NULL, 'region' => NULL, 'city' => NULL, 'zip' => NULL

    private static function stripAccents($str)
    {
        return \strtr(\utf8_decode($str), \utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïñòóôõöøùúûüýÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬĭĮįİıĲĳĴĵĶķĹĺĻļĽľĿŀŁłŃńŅņŇňŉŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſƒƠơƯưǍǎǏǐǑǒǓǔǕǖǗǘǙǚǛǜǺǻǼǽǾǿ'), 'AAAAAAAECEEEEIIIIDNOOOOOOUUUUYsaaaaaaaeceeeeiiiinoooooouuuuyyAaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIJijJjKkLlLlLlLlllNnNnNnnOoOoOoOEoeRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuWwYyYZzZzZzsfOoUuAaIiOoUuUuUuUuUuAaAEaeOo');
    }
}
