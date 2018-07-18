<?php

namespace Broarm\Silverstripe\Instagram;

use BuildTask;

/**
 * The facebook import images task
 * requires the existence of the FacebookImage class for downloading the image
 *
 * Class ImportFacebookPhotosTasks
 */
class ImportMediaTasks extends BuildTask
{
    /**
     * @var Instagram
     */
    protected $instagram;

    protected $enabled = true;

    private static $data_mapping = array(
        'id' => 'InstagramID',
        'attribution' => 'InstagramAttribution',
        'created_time' => 'InstagramCreated',
        'type' => 'InstagramType',
        'user' => array(
            'full_name' => 'InstagramUserFullName',
            'id' => 'InstagramUserID',
            'username' => 'InstagramUserName',
            'profile_picture' => 'InstagramUserProfilePicture',
        ),
        'videos' => array(
            'standard_resolution' => array(
                'url' => 'InstagramVideoURL'
            )
        ),
        'link' => 'InstagramLink',
        'filter' => 'InstagramFilter',
        'user_has_liked' => 'InstagramUserHasLinked',
        'likes' => array(
            'count' => 'InstagramLikes'
        ),
        'location' => array(
            'latitude' => 'InstagramLocationLat',
            'longitude' => 'InstagramLocationLon',
            'id' => 'InstagramLocationID',
            'name' => 'InstagramLocationName',
        ),
        'caption' => array(
            'id' => 'InstagramCaptionID',
            'created_time' => 'InstagramCaptionCreated',
            'text' => 'InstagramCaptionText',
            'from' => array(
                'full_name' => 'InstagramCaptionFromFullName',
                'id' => 'InstagramCaptionFromID',
                'username' => 'InstagramCaptionFromUserName',
                'profile_picture' => 'InstagramCaptionFromProfilePicture',
            ),
        ),
        'images' => array(
            'standard_resolution' => array(
                'url' => 'InstagramImageURL'
            )
        )
    );


    /**
     * Run the facebook import task
     *
     * @param $request
     */
    public function run($request)
    {
        $this->instagram = new Instagram();
        if (!Director::is_cli()) echo "<pre>";
        foreach (Instagram::getAuthenticatedMembers() as $member) {
            echo "Import Istagram post for {$member->getName()}\n\n";
            if ($media = $this->instagram->getMemberMedia($member, null, $member->InstagramAccessToken)) {
                foreach ($media as $mediaObject) {
                    $obj = self::handleObject($mediaObject->toMap());
                    echo "Created instagram media obj with ID {$obj->ID} from source {$obj->InstagramID} \n\n";
                }
            }
        }
        if (!Director::is_cli()) echo "</pre>";
        exit('Done');
    }


    /**
     * Handle the image data
     *
     * @param array $data
     * @return \DataObject|InstagramMediaObject
     */
    private static function handleObject(array $data) {
        // Find or make a facebook image object from the given facebook id
        $mediaObject = InstagramMediaObject::find_or_make($data['id']);

        // Loop over the data and set like the mapping
        self::loopMap($mediaObject, $data, self::config()->get('data_mapping'));

        $mediaObject->write();
        return $mediaObject;
    }


    /**
     * Loop over the data map and set accordingly
     *
     * @param InstagramMediaObject $mediaObject
     * @param $dataSet
     * @param $map
     */
    private static function loopMap(InstagramMediaObject $mediaObject, $dataSet, $map)
    {
        foreach ($map as $from => $to) {
            if (is_array($to) && isset($dataSet[$from])) {
                self::loopMap($mediaObject, $dataSet[$from], $to);
            } elseif (isset($dataSet[$from])) {
                echo "Set $to => $dataSet[$from] \n";
                $mediaObject->setField($to, $dataSet[$from]);
            }
        }
    }
}
