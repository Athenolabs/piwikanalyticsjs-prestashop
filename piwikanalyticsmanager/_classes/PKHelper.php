<?php

if (!defined('_PS_VERSION_'))
    exit;

if (class_exists('PKHelper', FALSE))
    return;

/*
 * Copyright (C) 2014-2015 Christian Jensen
 *
 * This file is part of PiwikAnalyticsManager for prestashop.
 * 
 * PiwikAnalyticsManager for prestashop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * PiwikAnalyticsManager for prestashop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with PiwikAnalyticsManager for prestashop.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link http://cmjnisse.github.io/piwikanalyticsjs-prestashop
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

class PKHelper {

    public static $acp = array(
        'updatePiwikSite' => array(
            'required' => array('idSite'),
            'optional' => array('siteName', 'urls', 'ecommerce', 'siteSearch', 'searchKeywordParameters', 'searchCategoryParameters', 'excludedIps', 'excludedQueryParameters', 'timezone', 'currency', 'group', 'startDate', 'excludedUserAgents', 'keepURLFragments', 'type'),
            'order' => array('idSite', 'siteName', 'urls', 'ecommerce', 'siteSearch', 'searchKeywordParameters', 'searchCategoryParameters', 'excludedIps', 'excludedQueryParameters', 'timezone', 'currency', 'group', 'startDate', 'excludedUserAgents', 'keepURLFragments', 'type'),
        ),
        'getPiwikSite' => array('required' => array('idSite'), 'optional' => array(''), 'order' => array('idSite'),),
        'getPiwikSite2' => array('required' => array('idSite'), 'optional' => array(''), 'order' => array('idSite'),),
        'getSitesGroups' => array('required' => array(), 'optional' => array(), 'order' => array(),),
    );

    /**
     * all errors isset by class PKHelper
     * @var string[] 
     */
    public static $errors = array();

    /**
     * last isset error by class PKHelper
     * @var string
     */
    public static $error = "";
    protected static $_cachedResults = array();

    /**
     * for Prestashop 1.4 translation
     * @var piwikanalyticsjs|piwikanalyticsmanager
     */
    public static $_module = null;

    /**
     * prefix to use for configurations values
     */
    const CPREFIX = "PIWIK_";

    /**
     * 
     * @param type $idSite
     * @param type $siteName
     * @param array $urls
     * @param type $ecommerce
     * @param type $siteSearch
     * @param type $searchKeywordParameters
     * @param type $searchCategoryParameters
     * @param type $excludedIps
     * @param type $excludedQueryParameters
     * @param type $timezone
     * @param type $currency
     * @param type $group
     * @param type $startDate
     * @param type $excludedUserAgents
     * @param type $keepURLFragments
     * @param type $type
     * @return boolean
     */
    public static function updatePiwikSite($idSite, $siteName = NULL, $urls = NULL, $ecommerce = NULL, $siteSearch = NULL, $searchKeywordParameters = NULL, $searchCategoryParameters = NULL, $excludedIps = NULL, $excludedQueryParameters = NULL, $timezone = NULL, $currency = NULL, $group = NULL, $startDate = NULL, $excludedUserAgents = NULL, $keepURLFragments = NULL, $type = NULL) {
        if (!self::baseTest() || ($idSite <= 0))
            return false;
        $url = self::getBaseURL($idSite);
        $url .= "&method=SitesManager.updateSite&format=JSON";
        if ($siteName !== NULL)
            $url .= "&siteName=" . urlencode($siteName);

        if ($urls !== NULL) {
            foreach (explode(',', $urls) as $value) {
                $url .= "&urls[]=" . urlencode(trim($value));
            }
        }
        if ($ecommerce !== NULL)
            $url .= "&ecommerce=" . urlencode($ecommerce);
        if ($siteSearch !== NULL)
            $url .= "&siteSearch=" . urlencode($siteSearch);
        if ($searchKeywordParameters !== NULL)
            $url .= "&searchKeywordParameters=" . urlencode($searchKeywordParameters);
        if ($searchCategoryParameters !== NULL)
            $url .= "&searchCategoryParameters=" . urlencode($searchCategoryParameters);
        if ($excludedIps !== NULL)
            $url .= "&excludedIps=" . urlencode($excludedIps);
        if ($excludedQueryParameters !== NULL)
            $url .= "&excludedQueryParameters=" . urlencode($excludedQueryParameters);
        if ($timezone !== NULL)
            $url .= "&timezone=" . urlencode($timezone);
        if ($currency !== NULL)
            $url .= "&currency=" . urlencode($currency);
        if ($group !== NULL)
            $url .= "&group=" . urlencode($group);
        if ($startDate !== NULL)
            $url .= "&startDate=" . urlencode($startDate);
        if ($excludedUserAgents !== NULL)
            $url .= "&excludedUserAgents=" . urlencode($excludedUserAgents);
        if ($keepURLFragments !== NULL)
            $url .= "&keepURLFragments=" . urlencode($keepURLFragments);
        if ($type !== NULL)
            $url .= "&type=" . urlencode($type);
        $md5Url = md5($url);
        if ($result = self::getAsJsonDecoded($url)) {
            $url2 = self::getBaseURL($idSite) . "&method=SitesManager.getSiteFromId&format=JSON";
            unset(self::$_cachedResults[md5($url2)]); // Clear cache for updated site
            return ($result->result == 'success' && $result->message == 'ok' ? TRUE : ($result->result != 'success' ? $result->message : FALSE));
        } else
            return FALSE;
    }

    /**
     * get all website groups
     * @return array|boolean
     */
    public static function getSitesGroups() {
        if (!self::baseTest())
            return FALSE;
        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getSitesGroups&format=JSON";
        if ($result = self::getAsJsonDecoded($url))
            return $result;
        else
            return FALSE;
    }

    /**
     * get image tracking code for use with or without proxy script
     * @return array
     */
    public static function getPiwikImageTrackingCode() {
        $ret = array(
            'default' => self::l('I need Site ID and Auth Token before i can get your image tracking code'),
            'proxy' => self::l('I need Site ID and Auth Token before i can get your image tracking code')
        );

        $idSite = (int) Configuration::get(PKHelper::CPREFIX . 'SITEID');
        if (!self::baseTest() || ($idSite <= 0))
            return $ret;

        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getImageTrackingCode&format=JSON&actionName=NoJavaScript";
        $url .= "&piwikUrl=" . urlencode(rtrim(Configuration::get(PKHelper::CPREFIX . 'HOST'), '/'));
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
                $ret['proxy'] = str_replace(Configuration::get(PKHelper::CPREFIX . 'HOST') . 'piwik.php', Configuration::get(PKHelper::CPREFIX . 'PROXY_SCRIPT'), $ret['default']);
            else
                $ret['proxy'] = str_replace(Configuration::get(PKHelper::CPREFIX . 'HOST') . 'piwik.php?', Configuration::get(PKHelper::CPREFIX . 'PROXY_SCRIPT') . '&', $ret['default']);
        }
        return $ret;
    }

    public static function getPiwikSite2($idSite = 0) {
        if ($idSite == 0)
            $idSite = (int) Configuration::get(PKHelper::CPREFIX . 'SITEID');
        if ($result = self::getPiwikSite($idSite)) {
            $url = self::getBaseURL($idSite);
            $url .= "&method=SitesManager.getSiteUrlsFromId&format=JSON";
            if ($resultUrls = self::getAsJsonDecoded($url)) {
                $result[0]->main_url = implode(',', $resultUrls);
            }
            return $result;
        }
        return false;
    }

    /**
     * get Piwik site based on the current settings in the configuration
     * @return stdClass[]
     */
    public static function getPiwikSite($idSite = 0) {
        if ($idSite == 0)
            $idSite = (int) Configuration::get(PKHelper::CPREFIX . 'SITEID');
        if (!self::baseTest() || ($idSite <= 0))
            return false;

        $url = self::getBaseURL($idSite);
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
                if ((_PS_VERSION_ < '1.5'))
                    self::$error = self::l('E-commerce is not active for your site in piwik!');
                else
                    self::$error = self::l('E-commerce is not active for your site in piwik!, you can enable it in the advanced settings on this page');
                self::$errors[] = self::$error;
            }
            if ((bool) self::$_cachedResults[$md5Url][0]->sitesearch === false || self::$_cachedResults[$md5Url][0]->sitesearch == 0) {
                if ((_PS_VERSION_ < '1.5'))
                    self::$error = self::l('Site search is not active for your site in piwik!');
                else
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
            $https = (bool) Configuration::get(PKHelper::CPREFIX . 'CRHTTPS');
        if ($pkHost === NULL)
            $pkHost = Configuration::get(PKHelper::CPREFIX . 'HOST');
        if ($isoCode === NULL)
            $isoCode = strtolower((isset(Context::getContext()->language->iso_code) ? Context::getContext()->language->iso_code : 'en'));
        if ($idSite === NULL)
            $idSite = Configuration::get(PKHelper::CPREFIX . 'SITEID');
        if ($tokenAuth === NULL)
            $tokenAuth = Configuration::get(PKHelper::CPREFIX . 'TOKEN_AUTH');
        return ($https ? 'https' : 'http') . "://{$pkHost}index.php?module={$pkModule}&language={$isoCode}&idSite={$idSite}&token_auth={$tokenAuth}";
    }

    /**
     * check if the basics are there before we make any piwik requests
     * @return boolean
     */
    protected static function baseTest() {
        static $_error1 = FALSE;
        $pkToken = Configuration::get(PKHelper::CPREFIX . 'TOKEN_AUTH');
        $pkHost = Configuration::get(PKHelper::CPREFIX . 'HOST');
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
        static $_error2 = FALSE;
        $lng = strtolower((isset(Context::getContext()->language->iso_code) ? Context::getContext()->language->iso_code : 'en'));

        $httpauth = "";
        $httpauth_usr = Configuration::get(PKHelper::CPREFIX . 'PAUTHUSR');
        $httpauth_pwd = Configuration::get(PKHelper::CPREFIX . 'PAUTHPWD');
        if ((!empty($httpauth_usr) && !is_null($httpauth_usr) && $httpauth_usr !== false) && (!empty($httpauth_pwd) && !is_null($httpauth_pwd) && $httpauth_pwd !== false)) {
            $httpauth = "Authorization: Basic " . base64_encode("$httpauth_usr:$httpauth_pwd") . "\r\n";
        }

        $options = array(
            'http' => array(
                'user_agent' => (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''),
                'method' => "GET",
                'header' => "Accept-language: {$lng}\r\n" . $httpauth
            )
        );

        $context = stream_context_create($options);
        $getF = @file_get_contents($url, false, $context);
        if ($getF !== FALSE) {
            return Tools::jsonDecode($getF);
        }
        $http_response = "";
        foreach ($http_response_header as $value) {
            if (preg_match("/^HTTP\/.*/i", $value)) {
                $http_response = ':' . $value;
            }
        }
        if (!$_error2) {
            self::$error = sprintf(self::l('Unable to connect to api %s'), $http_response);
            self::$errors[] = self::$error;
            $_error2 = TRUE;
        }
        return FALSE;
    }

    /**
     * @see Module::l
     */
    private static function l($string, $specific = false) {
        if (_PS_VERSION_ < '1.5')
            return PKHelper::$_module->l($string, ($specific) ? $specific : 'pkhelper');
        return Translate::getModuleTranslation('piwikanalyticsmanager', $string, ($specific) ? $specific : 'pkhelper');
        // the following lines are need for the translation to work properly
        // $this->l('I need Site ID and Auth Token before i can get your image tracking code')
        // $this->l('E-commerce is not active for your site in piwik!, you can enable it in the advanced settings on this page')
        // $this->l('Site search is not active for your site in piwik!, you can enable it in the advanced settings on this page')
        // $this->l('Unable to connect to api %s')
        // $this->l('E-commerce is not active for your site in piwik!')
        // $this->l('Site search is not active for your site in piwik!')
    }

    /**
     * get websites by group
     * NOTE: Not tested not in use by this module but here for the future, and may be removed.!
     * @param string $group
     * @return array|boolean
     */
    public static function getSitesFromGroup($group) {
        if (!self::baseTest())
            return FALSE;
        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getSitesFromGroup&format=JSON&group=" . urlencode($group);
        if ($result = self::getAsJsonDecoded($url))
            return $result;
        else
            return FALSE;
    }

    /**
     * rename websites group
     * NOTE: Not tested not in use by this module but here for the future, and may be removed.!
     * @param string $oldGroupName
     * @param string $newGroupName
     * @return array|boolean
     */
    public static function renameGroup($oldGroupName, $newGroupName) {
        if (!self::baseTest())
            return FALSE;
        $url = self::getBaseURL();
        $url .= "&method=SitesManager.getSitesFromGroup&format=JSON"
                . "&oldGroupName=" . urlencode($oldGroupName)
                . "&newGroupName=" . urlencode($newGroupName);
        if ($result = self::getAsJsonDecoded($url))
            return $result;
        else
            return FALSE;
    }

}
