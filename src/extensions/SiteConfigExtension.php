<?php

namespace Broarm\Instagram\Extensions;

use Broarm\Instagram\InstagramClient;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\TextField;
use Broarm\Instagram\Instagram;

/**
 * Class SiteConfigExtension
 * @package Broarm\Silverstripe\Instagram
 *
 * @property string InstagramAppID
 * @property string InstagramAppSecret
 * @property |SiteConfig|InstagramAuthenticator owner
 */
class SiteConfigExtension extends DataExtension
{
    private static $db = [
        'InstagramAppID' => 'Varchar',
        'InstagramAppSecret' => 'Varchar'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Check if the App ID/Secret already exists or give the user a option to set it trough the CMS
        // The App ID/Secret can also be set trough the config.yml
        if (!InstagramClient::config()->get('app_id')) {
            $fields->addFieldToTab(
                'Root.Instagram',
                TextField::create('InstagramAppID', _t(__CLASS__ . '.InstagramAppID', 'Instagram App ID'))
                    ->setDescription(_t(
                        __CLASS__ . '.InstagramAppIDDescription',
                        'You can get a App ID from developers.facebook.com'
                    ))
            );
        }

        if (!InstagramClient::config()->get('app_secret')) {
            $fields->addFieldToTab(
                'Root.Instagram',
                TextField::create('InstagramAppSecret', _t(__CLASS__ . '.InstagramAppSecret', 'Instagram App Secret'))
                    ->setDescription(_t(
                        __CLASS__ . '.InstagramAppSecretDescription',
                        'You can get a App Secret from developers.facebook.com'
                    ))
            );
        }
    }
}
