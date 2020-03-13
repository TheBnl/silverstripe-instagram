<?php

namespace Broarm\Instagram\Extensions;

use Broarm\Instagram\Instagram;
use Broarm\Instagram\InstagramClient;
use GuzzleHttp\Exception\RequestException;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use Broarm\Instagram\Forms\RevokeButton;
use Broarm\Instagram\Forms\AccountInformationField;
use Broarm\Instagram\Forms\AuthButton;
use Exception;

/**
 * Class MemberExtension
 *
 * @property string InstagramAccessToken
 * @property string InstagramAccessTokenExpirers
 * @property string InstagramID
 * @property string InstagramUserName
 *
 * @property Member|MemberExtension owner
 */
class MemberExtension extends DataExtension
{
    private static $db = [
        'InstagramAccessToken' => 'Varchar',
        'InstagramAccessTokenExpirers' => 'Datetime',
        'InstagramID' => 'Varchar',
        'InstagramUserName' => 'Varchar',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'InstagramAccessToken',
            'InstagramAccessTokenExpirers',
            'InstagramID',
            'InstagramUserName',
            'InstagramProfilePicture',
            'InstagramFullName'
        ]);

        // Render user feedback
        if (($controller = Controller::curr()) && $request = $controller->getRequest()) {
            if (($success = $request->getVar('instagram_success')) !== null && $message = $request->getVar('instagram_message')) {
                $messageClass = $success ? 'good' : 'bad';
                $message = urldecode($message);
                $fields->addFieldsToTab('Root.Instagram', [
                    LiteralField::create('InstagramMessage', "<p class='message $messageClass'>$message</p>")
                ]);
            }
        }

        if (!$this->owner->InstagramAccessToken) {
            $fields->addFieldsToTab('Root.Instagram', [
                AuthButton::create('InstagramAuthButton'),
                TextField::create('InstagramAccessToken', _t(__CLASS__ . '.InstagramAccessToken', 'Or add a generated acccess token'))
                    ->setDescription(_t(
                        __CLASS__ . 'InstagramAccessTokenDescription',
                        'You can generate access tokens for test users of your app. <a href="{read_more_link}" target="_blank">more information</a>',
                        null,
                        ['read_more_link' => 'https://developers.facebook.com/docs/instagram-basic-display-api/overview#user-token-generator']
                    ))
            ]);
        } else {
            $fields->addFieldsToTab('Root.Instagram', array(
                ReadonlyField::create('InstagramUserName', _t(__CLASS__ . '.InstagramUserName', 'Connected to IG account')),
                RevokeButton::create('InstagramRevokeButton', $this->owner->ID)
            ));
        }
    }

    public function onBeforeWrite()
    {
        // update user date here to also support set generated tokens
        if ($this->owner->isChanged('InstagramAccessToken')) {
            try {
                $client = new InstagramClient($this->owner->InstagramAccessToken);
                $response = $client->getUser();
                $user = json_decode($response->getBody()->getContents());
                $this->owner->InstagramID = $user->id;
                $this->owner->InstagramUserName = $user->username;
            } catch (Exception $e) {
                // soft error ?
            } catch (RequestException $e) {
                // soft error ?
            }
        }
    }
}