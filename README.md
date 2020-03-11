# Silverstripe Instagram

Add support for the [Instagram Basic Display API](https://developers.facebook.com/docs/instagram-basic-display-api) to your website.
Creates a tab in on the Member section where content authors can authenticate with your Instagram app.
The module contains a task that fetches the authenticated member's images form instagram.

![authenticate](screenshots/authenticate.png)

![authenticated](screenshots/authenticated.png)

You can also paste a token from your app on [developers.facebook.com](https://developers.facebook.com/) with the [User Token Generator](https://developers.facebook.com/docs/instagram-basic-display-api/overview#user-token-generator).
That way you can easily set up a connection for a client, your client only needs to accept the invitation from your facebook app. 

The default behaviour runs a task that checks media from authenticated users. This data gets stored in `InstagramMediaObject`'s. 
The simplest way to get started would by by running the task either by cron or by hand and querying the `InstagramMediaObject`.

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
