<?php

namespace Broarm\Silverstripe\Instagram;

use DataObject;
use Director;
use FileNameFilter;
use Folder;
use Image;
use Member;

/**
 * Class InstagramMediaObject
 *
 * @package Broarm\Silverstripe\Instagram
 *
 * @property string Title
 * @property string InstagramID
 * @property string InstagramCaptionText
 * @property string InstagramMediaType
 * @property string InstagramImageURL
 * @property string InstagramLink
 * @property string InstagramCreated
 * @property string InstagramUserName
 */
class InstagramMediaObject extends Image
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'InstagramID' => 'Varchar(255)', // id
        'InstagramCaptionText' => 'Varchar(255)', // caption
        'InstagramMediaType' => 'Varchar(255)', // media_type
        'InstagramImageURL' => 'Varchar(255)', // media_url
        'InstagramLink' => 'Varchar(255)', // permalink
        'InstagramCreated' => 'SS_Datetime', // timestamp
        'InstagramUserName' => 'Varchar(255)', // username
    );

    private static $default_sort = 'InstagramCreated DESC';

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Title = $this->InstagramCaptionText;

        if ($this->isChanged('InstagramImageURL')) {
            $this->setImage();
        }
    }

    /**
     * Find or make a facebook image
     *
     * @param $instagramId
     *
     * @return DataObject|InstagramMediaObject
     */
    public static function find_or_make($instagramId)
    {
        if (!$image = InstagramMediaObject::get()->find('InstagramID', $instagramId)) {
            $image = InstagramMediaObject::create();
        }

        return $image;
    }

    /**
     * Upload images to the /instagram/OWNER_ID/ folder
     *
     * @return string
     */
    private function uploadFolder()
    {
        $filter = FileNameFilter::create();
        $folder = $filter->filter($this->InstagramUserName);
        return "instagram/{$folder}";
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
        $fileName = explode('?', $sourcePath['basename'])[0];
        $relativeFilePath = $folder->Filename . $fileName;
        $absoluteFilePath = "$baseFolder/$relativeFilePath";


        print_r("\n\ndownload to file: \n");
        print_r($absoluteFilePath);
        print_r("\n === \n");


        if (self::download_file($imageSource, $absoluteFilePath)) {
            $this->setField('ParentID', $folder->ID);
            $this->OwnerID = (Member::currentUser()) ? Member::currentUser()->ID : 0;
            $this->setName($fileName);
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
     *
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
