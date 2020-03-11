<?php

namespace Broarm\Instagram;

use Broarm\Instagram\Controllers\CallbackController;
use GuzzleHttp\Client;
use SilverStripe\Control\Director;

class InstagramAuthClient
{
    const AUTH_URL = 'https://api.instagram.com/oauth/';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @return Client
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::AUTH_URL,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
    }

    /**
     * Get an access token from the user auth token
     *
     * @param string $code
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getAccessToken($code)
    {
        if (!$appId = InstagramClient::getAppID()) {
            throw new Exception('No app id configured, set this in your config under Broarm\Instagram\InstagramClient.app_id of in the DB trough site config');
        }

        if (!$appSecret = InstagramClient::getAppSecret()) {
            throw new Exception('No app secret configured, set this in your config under Broarm\Instagram\InstagramClient.app_secret of in the DB trough site config');
        }

        return $this->client->post('access_token', [
            'form_params' => [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => Director::absoluteURL(CallbackController::REDIRECT_URL),
                'code' => $code
            ]
        ]);
    }
}