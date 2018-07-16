<?php

namespace Broarm\Silverstripe\Instagram;

use ArrayList;
use Member;
use Object;
use RestfulService;

/**
 * Class Instagram
 * @package Broarm\Silverstripe\Instagram
 */
class Instagram extends Object
{
    const API_URL = 'https://api.instagram.com/v1/';

    const LIMIT = 20;

    private static $client_id = null;

    private static $client_secret = null;


    /**
     * Set up a connection with the Instagram API
     *
     * @param int $limit
     * @param string $search
     * @param string $token
     * @return RestfulService
     */
    private static function connection($limit = null, $search = null, $token = null)
    {
        $connection = new RestfulService(self::API_URL);

        $query = array(
            'access_token' => $token ? $token : Instagram::getAuthenticatedMembers()->first()->getField('InstagramAccessToken'),
            'count' => $limit ? $limit : self::LIMIT
        );

        if ($search) {
            $query["q"] = $search;
        }

        $connection->setQueryString($query);
        return $connection;
    }


    /**
     * Get a list of authenticated members
     *
     * @return \DataList
     */
    public static function getAuthenticatedMembers() {
        return Member::get()->filter(array('InstagramAccessToken:not' => 'NULL'));
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
        $connection = self::connection($limit, null, $token)->request($node);
        if (($body = json_decode($connection->getBody(), true)) && isset($body["data"]) && !empty($body["data"])) {
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
    public function getMemberMedia(Member $member, $limit = null, $token = null)
    {
        return $this->get("users/{$member->InstagramID}/media/recent/", $limit, $token);
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