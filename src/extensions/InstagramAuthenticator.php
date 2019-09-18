<?php

namespace Broarm\Instagram;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\TextField;

/**
 * Class InstagramAuthenticator
 * @package Broarm\Silverstripe\Instagram
 *
 * @property string ClientID
 * @property string ClientSecret
 * @property |SiteConfig|InstagramAuthenticator owner
 */
class InstagramAuthenticator extends DataExtension
{
    const API_OAUTH_URL = 'https://api.instagram.com/oauth/authorize';

    const API_OAUTH_TOKEN_URL = 'https://api.instagram.com/oauth/access_token';

    const REDIRECT_PATH = '/instagram/authenticate';

    private static $db = array(
        'InstagramClientID' => 'Varchar(255)',
        'InstagramClientSecret' => 'Varchar(255)'
    );

    public function updateCMSFields(FieldList $fields)
    {
        // Check if the Client ID/Secret already exists or give the user a option to set it trough the CMS
        // The Client ID/Secret can also be set trough the config.yml
        if (!self::getClientID()) {
            $fields->addFieldToTab(
                'Root.Instagram',
                TextField::create('ClientID', 'Instagram Client ID')
                    ->setDescription(_t(
                        'Instagram.CLIENT_ID_DESCRIPTION',
                        'You can get a Client ID from the developer section on Instagram'
                    ))
            );
        }

        if (!self::getClientSecret()) {
            $fields->addFieldToTab(
                'Root.Instagram',
                TextField::create('ClientSecret', 'Instagram Client Secret')
                    ->setDescription(_t(
                        'Instagram.CLIENT_ID_DESCRIPTION',
                        'You can get a Client Secret from the developer section on Instagram'
                    ))
            );
        }

        return $fields;
    }


    /**
     * Get the client id from config or database
     *
     * @return string
     */
    public static function getClientID()
    {
        $siteConfig = SiteConfig::current_site_config();
        $clientID = Instagram::config()->get('client_id');
        return isset($clientID)
            ? $clientID
            : $siteConfig->getField('InstagramClientID');
    }

    /**
     * Get the client secret from config or database
     *
     * @return string
     */
    public static function getClientSecret()
    {
        $siteConfig = SiteConfig::current_site_config();
        $clientSecret = Instagram::config()->get("client_secret");
        return $clientSecret
            ? $clientSecret
            : $siteConfig->getField('InstagramClientSecret');
    }


    /**
     * Build the authenticator URL
     *
     * @return string
     */
    public static function getAuthenticationURL()
    {
        return self::API_OAUTH_URL . "?client_id=" . self::getClientID() . "&redirect_uri=" . urlencode(self::getRedirectURL()) . "&response_type=code";
    }


    /**
     * Build the redirect URL
     *
     * @return string
     */
    public static function getRedirectURL()
    {
        return Director::absoluteURL(self::REDIRECT_PATH);
    }
}
