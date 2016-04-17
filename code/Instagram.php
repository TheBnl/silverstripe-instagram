<?php

/**
 * Created by PhpStorm.
 * User: bramdeleeuw
 * Date: 20/02/16
 * Time: 19:34
 */
class Instagram
{

	/**
	 * The API base URL
	 */
	const API_URL = 'https://api.instagram.com/v1/';

	const LIMIT = 8;

	public function __construct() {}

	/**
	 * Set up a connection with the Instagram API
	 * @param int $limit
	 * @param Member $member
	 * @param string $search
	 * @return RestfulService
	 */
	private static function connection($limit = self::LIMIT, Member $member = null, $search = null) {
		$connection = new RestfulService(self::API_URL);
		//$siteConfig = SiteConfig::current_site_config();
		if (!$member) $member = Member::get()->filter(array('InstagramAccessToken:not' => 'NULL'));
		if ($member->count() && $member->first()->exists()) {
			$member = $member->first();
		} else {
			user_error('No members yet authenticated with Instagramm');
			return false;
		}

		$query = array(
				'access_token' => $member->getAccessToken(),
				'count' => $limit
		);

		if ($search) $query["q"] = $search;
		$connection->setQueryString($query);

		return $connection;
	}

	/**
	 * Get the current users media
	 * This feature will work in sandbox mode, it will showcase the latest 20 media items
	 * @param int $limit
	 * @return ArrayList
	 */
	public static function getCurrentUserMedia($limit = self::LIMIT) {
		$connection = self::connection($limit)->request("users/self/media/recent/");
		$body = json_decode($connection->getBody(), true);
		return new ArrayList($body["data"]);
	}

	/**
	 * Get Media for the user with the given ID
	 * This feature will only work in sandbox mode if the requested user name granted permission to the app
	 * @param $userName
	 * @param int $limit
	 * @return ArrayList
	 */
	public static function getUserMedia($userName, $limit = self::LIMIT) {
		$userID = self::getUserID($userName);
		$connection = self::connection($limit)->request("users/$userID/media/recent/");
		$body = json_decode($connection->getBody(), true);
		return new ArrayList($body["data"]);
	}

	/**
	 * Get media associated with the given tag
	 * In sandbox mode this feature will only work with tags from authorized accounts
	 * @param $tagName
	 * @param int $limit
	 * @return ArrayList
	 */
	public static function getTaggedMedia($tagName, $limit = self::LIMIT) {
		$connection = self::connection($limit)->request("tags/$tagName/media/recent/");
		$body = json_decode($connection->getBody(), true);
		return new ArrayList($body["data"]);
	}

	/**
	 * Get the User ID from the given user name
	 * In sandbox mode this feature will only work with authorized accounts
	 * @param $userName
	 * @return ArrayList
	 */
	public static function getUserID($userName) {
		$member = Member::get()->find('InstagramUserName', $userName);
		return $member->getField('InstagramID');
		/**
		$connection = self::connection($limit, $member, $userName)->request("users/search/");
		$body = json_decode($connection->getBody(), true);

		echo "<pre>";
		print_r($body);
		echo "</pre>";
		exit();

		return new ArrayList($body["data"]);
		 */
	}
}