<?php

namespace Broarm\Instagram;

use GuzzleHttp\Client;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;

/**
 * Class CallbackController
 * @package Broarm\Silverstripe\Instagram
 */
class CallbackController extends Controller
{
    private static $allowed_actions = array(
        'authenticate',
        'revoke'
    );

    public function revoke(HTTPRequest $request)
    {
        if ($member = DataObject::get_by_id(Member::class, $request->param('ID'))) {
            $member->update([
                'InstagramAccessToken' => null,
                'InstagramID' => null,
                'InstagramUserName' => null,
                'InstagramProfilePicture' => null,
                'InstagramFullName' => null
            ]);
            $member->write();
        }

        $this->redirectBack();
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
        $member = Security::getCurrentUser();
        $memberID = $member ? $member->ID : 0;

        if ($code) {
            $client = new Client();
            $request = $client->request('POST', InstagramAuthenticator::API_OAUTH_TOKEN_URL, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    "client_id" => InstagramAuthenticator::getClientID(),
                    "client_secret" => InstagramAuthenticator::getClientSecret(),
                    "grant_type" => "authorization_code",
                    "redirect_uri" => InstagramAuthenticator::getRedirectURL(),
                    "code" => $code
                ]
            ]);

            if ($request->getBody()->isReadable()) {
                $response = Convert::json2obj($request->getBody()->getContents());
                if (array_key_exists("error_message", $response)) {
                    $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=false&error_description=$error_description#Root_Instagram"));
                } else {
                    $member->update([
                        'InstagramAccessToken' => $response->access_token,
                        'InstagramID' => $response->user->id,
                        'InstagramUserName' => $response->user->username,
                        'InstagramProfilePicture' => $response->user->profile_picture,
                        'InstagramFullName' => $response->user->full_name
                    ]);
                    try {
                        $member->write();
                        $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=true#Root_Instagram"));
                    } catch (\Exception $e) {
                        $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=false&error=1&error_reason=write_error&error_description={$e->getMessage()}#Root_Instagram"));
                    }
                }
            }
            
        } elseif ($error) {
            $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=false&error=$error&error_reason=$error_reason&error_description=$error_description#Root_Instagram"));
        } else {
            $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?authenticated=false"));
        }
    }
}
