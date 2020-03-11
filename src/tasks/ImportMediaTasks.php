<?php

namespace Broarm\Instagram\Tasks;

use Broarm\Instagram\Extensions\MemberExtension;
use Broarm\Instagram\InstagramClient;
use Broarm\Instagram\Model\InstagramMediaObject;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;

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

    private static $data_mapping = [
        'id' => 'InstagramID',
        'caption' => 'InstagramCaptionText',
        'media_type' => 'InstagramMediaType',
        'media_url' => 'InstagramImageURL',
        'permalink' => 'InstagramLink',
        'timestamp' => 'InstagramCreated',
        'username' => 'InstagramUserName',
    ];

    /**
     * Run the facebook import task
     *
     * @param $request
     */
    public function run($request)
    {
        /** @var Member|MemberExtension $member */
        foreach (InstagramClient::getAuthenticatedMembers() as $member) {
            echo "Import Instagram post for {$member->getName()}\n\n";
            $client = new InstagramClient($member->InstagramAccessToken);

            // Refresh the access token
            $tokenResponse = $client->getRefreshLongLivedAcctionToken();
            $token = json_decode($tokenResponse->getBody()->getContents());

            $member->InstagramAccessToken = $token->access_token;
            $member->write();

            // Initiate a new client with the fresh token
            $client = new InstagramClient($member->InstagramAccessToken);
            $response = $client->getUserMedia();
            $response = json_decode($response->getBody()->getContents());

            if (key_exists('data', $response)) {
                foreach ($response->data as $mediaObject) {
                    $mediaResponse = $client->getMedia($mediaObject->id);
                    $mediaItem = json_decode($mediaResponse->getBody()->getContents());
                    $obj = self::handleObject($mediaItem);
                    echo "Created instagram media obj with ID {$obj->ID} from source {$obj->InstagramID} \n\n";
                }
            }
        }

        exit('Done');
    }


    /**
     * Handle the image data
     *
     * @param stdClass|array $data
     * @return DataObject|InstagramMediaObject
     */
    private static function handleObject($data) {
        // Find or make a facebook image object from the given facebook id
        $mediaObject = InstagramMediaObject::find_or_make($data->id);

        // Loop over the data and set like the mapping
        self::loopMap($mediaObject, $data, self::config()->get('data_mapping'));

        try {
            if ($mediaObject->isChanged('InstagramImageURL', DataObject::CHANGE_VALUE)) {
                $mediaObject->downloadImage();
            }

            if ($mediaObject->isChanged()) {
                $mediaObject->write();
                $mediaObject->generateThumbnails();
            }

            if (!$mediaObject->isLiveVersion()) {
                $mediaObject->publishSingle();
            }
        } catch (\Exception $e) {
            echo "[ERROR] {$e->getMessage()} \n";
        }

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
            if (is_array($to) && key_exists($from, $dataSet)) {
                self::loopMap($mediaObject, $dataSet->$from, $to);
            } elseif (key_exists($from, $dataSet)) {
                echo "Set $to => {$dataSet->$from} \n";
                $mediaObject->setField((string)$to, $dataSet->$from);
            }
        }
    }
}
