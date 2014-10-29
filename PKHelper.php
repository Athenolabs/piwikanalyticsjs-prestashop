<?php

if (!defined('_PS_VERSION_'))
    exit;

/*
 * Copyright (C) 2014 Christian Jensen
 *
 * This file is part of PiwikAnalyticsJS for prestashop.
 * 
 * PiwikAnalyticsJS for prestashop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * PiwikAnalyticsJS for prestashop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with PiwikAnalyticsJS for prestashop.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link http://cmjnisse.github.io/piwikanalyticsjs-prestashop
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

class PKHelper {
    public static $errors = array();
    public static $error = "";
    protected static $_cachedResults = array();

    public static function getPiwikImageTrackingCode() {
        $ret = array(
            'default' => self::l('I need Site ID and Auth Token before i can get your image tracking code'),
            'proxy' => self::l('I need Site ID and Auth Token before i can get your image tracking code')
        );

        $idSite = (int) Configuration::get('PIWIK_SITEID');
        if (!self::baseTest() || ($idSite <= 0))
            return $ret;

        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getImageTrackingCode&format=JSON&actionName=NoJavaScript";
        $url .= "&piwikUrl=" . urlencode(rtrim(Configuration::get('PIWIK_HOST'), '/'));
        $md5Url = md5($url);
        if (!isset(self::$_cachedResults[$md5Url])) {
            if ($result = self::getAsJsonDecoded($url))
                self::$_cachedResults[$md5Url] = $result;
            else
                self::$_cachedResults[$md5Url] = false;
        }
        if (self::$_cachedResults[$md5Url] !== FALSE) {
            $ret['default'] = htmlentities('<noscript>' . self::$_cachedResults[$md5Url]->value . '</noscript>');
            if ((bool) Configuration::get('PS_REWRITING_SETTINGS'))
                $ret['proxy'] = str_replace(Configuration::get('PIWIK_HOST') . 'piwik.php', Configuration::get('PIWIK_PROXY_SCRIPT'), $ret['default']);
            else
                $ret['proxy'] = str_replace(Configuration::get('PIWIK_HOST') . 'piwik.php?', Configuration::get('PIWIK_PROXY_SCRIPT') . '&', $ret['default']);
        }
        return $ret;
    }

    /**
     * get Piwik site based on the current settings in the configuration
     * @return stdClass[]
     */
    public static function getPiwikSite() {
        $idSite = (int) Configuration::get('PIWIK_SITEID');
        if (!self::baseTest() || ($idSite <= 0))
            return false;

        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getSiteFromId&format=JSON";
        $md5Url = md5($url);
        if (!isset(self::$_cachedResults[$md5Url])) {
            if ($result = self::getAsJsonDecoded($url))
                self::$_cachedResults[$md5Url] = $result;
            else
                self::$_cachedResults[$md5Url] = false;
        }
        if (self::$_cachedResults[$md5Url] !== FALSE) {
            if (isset(self::$_cachedResults[$md5Url]->result) && self::$_cachedResults[$md5Url]->result == 'error') {
                self::$error = self::$_cachedResults[$md5Url]->message;
                self::$errors[] = self::$error;
                return false;
            }
            if (!isset(self::$_cachedResults[$md5Url][0])) {
                return false;
            }
            if ((bool) self::$_cachedResults[$md5Url][0]->ecommerce === false || self::$_cachedResults[$md5Url][0]->ecommerce == 0) {
                self::$error = self::l('E-commerce is not active for your site in piwik!, you can enable it in the advanced settings on this page');
                self::$errors[] = self::$error;
            }
            if ((bool) self::$_cachedResults[$md5Url][0]->sitesearch === false || self::$_cachedResults[$md5Url][0]->sitesearch == 0) {
                self::$error = self::l('Site search is not active for your site in piwik!, you can enable it in the advanced settings on this page');
                self::$errors[] = self::$error;
            }
            return self::$_cachedResults[$md5Url];
        }
        return false;
    }

    /**
     * get all supported time zones from piwik
     * @return array
     */
    public static function getTimezonesList() {
        if (!self::baseTest())
            return array();
        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getTimezonesList&format=JSON";
        $md5Url = md5($url);
        if (!isset(self::$_cachedResults[$md5Url])) {
            if ($result = self::getAsJsonDecoded($url))
                self::$_cachedResults[$md5Url] = $result;
            else
                self::$_cachedResults[$md5Url] = array();
        }
        return self::$_cachedResults[$md5Url];
    }

    /**
     * get all Piwik sites the current authentication token has admin access to
     * @return stdClass[]
     */
    public static function getMyPiwikSites($fetchAliasUrls = false) {
        if (!self::baseTest())
            return array();
        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getSitesWithAdminAccess&format=JSON" . ($fetchAliasUrls ? '&fetchAliasUrls=1' : '');
        $md5Url = md5($url);
        if (!isset(self::$_cachedResults[$md5Url])) {
            if ($result = self::getAsJsonDecoded($url))
                self::$_cachedResults[$md5Url] = $result;
            else
                self::$_cachedResults[$md5Url] = array();
        }
        return self::$_cachedResults[$md5Url];
    }

    /**
     * get all Piwik siteIDs the current authentication token has admin access to
     * @return array
     */
    public static function getMyPiwikSiteIds() {
        if (!self::baseTest())
            return array();
        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getSitesIdWithAdminAccess&format=JSON";
        $md5Url = md5($url);
        if (!isset(self::$_cachedResults[$md5Url])) {
            if ($result = self::getAsJsonDecoded($url))
                self::$_cachedResults[$md5Url] = $result;
            else
                self::$_cachedResults[$md5Url] = array();
        }
        return self::$_cachedResults[$md5Url];
    }

    /**
     * get the base url for all requests to Piwik
     * @param integer $idSite
     * @param string $pkHost
     * @param boolean $https
     * @param string $pkModule
     * @param string $isoCode
     * @param string $tokenAuth
     * @return string
     */
    protected static function getBaseURL($idSite = NULL, $pkHost = NULL, $https = NULL, $pkModule = 'API', $isoCode = NULL, $tokenAuth = NULL) {
        if ($https === NULL)
            $https = (bool) Configuration::get('PIWIK_CRHTTPS');
        if ($pkHost === NULL)
            $pkHost = Configuration::get('PIWIK_HOST');
        if ($isoCode === NULL)
            $isoCode = Context::getContext()->language->iso_code;
        if ($idSite === NULL)
            $idSite = Configuration::get('PIWIK_SITEID');
        if ($tokenAuth === NULL)
            $tokenAuth = Configuration::get('PIWIK_TOKEN_AUTH');
        return ($https ? 'https' : 'http') . "://{$pkHost}index.php?module={$pkModule}&language={$isoCode}&idSite={$idSite}&token_auth={$tokenAuth}";
    }

    /**
     * check if the basics are there before we make any piwik requests
     * @return boolean
     */
    protected static function baseTest() {
        static $_error1 = FALSE;
        $pkToken = Configuration::get('PIWIK_TOKEN_AUTH');
        $pkHost = Configuration::get('PIWIK_HOST');
        if (empty($pkToken) || empty($pkHost)) {
            if (!$_error1) {
                self::$error = self::l('Piwik auth token and/or Piwik site id cannot be empty');
                self::$errors[] = self::$error;
                $_error1 = TRUE;
            }
            return false;
        }
        return true;
    }

    /**
     * get output of api as json decoded object
     * @param string $url the full http(s) url to use for fetching the api result
     * @return boolean
     */
    protected static function getAsJsonDecoded($url) {
        $options = array(
            'http' => array(
                'method' => "GET",
                'header' => "Accept-language: en\r\n" .
                (isset($_SERVER['HTTP_USER_AGENT']) ? "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n" : '')
            /* tested on server that denied empty(or php default) user agent so set it to browser */
            )
        );

        $context = stream_context_create($options);
        $getF = @file_get_contents($url, false, $context);
        if ($getF !== FALSE) {
            return Tools::jsonDecode($getF);
        }
        $http_response = "";
        foreach ($http_response_header as $value) {
            if (preg_match("/^HTTP\/.*", $value)) {
                $http_response = ':' . $value;
            }
        }
        self::$error = sprintf(self::l('Unable to connect to api %s'), $http_response);
        self::$errors[] = self::$error;
        return FALSE;
    }

    /**
     * @see Module::l
     */
    private static function l($string, $specific = false) {
        return Translate::getModuleTranslation('piwikanalyticsjs', $string, ($specific) ? $specific : 'piwikanalyticsjs');
    }

}