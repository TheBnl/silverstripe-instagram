<?php

namespace Broarm\Silverstripe\Instagram;

use DataExtension;
use FieldList;
use Member;

/**
 * Class MemberExtension
 *
 * @property string InstagramAccessToken
 * @property string InstagramID
 * @property string InstagramUserName
 * @property string InstagramProfilePicture
 * @property string InstagramFullName
 * @property string InstagramClientID
 * @property string InstagramClientSecret
 * @property Member|MemberExtension owner
 */
class MemberExtension extends DataExtension
{
    private static $db = array(
        'InstagramAccessToken' => 'Varchar(255)',
        'InstagramID' => 'Varchar(255)',
        'InstagramUserName' => 'Varchar(255)',
        'InstagramProfilePicture' => 'Varchar(255)',
        'InstagramFullName' => 'Varchar(255)',
    );

    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->owner->InstagramAccessToken) {
            $fields->addFieldToTab('Root.Instagram', AuthButton::create('InstagramAuthButton'));
        } else {
            $fields->addFieldsToTab('Root.Instagram', array(
                AccountInformationField::create('InstagramAccountInformation', $this->owner),
                RevokeButton::create('InstagramRevokeButton')
            ));
        }

        $fields->removeByName(array(
            'InstagramAccessToken',
            'InstagramID',
            'InstagramUserName',
            'InstagramProfilePicture',
            'InstagramFullName'
        ));
    }
}