<?php

namespace Broarm\Instagram;

use Broarm\Instagram\Controllers\CallbackController;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class InstagramClient
 * @package Broarm\Instagram
 */
class InstagramClient
{
    use Configurable;

    const API_URL = 'https://graph.instagram.com/';

    private static $app_id = null;

    private static $app_secret = null;

    private static $scope = [
        'user_profile',
        'user_media'
    ];

    private static $user_fields = [
        'id',
        'username'
    ];

    private static $response_type = 'code';

    /**
     * @var Client
     */
    protected $client;

    /**
     * Construct a client for a given access token
     * Access tokens are stored on members, so multiple accounts van be added to your site.
     *
     * @param $accessToken
     */
    public function __construct($accessToken)
    {
        if (!$appSecret = self::getAppSecret()) {
            throw new Exception('No app secret configured, set this in your config under Broarm\Instagram\InstagramClient.app_secret of in the DB trough site config');
        }

        $defaultQuery = [
            'client_secret' => $appSecret,
            'access_token' => $accessToken
        ];

        $handler = new HandlerStack();
        $handler->setHandler(new CurlHandler());
        $handler->unshift(Middleware::mapRequest(function(RequestInterface $request) use ($defaultQuery) {
            $uri = $request->getUri();
            foreach ($defaultQuery as $key => $value) {
                $uri = Uri::withQueryValue($uri, $key, $value);
            }
            return $request->withUri($uri);
        }));

        $this->client = new Client([
            'base_uri' => self::API_URL,
            'handler' => $handler
        ]);
    }

    /**
     * Exchange the short lived access token for a long lived access token
     *
     * @param $accessToken
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getLongLivedAccessToken()
    {
        return $this->client->get('access_token', [
            'query' => [
                'grant_type'=> 'ig_exchange_token',
            ]
        ]);
    }

    /**
     * Get the user info for the user connected to the token or given id
     *
     * @param string $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getUser($id = 'me')
    {
        return $this->client->get($id, [
            'query' => [
                'fields'=> implode(',', self::config()->get('user_fields'))
            ]
        ]);
    }

    /**
     * Get the media for the current user or given user id
     *
     * @param string $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getUserMedia($id = 'me')
    {
        return $this->client->get("$id/media");
    }

    public function getMedia($id)
    {
        return $this->client->get($id, [
            'query' => [
                'fields' => implode(',', [
                    'caption',
                    'id',
                    'media_type',
                    'media_url',
                    'permalink',
                    'thumbnail_url',
                    'timestamp',
                    'username',
                ])
            ]
        ]);
    }

    /**
     * Get the app id form the config or database
     *
     * @return string
     */
    public static function getAppID()
    {
        $siteConfig = SiteConfig::current_site_config();
        return self::config()->get('app_id') ?: $siteConfig->InstagramAppID;
    }

    /**
     * Get the app secret form the config or database
     *
     * @return string
     */
    public static function getAppSecret()
    {
        $siteConfig = SiteConfig::current_site_config();
        return self::config()->get('app_secret') ?: $siteConfig->InstagramAppSecret;
    }

    /**
     * Create the url for authenicating users
     *
     * @return string
     */
    public static function getAuthenticationURL()
    {
        $url = Controller::join_links([InstagramAuthClient::AUTH_URL, 'authorize']);
        $query = [
            'client_id' => self::getAppID(),
            'redirect_uri' => Director::absoluteURL(CallbackController::REDIRECT_URL),
            'scope' => implode(',', self::config()->get('scope')),
            'response_type' => self::config()->get('response_type'),
        ];

        return $url . '?' . http_build_query($query);
    }

    /**
     * Get a list of authenticated members
     *
     * @return DataList
     */
    public static function getAuthenticatedMembers()
    {
        return Member::get()->filter(array('InstagramAccessToken:not' => null));
    }
}