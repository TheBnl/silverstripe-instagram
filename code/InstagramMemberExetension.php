<?php

/**
 * Created by PhpStorm.
 * User: Bram de Leeuw
 * Date: 13/03/16
 * Time: 17:12
 */
class InstagramMemberExtension extends DataExtension
{

	/**
	 * Database fields
	 * @var array
	 */
	private static $db = array(
		"InstagramAccessToken" => "Varchar(255)",
		"InstagramID" => "Varchar(255)",
		"InstagramUserName" => "Varchar(255)",
		"InstagramProfilePicture" => "Varchar(255)",
		"InstagramFullName" => "Varchar(255)",
	);

	public function updateCMSFields(FieldList $fields)
	{
		$fields->removeByName(array(
			"InstagramAccessToken",
			"InstagramID",
			"InstagramUserName",
			"InstagramProfilePicture",
			"InstagramFullName"
		));
	}

	/**
	 * Return the access token
	 * @return string
	 */
	public function getAccessToken() {
		return $this->owner->getField("InstagramAccessToken");
	}
}