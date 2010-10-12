# Twitter Comments

Comment plugin to use twitter as the source for comments.

This is very, very rudimentary at the moment and is a work-in-progress.

Special thanks to Happy Cog for the inspiration: (http://cog.gd/8)[http://cog.gd/8]

## How Twitter Comments Works

Twitter Comments (TC) uses the built-in WordPress commenting system, leaving it untouched. The only difference is that however it requires commentors 
to authorize your blog as a Twitter application which allows TC will automatically tweet the comment as the submitters status on Twitter. WordPress 
comment management and all built in options remain unaffected so you can do everything you could before (delete, edit, spam, pending approval, etc.)
In the case of pending approval, the tweet will still be posted to twitter but will not appear on your blog until approved.

All Twitter Comments comments are stored locally in the WordPress comment system.  

Note: WordPress comments require an email and author. This plugin uses the twitter username as the author and 
pre-fills the email address as [twitterusername]@twitter.com. This **isn't** a valid email adddress!

## INSTALLATION

1. Create application for your site at http://dev.twitter.com
 * When entering the callback url use `[your site's WordPress URL]/wp-content/plugins/twitter-comments/callback.php`

2. Copy and paste Consumer Key and Consumer Secret and Callback Url into the settings for Twitter Comments in the WordPress admin panel.

3. Enter the appropriate URL shortener API url in the admin panel. I highly suggest you get your own!

4. Start commenting :) 

## TODO's

* Cron to search and automatically digest Twitter tweets that reference the appropriate short url.
* Blog post as a comment options.
* Allow standard WordPress comments along side twitter comments.
* Overall ui improvements.