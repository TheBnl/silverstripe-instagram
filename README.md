# Silverstripe Instagram

Add support for the [Instagram Basic Display API](https://developers.facebook.com/docs/instagram-basic-display-api) to your website.
Creates a tab in on the Member section where content authors can authenticate with your Instagram app.
The module contains a task that fetches the authenticated member's images form instagram.

You can also paste a token from your app on [developers.facebook.com](https://developers.facebook.com/) with the [User Token Generator](https://developers.facebook.com/docs/instagram-basic-display-api/overview#user-token-generator).
That way you can easily set up a connection for a client, your client only needs to accept the invitation from your facebook app. 

### Installation

Install the module trough composer

```bash
composer require bramdeleeuw/silverstripe-instagram
```

Set up your app in [developers.facebook.com](https://developers.facebook.com/) and follow [these instructions](https://developers.facebook.com/docs/instagram-basic-display-api/getting-started) to add the Instagram Basic API.
After completing step 3 you should have an app set up with at least one test user and a `app_id` and `app_secret`.
From the test users overview you can also generate a user token so you don't have to authorise trough the CMS.  

Configure the `app_id` and `app_secret` in your yml config. Or you can use the CMS Setting to connect with your app.

```yaml
Broarm\Instagram\InstagramClient:
  app_id: 'YOUR_APP_ID'
  app_secret: 'YOUR_APP_SECRET'
```

Next authorize your user so you can read their feed. Each member can be connected to their own feed, so you can read the feed from multiple users.
Go to the Security admin and select the member you want to connect to instagram. 
Here you hava a button to authenticate instagram, this will redirect to a Instagram authentication screen where the user can connect themselves (make sure you are loggen in with the account you want to connect).
Or you have the option to paste a generated user token, after a save you should see the profile is connected.

### Available methods

The default behaviour runs a task that checks media from authenticated users. This data gets stored in `Broarm\Instagram\Model\InstagramMediaObject`'s. 
The simplest way to get started would by by running the task either by cron or by hand and querying the `Broarm\Instagram\Model\InstagramMediaObject`.

But you could also create your own tasks or, request the api directly, with the following methods.

```php
$member = Security::getCurrentUser();
$instagram = new \Broarm\Instagram\InstagramClient($member->InstagramAccessToken);
 
// Get the media from the user connected to the access token
$instagram->getUserMedia();

// Get the media for the given user id
$instagram->getUserMedia($member->InstagramID); 

// The above calls return a simple array with media id's 
// Use this call to get the media for the given id
$instagram->getMedia($id);
```
