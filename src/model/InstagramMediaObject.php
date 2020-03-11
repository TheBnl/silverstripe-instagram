<?php

namespace Broarm\Instagram\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Stream\GuzzleStreamWrapper;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * Class InstagramMediaObject
 * @package Broarm\Instagram
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
    private static $table_name = 'InstagramMediaObject';

    private static $db = array(
        'Title' => 'Varchar',
        'InstagramID' => 'Varchar', // id
        'InstagramCaptionText' => 'Varchar', // caption
        'InstagramMediaType' => 'Varchar', // media_type
        'InstagramImageURL' => 'Varchar', // media_url
        'InstagramLink' => 'Varchar', // permalink
        'InstagramCreated' => 'Datetime', // timestamp
        'InstagramUserName' => 'Varchar', // username
    );

    private static $default_sort = 'InstagramCreated DESC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }


    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Title = $this->InstagramCaptionText;
    }

    /**
     * Find or make a facebook image
     *
     * @param $instagramId
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
     * Find the owner ID
     *
     * @return int
     */
    public function findOwnerID()
    {
        if ($user = DataObject::get_one(Member::class, ['InstagramID' => $this->InstagramUserID])) {
            return $user->ID;
        } elseif ($user = Security::getCurrentUser()) {
            return $user->ID;
        } else {
            return 0;
        }
    }

    /**
     * Download the image
     *
     * @throws \Exception
     */
    public function downloadImage()
    {
        $client = new Client(['http_errors' => false]);
        $folder = Folder::find_or_make($this->uploadFolder());
        $imageSource = $this->InstagramImageURL;
        $sourcePath = pathinfo($imageSource);
        $fileName = explode('?', $sourcePath['basename'])[0];
        $request = $client->request('GET', $imageSource);
        $stream = $request->getBody();

        if ($stream->isReadable()) {
            $this->setFromStream($stream->detach(), $fileName);
            $this->ParentID = $folder->ID;
            $this->OwnerID = $this->findOwnerID();
        } else {
            throw new \Exception("Error while downloading file: $imageSource");
        }
    }

    /**
     * Generate thumbnails for use in the CMS
     */
    public function generateThumbnails()
    {
        $assetAdmin = AssetAdmin::singleton();
        $this->FitMax(
            $assetAdmin->config()->get('thumbnail_width'),
            $assetAdmin->config()->get('thumbnail_height')
        );
        $this->FitMax(
            UploadField::config()->uninherited('thumbnail_width'),
            UploadField::config()->uninherited('thumbnail_height')
        );
    }
}
