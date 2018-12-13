<?php

namespace Broarm\Instagram;

use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

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
class InstagramMediaObject extends Image
{
    private static $table_name = 'InstagramMediaObject';

    private static $db = array(
        'Title' => 'Varchar(255)',
        'InstagramID' => 'Varchar(255)', // id
        'InstagramAttribution' => 'Varchar(255)', // attribution
        'InstagramCreated' => 'DBDatetime', // created_time
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
        'InstagramCaptionCreated' => 'DBDatetime', // caption created_time
        'InstagramCaptionText' => 'Varchar(255)', // caption text
        'InstagramCaptionFromFullName' => 'Varchar(255)', // caption from full_name
        'InstagramCaptionFromID' => 'Varchar(255)', // caption from id
        'InstagramCaptionFromUserName' => 'Varchar(255)', // caption from username
        'InstagramCaptionFromProfilePicture' => 'Varchar(255)', // caption from profile_picture
        'InstagramImageURL' => 'Varchar(255)', // images standard_resolution
    );

    private static $default_sort = 'InstagramCreated DESC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }


    public function onBeforeWrite()
    {
        $this->Title = $this->InstagramCaptionText;
        $this->Created = $this->InstagramCreated;//date('Y-m-d', $this->InstagramCreated);

        // Download and set the image first time it's downloaded
        if (!$this->exists()) {
            try {
                $this->setImage();
            } catch (\Exception $e) {
                user_error($e, E_USER_ERROR);
            }
        }

        parent::onBeforeWrite();
    }

    /**
     * Find or make a facebook image
     *
     * @param $instagramId
     * @return DataObject|InstagramMediaObject
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
     * @throws \Exception
     */
    private function setImage()
    {
        $folder = Folder::find_or_make($this->uploadFolder());
        $imageSource = $this->InstagramImageURL;
        $sourcePath = pathinfo($imageSource);
        $fileName = explode('?',$sourcePath['basename'])[0];
        if ($stream = fopen($imageSource, 'r')) {
            $this->setFromStream($stream, $fileName);
            $this->ParentID =  $folder->ID;
            if ($user = DataObject::get_one(Member::class, ['InstagramID' => $this->InstagramUserID])) {
                $this->OwnerID = $user->ID;
            }
        } else {
            throw new \Exception("Error while downloading file: $imageSource");
        }
    }
}
