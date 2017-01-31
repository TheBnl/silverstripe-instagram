# Silverstripe Instagram

**Still in development**

Add instagram api support to you website! Creates a tab in on the Member section where content authors can authenticate with your Instagram app.
The module contains a task that fetches the authenticated member's images form instagram.

![authenticate](screenshots/authenticate.png)

![authenticated](screenshots/authenticated.png)

Some of the features are only available to non-sandbox clients.
By default, sandbox mode will only return the last 20 media items from authenticated users. 

The default behaviour runs a task that checks media from authenticated users. This data gets stored in `InstagramMediaObject`'s. 
The simplest way to get started would by by running the task either by cron or by hand and querying the `InstagramMediaObject`.

But you could also create your own tasks or, request the api directly, with the following methods.

```php
$instagram = Instagram::create();
 
// All the following request go trough this basic method.
// You could do most of the available API requests with this method. 
$instagram->get($node = null, $limit = null);
 
// Get the media of the current (authentiated) user
$instagram->getCurrentUserMedia($limit = null);
 
// Get the media of a given "Silverstripe" member, only works if the member is authenticated
// See ImportMediaTasks.php for a implementation 
$instagram->getMemberMedia(Member $member, $limit = null)
 
// Get media by tag from the pool of authenticated members
$instagram->getTaggedMedia($tagName, $limit = null)
```
