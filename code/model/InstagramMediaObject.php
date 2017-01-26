<?php

namespace Broarm\Silverstripe\Instagram;

use Director;
use Folder;
use Image;
use Member;

/**
 * Class InstagramMediaObject
 * @package Broarm\Silverstripe\Instagram
 *
 * @property string Title
 * @property string InstagramID
 * @property string InstagramAttribution
 * @property string InstagramCreated
 * @property string InstagramType
 * @property string InstagramUserFullName
 * @property string InstagramUserID
 * @property string InstagramUserName
 * @property string InstagramUserProfilePicture
 * @property string InstagramVideoURL
 * @property string InstagramLink
 * @property string InstagramFilter
 * @property string InstagramUserHasLinked
 * @property int InstagramLikes
 * @property float InstagramLocationLat
 * @property float InstagramLocationLon
 * @property string InstagramLocationID
 * @property string InstagramLocationName
 * @property string InstagramCaptionID
 * @property string InstagramCaptionCreated
 * @property string InstagramCaptionText
 * @property string InstagramCaptionFromFullName
 * @property string InstagramCaptionFromID
 * @property string InstagramCaptionFromUserName
 * @property string InstagramCaptionFromProfilePicture
 * @property string InstagramImageURL
 */
class InstagramMediaObject extends Image {

    private static $db = array(
        'Title' => 'Varchar(255)',
        'InstagramID' => 'Varchar(255)', // id
        'InstagramAttribution' => 'Varchar(255)', // attribution
        'InstagramCreated' => 'SS_DateTime', // created_time
        'InstagramType' => 'Varchar(255)', // type
        'InstagramUserFullName' => 'Varchar(255)', // user full_name
        'InstagramUserID' => 'Varchar(255)', // user id
        'InstagramUserName' => 'Varchar(255)', // user username
        'InstagramUserProfilePicture' => 'Varchar(255)', // user profile_picture
        'InstagramVideoURL' => 'Varchar(255)', // videos standard_resolution url
        'InstagramLink' => 'Varchar(255)', // link
        'InstagramFilter' => 'Varchar(255)', // filter
        'InstagramUserHasLinked' => 'Boolean', // user_has_liked
        'InstagramLikes' => 'Int', // likes count
        'InstagramLocationLat' => 'Decimal(10,7)', // location latitude
        'InstagramLocationLon' => 'Decimal(10,7)', // location longitude
        'InstagramLocationID' => 'Varchar(255)', // location id
        'InstagramLocationName' => 'Varchar(255)', // location name
        'InstagramCaptionID' => 'Varchar(255)', // caption id
        'InstagramCaptionCreated' => 'SS_DateTime', // caption created_time
        'InstagramCaptionText' => 'Varchar(255)', // caption text
        'InstagramCaptionFromFullName' => 'Varchar(255)', // caption from full_name
        'InstagramCaptionFromID' => 'Varchar(255)', // caption from id
        'InstagramCaptionFromUserName' => 'Varchar(255)', // caption from username
        'InstagramCaptionFromProfilePicture' => 'Varchar(255)', // caption from profile_picture
        'InstagramImageURL' => 'Varchar(255)', // images standard_resolution
    );

    private static $default_sort = 'InstagramCreated DESC';

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        return $fields;
    }


    public function onBeforeWrite()
    {
        $this->setField('Title', $this->InstagramCaptionText);

        // Download and set the image first time it's downloaded
        if (!$this->exists()) {
            $this->setImage();
        }

        parent::onBeforeWrite();
    }

    /**
     * Find or make a facebook image
     *
     * @param $instagramId
     * @return \DataObject|InstagramMediaObject
     */
    public static function find_or_make($instagramId)
    {
        if ($image = InstagramMediaObject::get()->find('InstagramID', $instagramId)) {
            return $image;
        } else {
            return InstagramMediaObject::create();
        }
    }


    /**
     * Upload images to the /instagram/OWNER_ID/ folder
     *
     * @return string
     */
    private function uploadFolder()
    {
        return "instagram/{$this->InstagramUserID}";
    }


    /**
     * Set the image
     */
    private function setImage()
    {
        $folder = Folder::find_or_make($this->uploadFolder());
        $imageSource = $this->InstagramImageURL;
        $sourcePath = pathinfo($imageSource);
        $baseFolder = Director::baseFolder();
        $relativeFilePath = $folder->Filename . explode('?', $sourcePath['basename'])[0];
        $absoluteFilePath = "$baseFolder/$relativeFilePath";
        
        if (self::download_file($imageSource, $absoluteFilePath)) {
            $this->setField('ParentID', $folder->ID);
            $this->OwnerID = (Member::currentUser()) ? Member::currentUser()->ID : 0;
            $this->setName($sourcePath['basename']);
            $this->setFilename($relativeFilePath);
        } else {
            // Download error
            // Todo: throw actual error
        }
    }


    /**
     * Download the file to the given path
     *
     * @param $url
     * @param $path
     * @return bool
     */
    private static function download_file($url, $path)
    {
        if (!file_exists($path)) {
            $fp = fopen($path, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            return true;
        } else {
            return false;
        }
    }
}