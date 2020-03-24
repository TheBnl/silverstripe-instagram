<?php

namespace Broarm\Silverstripe\Instagram;

use BuildTask;
use DataObject;
use Member;
use SS_HTTPRequest;
use stdClass;
use ValidationException;


/**
 * The facebook import images task
 * requires the existence of the FacebookImage class for downloading the image
 *
 * Class ImportFacebookPhotosTasks
 */
class ImportMediaTasks extends BuildTask
{
    protected $enabled = true;

    private static $data_mapping = array(
        'id' => 'InstagramID',
        'caption' => 'InstagramCaptionText',
        'media_type' => 'InstagramMediaType',
        'media_url' => 'InstagramImageURL',
        'thumbnail_url' => 'InstagramImageURL',
        'permalink' => 'InstagramLink',
        'timestamp' => 'InstagramCreated',
        'username' => 'InstagramUserName',
    );

    /**
     * @param SS_HTTPRequest $request
     *
     * @throws ValidationException
     */
    public function run($request)
    {
        /** @var Member|MemberExtension $member */
        foreach (InstagramClient::getAuthenticatedMembers() as $member) {
            self::log("Import Instagram post for {$member->getName()}");
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
                    $obj = self::handleObject($mediaObject);
                    self::log("Created instagram media obj with ID {$obj->ID} from source {$obj->InstagramID}");
                }
            }
        }

        exit('Done');
    }

    /**
     * Handle the image data
     *
     * @param stdClass|array $data
     *
     * @return InstagramMediaObject|DataObject
     * @throws ValidationException
     */
    private static function handleObject($data) {
        // Find or make a facebook image object from the given facebook id
        $mediaObject = InstagramMediaObject::find_or_make($data->id);

        // Loop over the data and set like the mapping
        self::loopMap($mediaObject, $data, self::config()->get('data_mapping'));

        $mediaObject->setImage();
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

        print_r("\n===\n");
        print_r($dataSet);
        print_r("\n===\n");

        foreach ($map as $from => $to) {
            if (is_array($to) && key_exists($from, $dataSet)) {
                self::loopMap($mediaObject, $dataSet->$from, $to);
            } elseif (key_exists($from, $dataSet)) {
                $value = $dataSet->$from;
                if ($from === 'caption') {
                    // Strip emojis since these are not supported by SS3
                    $value = preg_replace("/[^A-Za-z0-9-_#. ]/", '', $value);
                }
                self::log("Set $to => {$value}");
                $mediaObject->setField((string)$to, $value);
            }
        }
    }

    public static function log($message, $level = 'info')
    {
        echo "[$level] $message \n";
    }
}
