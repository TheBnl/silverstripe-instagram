<?php

namespace Broarm\Silverstripe\Instagram;

use Controller;
use DataExtension;
use Exception;
use FieldList;
use GuzzleHttp\Exception\RequestException;
use LiteralField;
use Member;
use ReadonlyField;
use TextField;

/**
 * Class MemberExtension
 *
 * @property string InstagramAccessToken
 * @property string InstagramAccessTokenExpires
 * @property string InstagramID
 * @property string InstagramUserName
 *
 * @property Member|MemberExtension owner
 */
class MemberExtension extends DataExtension
{
    private static $db = array(
        'InstagramAccessToken' => 'Varchar(255)',
        'InstagramAccessTokenExpires' => 'SS_Datetime',
        'InstagramID' => 'Varchar(255)',
        'InstagramUserName' => 'Varchar(255)',
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'InstagramAccessToken',
            'InstagramAccessTokenExpires',
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
                TextField::create('InstagramAccessToken', _t('Instagram.InstagramAccessToken', 'Or add a generated acccess token'))
                    ->setDescription(_t(
                        'Instagram.InstagramAccessTokenDescription',
                        'You can generate access tokens for test users of your app. <a href="{read_more_link}" target="_blank">more information</a>',
                        null,
                        ['read_more_link' => 'https://developers.facebook.com/docs/instagram-basic-display-api/overview#user-token-generator']
                    ))
            ]);
        } else {
            $fields->addFieldsToTab('Root.Instagram', array(
                ReadonlyField::create('InstagramUserName', _t('Instagram.InstagramUserName', 'Connected to IG account')),
                RevokeButton::create('InstagramRevokeButton', $this->owner->ID)
            ));
        }
    }

    public function onBeforeWrite()
    {
        // update user date here to also support set generated tokens
        if ($this->owner->isChanged('InstagramAccessToken') && $this->owner->InstagramAccessToken) {
            try {
                $client = new InstagramClient($this->owner->InstagramAccessToken);
                $response = $client->getUser();
                $user = json_decode($response->getBody()->getContents());
                $this->owner->InstagramID = $user->id;
                $this->owner->InstagramUserName = $user->username;
            } catch (RequestException $e) {
                // soft error ?
            } catch (Exception $e) {
                // soft error ?
            }
        }
    }
}
