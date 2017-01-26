<?php

namespace Broarm\Silverstripe\Instagram;

use Controller;
use Director;
use Member;

/**
 * Class CallbackController
 * @package Broarm\Silverstripe\Instagram
 */
class CallbackController extends Controller
{
    private static $allowed_actions = array('authenticate');

    public function init()
    {
        parent::init();
    }

    /**
     * Authenticate the user with the Instagram API
     */
    public function authenticate()
    {
        $code = $this->getRequest()->getVar('code');
        $error = $this->getRequest()->getVar('error');
        $error_reason = $this->getRequest()->getVar('error_reason');
        $error_description = $this->getRequest()->getVar('error_description');
        $memberID = Member::currentUserID();

        if ($code) {
            $url = InstagramAuthenticator::API_OAUTH_TOKEN_URL;
            $fields = array(
                "client_id" => InstagramAuthenticator::getClientID(),
                "client_secret" => InstagramAuthenticator::getClientSecret(),
                "grant_type" => "authorization_code",
                "redirect_uri" => urlencode(InstagramAuthenticator::getRedirectURL()),
                "code" => $code
            );

            $fields_string = "";
            foreach ($fields as $key => $value) {
                $fields_string .= $key . '=' . $value . '&';
            }
            rtrim($fields_string, '&');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            $response = json_decode(curl_exec($ch));
            curl_close($ch);

            if (array_key_exists("error_message", $response)) {
                $this->redirect(Director::absoluteURL("admin/settings/?authenticated=false&error_description=$error_description#Root_Instagram"));
            } else {
                $member = Member::currentUser();
                $member->setField('InstagramAccessToken', $response->access_token);
                $member->setField('InstagramID', $response->user->id);
                $member->setField('InstagramUserName', $response->user->username);
                $member->setField('InstagramProfilePicture', $response->user->profile_picture);
                $member->setField('InstagramFullName', $response->user->full_name);
                $member->write();

                $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=true#Root_Instagram"));
            }

        } else {
            if ($error) {
                $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=false&error=$error&error_reason=$error_reason&error_description=$error_description#Root_Instagram"));
            } else {
                $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=false"));
            }
        }
    }
}
