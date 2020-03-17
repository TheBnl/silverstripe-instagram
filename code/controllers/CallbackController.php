<?php

namespace Broarm\Silverstripe\Instagram;

use Controller;
use DataObject;
use Director;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Member;
use SS_HTTPRequest;
use SS_HTTPResponse;

/**
 * Class CallbackController
 * @package Broarm\Silverstripe\Instagram
 */
class CallbackController extends Controller
{
    const REDIRECT_URL = '/instagram/authenticate';
    const REVOKE_URL = '/instagram/revoke';
    const MESSAGE_SUCCESS = 1;
    const MESSAGE_ERROR = 0;

    private static $allowed_actions = array(
        'authenticate',
        'revoke'
    );

    public function revoke(SS_HTTPRequest $request)
    {
        if ($member = DataObject::get_by_id(Member::class, $request->param('ID'))) {
            $member->update([
                'InstagramAccessToken' => null,
                'InstagramAccessTokenExpires' => null,
                'InstagramID' => null,
                'InstagramUserName' => null,
                'InstagramProfilePicture' => null,
                'InstagramFullName' => null
            ]);
            $member->write();
        }

        return $this->redirectWithMessage('Removed access to Instagram');
    }

    /**
     * Authenticate the user with the Instagram API
     */
    public function authenticate()
    {
        $member = Member::currentUser();
        $request = $this->getRequest();
        $code = $request->getVar('code');

        if (!$code) {
            return $this->redirectWithMessage('Did not receive a valid auth token');
        }

        // Get a access token from given authentication code
        try {
            $authClient = new InstagramAuthClient();
            $authResponse = $authClient->getAccessToken($code);
        } catch (RequestException $e) {
            return $this->redirectWithMessage($e->getMessage());
        } catch (Exception $e) {
            return $this->redirectWithMessage($e->getMessage());
        }

        if (!$authResponse->getBody()->isReadable()) {
            // error, request is not readable
            return $this->redirectWithMessage('Could not read response');
        }

        $response = json_decode($authResponse->getBody()->getContents());
        if (!(key_exists('access_token', $response) && key_exists('user_id', $response))) {
            // error, received no access tokoen
            return $this->redirectWithMessage('Could not receive token from auth response');
        }

        $member->update([
            'InstagramAccessToken' => $response->access_token,
            'InstagramID' => $response->user_id
        ]);

        // Exchange shortlived access token for a long lived access token we can refresh during the import
        try {
            $client = new InstagramClient($response->access_token);
            $response = $client->getLongLivedAccessToken();
        } catch (RequestException $e) {
            return $this->redirectWithMessage($e->getMessage());
        } catch (Exception $e) {
            return $this->redirectWithMessage($e->getMessage());
        }

        if (!$response->getBody()->isReadable()) {
            return $this->redirectWithMessage('Could not read response');
        }

        $response = json_decode($response->getBody()->getContents());
        if (!(key_exists('access_token', $response) && key_exists('expires_in', $response))) {
            return $this->redirectWithMessage('Could not read long lived access token from response');
        }

        $member->update([
            'InstagramAccessToken' => $response->access_token,
            'InstagramAccessTokenExpirers' => $response->expires_in
        ]);

        try {
            $member->write();
            return $this->redirectWithMessage('Authenticated Instagram', self::MESSAGE_SUCCESS);
        } catch (Exception $e) {
            return $this->redirectWithMessage('Failed to save member');
        }
    }

    /**
     * Redirect to the security admin for the active user
     *
     * @param $message
     * @param int $success
     * @return SS_HTTPResponse
     */
    protected function redirectWithMessage($message, $success = self::MESSAGE_ERROR)
    {
        $member = Member::currentUser();
        $memberID = $member ? $member->ID : 0;
        $message = urlencode($message);
        return $this->redirect(Director::absoluteURL("/admin/security/EditForm/field/Members/item/{$memberID}/edit?instagram_success=$success&instagram_message=$message"));
    }
}
