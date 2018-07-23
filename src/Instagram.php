<?php

namespace Broarm\Instagram;

use GuzzleHttp;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;

/**
 * Class Instagram
 * @package Broarm\Silverstripe\Instagram
 */
class Instagram
{
    use Configurable;

    const API_URL = 'https://api.instagram.com/v1/';

    const LIMIT = 20;

    private static $client_id = null;

    private static $client_secret = null;


    /**
     * @param $node
     * @param null $limit
     * @param null $search
     * @param null $token
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    private static function connection($node, $limit = null, $search = null, $token = null)
    {
        $client = new GuzzleHttp\Client();
        $query = [
            'access_token' => $token ? $token : Instagram::getAuthenticatedMembers()->first()->getField('InstagramAccessToken'),
            'count' => $limit ? $limit : self::LIMIT
        ];

        if ($search) {
            $query["q"] = $search;
        }

        try {
            $res = $client->request('GET', self::API_URL . $node, [
                'query' => $query
            ]);

            return $res;
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            user_error($e->getMessage(), E_USER_ERROR);
        }

        return null;
    }


    /**
     * Get a list of authenticated members
     *
     * @return DataList
     */
    public static function getAuthenticatedMembers() {
        return Member::get()->filter(array('InstagramAccessToken:not' => null));
    }


    /**
     * Make the request and decode the body
     *
     * @param null $node
     * @param int $limit
     * @param string $token
     * @return ArrayList
     */
    public function get($node = null, $limit = null, $token = null)
    {
        $request = self::connection($node, $limit, null, $token);
        if (($body = json_decode($request->getBody(), true)) && isset($body["data"]) && !empty($body["data"])) {
            return new ArrayList($body["data"]);
        }

        return new ArrayList();
    }


    /**
     * Get the current users media
     * This feature will work in sandbox mode, it will showcase the latest 20 media items
     *
     * @param int $limit
     * @param string $token
     * @return ArrayList
     */
    public function getCurrentUserMedia($limit = null, $token = null)
    {
        return $this->get('users/self/media/recent/', $limit, $token);
    }


    /**
     * Get media for the given Member
     * This feature will only work in sandbox mode if the requested user granted permission to the app
     *
     * @param Member|MemberExtension $member
     * @param int $limit
     * @param string $token
     * @return ArrayList
     */
    public function getMemberMedia(Member $member, $limit = null)
    {
        return $this->get("users/{$member->InstagramID}/media/recent/", $limit, $member->InstagramAccessToken);
    }


    /**
     * Get media associated with the given tag
     * In sandbox mode this feature will only work with tags from authorized accounts
     *
     * @param $tagName
     * @param null $limit
     * @param string $token
     * @return ArrayList
     */
    public function getTaggedMedia($tagName, $limit = null, $token = null)
    {
        return $this->get("tags/$tagName/media/recent/", $limit, $token);
    }
}