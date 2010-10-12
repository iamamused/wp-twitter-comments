<?php
/*
Plugin Name: Twitter Comments
Plugin URI: http://github.com/iamamused/twitter-comments
Description: Integrates Twitter with the WordPress commentig system. Commentors must authorize the blog as an app before post is created and tweeted.
Version: 1.0
Author: Jeffrey Sambells
Author URI: http://jeffreysambells.com
License: GPL2

	Copyright 2010  Jeffrey Sambells  (email : github@tropicalpixels.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Requires at least: 2.7
*/

session_start();
require_once dirname(__FILE__) . '/twitteroauth/twitteroauth.php';


function tweet_comment_init() {
	wp_enqueue_script('jquery');
}
add_action('init', 'tweet_comment_init');

/*
Runs in standard themes to insert the comment form. Action function argument: post ID.
*/
function tweet_comment_comments_template( $path ) {
	// Override theme tempalte.
	return dirname( __FILE__) . '/' . 'comments.php';
}
add_filter('comments_template', 'tweet_comment_comments_template');


/*
Runs in standard themes to insert the comment form. Action function argument: post ID.
*/
function tweet_comment_comment_form_defaults( $defaults ) {
	return $defaults;	
}
add_filter('comment_form_defaults', 'tweet_comment_comment_form_defaults');


/*
Applied to the comment data prior to any other processing, 
when saving a new comment in the database. 
Function arguments: comment data array, with indices "comment_post_ID", "comment_author", "comment_author_email", "comment_author_url", "comment_content", "comment_type", and "user_ID".
*/
function tweet_comment_preprocess_comment( $data ) {
	return $data;
}
add_filter('preprocess_comment', 'tweet_comment_preprocess_comment');


function tweet_comment_pre_comment_on_post( $postId ) {
	// Grab the comment and see if we've authorized twitter yet.
	//http://shiflett.org/blog/2010/sep/twitter-oauth
	if (empty($_SESSION['status']) || $_SESSION['status'] != 'verified') {
		$_SESSION['twitter_comments_post_storage'] = serialize($_POST);
		require dirname(__FILE__) . '/redirect.php';
		die();	
	}	
	
	/* Get user access tokens out of the session. */
	$access_token = $_SESSION['access_token'];
	
	/* Create a TwitterOauth object with consumer/user tokens. */
	$connection = new TwitterOAuth(get_option('CONSUMER_KEY'), get_option('CONSUMER_SECRET'), $access_token['oauth_token'], $access_token['oauth_token_secret']);
	
	//$content = $connection->get('account/verify_credentials');
	
	// Tweet it on behalf of the commentor.

	$short = ' ' . twitter_comments_get_short_for_post( $_POST['comment_post_ID'] );

	/* statuses/update */
	$parameters = array(
		'status' => substr( stripslashes( $_POST['comment'] ), 0, 140 - strlen( $short ) ) . $short,
	);

	$status = $connection->post('statuses/update', $parameters);
	
	switch ($connection->http_code) {
		case '200':
	    case '304':
	    break;
	    default:
	    	// There was an error.
	    	//header('content-type: text/plain'); var_dump($_POST, $status, $connection); die('error');
	    break;
	}
	
	// Now store it in wordpress too.
	
	// AUTHOR and EMAIL are required.
	$_POST['author'] = $_SESSION['access_token']['screen_name'];
	$_POST['email'] = $_SESSION['access_token']['screen_name'] . '@twitter.com';
	$_POST['url'] = 'http://twitter.com/' . $_SESSION['access_token']['screen_name'];
	//$_POST['comment'];
	
}
add_action('pre_comment_on_post', 'tweet_comment_pre_comment_on_post');


//////////////////////
// Admin settings
//////////////////////


// create custom plugin settings menu
add_action('admin_menu', 'twitter_comments_create_menu');

function twitter_comments_create_menu() {

	//create new top-level menu
	add_menu_page('Twitter Comments Plugin Settings', 'Twitter Comments Settings', 'administrator', __FILE__, 'twitter_comments_settings_page',plugins_url('/images/icon.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'twitter_comments_register_mysettings' );
}


function twitter_comments_register_mysettings() {
	register_setting( 'twitter-comments-settings-group', 'CONSUMER_KEY' );
	register_setting( 'twitter-comments-settings-group', 'CONSUMER_SECRET' );
	register_setting( 'twitter-comments-settings-group', 'OAUTH_CALLBACK' );
	register_setting( 'twitter-comments-settings-group', 'SHORT_PATTERN' );
}

function twitter_comments_settings_page() {
?>
<div class="wrap">
<h2>Twitter Comments</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'twitter-comments-settings-group' ); ?>

    <table class="form-table">
        <tr valign="top">
        <th scope="row">Twitter CONSUMER_KEY</th>
        <td><input type="text" name="CONSUMER_KEY" value="<?php echo get_option('CONSUMER_KEY'); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Twitter CONSUMER_SECRET</th>
        <td><input type="text" name="CONSUMER_SECRET" value="<?php echo get_option('CONSUMER_SECRET'); ?>" /></td>
        </tr>
        
		<tr valign="top">
		<th scope="row">Twitter OAUTH_CALLBACK</th>
		<td><input type="text" name="OAUTH_CALLBACK" value="<?php echo get_option('OAUTH_CALLBACK'); ?>" /></td>
		</tr>
		
		<tr valign="top">
		<th scope="row">API URL for shortener. Use %s for the location if the long url string.</th>
		<td>
			<p>For example: http://tinyurl.com/api-create.php?url=%s</p>
			<p>The URL service must use HTTP GET requests and output a plain-text short URL including the http prefix (like <a href="http://tinyurl.com/api-create.php?url=http://jeffreysambells.com">this</a>).</p>
			<p>HTTP POST is not supported. Use <code>%s</code>code> as the vaiable for the long url.</p>
			<input type="text" name="SHORT_PATTERN" value="<?php 
				$url = get_option('SHORT_PATTERN');
				if (strlen($url) == 0) $url = 'http://tinyurl.com/api-create.php?url=%s';
				echo $url; 
			?>" /></td>
		</tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php 
} 

function twitter_comments_get_short_for_post( $postId ) {
	$shortLink = get_post_meta($postId, 'TWITTER_COMMENTS_SHORT_URL', true );
	if (!$shortLink) {
		$permalink = get_permalink( $postId );
		$shortLink = file_get_contents( sprintf( get_option( 'SHORT_PATTERN' ), $permalink ) );
		add_post_meta($postId, 'TWITTER_COMMENTS_SHORT_URL', $shortLink, true );
	} 
	return $shortLink;
}