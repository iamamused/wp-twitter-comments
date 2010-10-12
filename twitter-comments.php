<?php
/*
Plugin Name: Twitter Comments
Version: 1.0
Description: Integrates twitter with the wordpress commentig system. Commentors must authenticate with twitter before post is created.
Contributors: Jeffrey Sambells
Author: Jeffrey Sambells
Author URI: http://jeffreysambells.com/
Plugin URI: 
Donate Link: 
Update Server: 
Disclaimer: No warranty is provided. PHP 5 required.
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
	
	$postId = $_POST['comment_post_ID'];

	/* statuses/update */
	$parameters = array(
		'status' => $_POST['comment']
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
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php 
} 


/*

function comment_form( $args = array(), $post_id = null ) {
	global $user_identity, $id;

	if ( null === $post_id )
		$post_id = $id;
	else
		$id = $post_id;

	$commenter = wp_get_current_commenter();

	$req = get_option( 'require_name_email' );
	$aria_req = ( $req ? " aria-required='true'" : '' );
	$fields =  array(
		'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name' ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
		            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
		'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email' ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
		            '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
		'url'    => '<p class="comment-form-url"><label for="url">' . __( 'Website' ) . '</label>' .
		            '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>',
	);

	$required_text = sprintf( ' ' . __('Required fields are marked %s'), '<span class="required">*</span>' );
	$defaults = array(
		'fields'               => apply_filters( 'comment_form_default_fields', $fields ),
		'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
		'must_log_in'          => '<p class="must-log-in">' .  sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
		'logged_in_as'         => '<p class="logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
		'comment_notes_before' => '<p class="comment-notes">' . __( 'Your email address will not be published.' ) . ( $req ? $required_text : '' ) . '</p>',
		'comment_notes_after'  => '<p class="form-allowed-tags">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s' ), ' <code>' . allowed_tags() . '</code>' ) . '</p>',
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'title_reply'          => __( 'Leave a Reply' ),
		'title_reply_to'       => __( 'Leave a Reply to %s' ),
		'cancel_reply_link'    => __( 'Cancel reply' ),
		'label_submit'         => __( 'Post Comment' ),
	);

	$args = wp_parse_args( $args, apply_filters( 'comment_form_defaults', $defaults ) );

	?>
		<?php if ( comments_open() ) : ?>
			<?php do_action( 'comment_form_before' ); ?>
			<div id="respond">
				<h3 id="reply-title"><?php comment_form_title( $args['title_reply'], $args['title_reply_to'] ); ?> <small><?php cancel_comment_reply_link( $args['cancel_reply_link'] ); ?></small></h3>
				<?php if ( get_option( 'comment_registration' ) && !is_user_logged_in() ) : ?>
					<?php echo $args['must_log_in']; ?>
					<?php do_action( 'comment_form_must_log_in_after' ); ?>
				<?php else : ?>
					<form action="<?php echo site_url( '/wp-comments-post.php' ); ?>" method="post" id="<?php echo esc_attr( $args['id_form'] ); ?>">
						<?php do_action( 'comment_form_top' ); ?>
						<?php if ( is_user_logged_in() ) : ?>
							<?php echo apply_filters( 'comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity ); ?>
							<?php do_action( 'comment_form_logged_in_after', $commenter, $user_identity ); ?>
						<?php else : ?>
							<?php echo $args['comment_notes_before']; ?>
							<?php
							do_action( 'comment_form_before_fields' );
							foreach ( (array) $args['fields'] as $name => $field ) {
								echo apply_filters( "comment_form_field_{$name}", $field ) . "\n";
							}
							do_action( 'comment_form_after_fields' );
							?>
						<?php endif; ?>
						<?php echo apply_filters( 'comment_form_field_comment', $args['comment_field'] ); ?>
						<?php echo $args['comment_notes_after']; ?>
						<p class="form-submit">
							<input name="submit" type="submit" id="<?php echo esc_attr( $args['id_submit'] ); ?>" value="<?php echo esc_attr( $args['label_submit'] ); ?>" />
							<?php comment_id_fields(); ?>
						</p>
						<?php do_action( 'comment_form', $post_id ); ?>
					</form>
				<?php endif; ?>
			</div><!-- #respond -->
			<?php do_action( 'comment_form_after' ); ?>
		<?php else : ?>
			<?php do_action( 'comment_form_comments_closed' ); ?>
		<?php endif; ?>
	<?php
}




Comment, Ping, and Trackback Actions
comment_closed 
Runs when the post is marked as not allowing comments while trying to display comment entry form. Action function argument: post ID.
comment_id_not_found 
Runs when the post ID is not found while trying to display comments or comment entry form. Action function argument: post ID.
comment_flood_trigger 
Runs when a comment flood is detected, just before wp_die is called to stop the comment from being accepted. Action function arguments: time of previous comment, time of current comment.
comment_on_draft 
Runs when the post is a draft while trying to display a comment entry form or comments. Action function argument: post ID.
comment_post 
Runs just after a comment is saved in the database. Action function arguments: comment ID, approval status ("spam", or 0/1 for disapproved/approved).
edit_comment 
Runs after a comment is updated/edited in the database. Action function arguments: comment ID.
delete_comment 
Runs just before a comment is deleted. Action function arguments: comment ID.
pingback_post 
Runs when a ping is added to a post. Action function argument: comment ID.
pre_ping 
Runs before a ping is fully processed. Action function arguments: array of the post links to be processed, and the "pung" setting for the post.
trackback_post 
Runs when a trackback is added to a post. Action function argument: comment ID.
wp_blacklist_check 
Runs to check whether a comment should be blacklisted. Action function arguments: author name, author email, author URL, comment text, author IP address, author's user agent (browser). Your function can execute a wp_die to reject the comment, or perhaps modify one of the input arguments so that it will contain one of the blacklist keywords set in the WordPress options.
wp_set_comment_status 
Runs when the status of a comment changes. Action function arguments: comment ID, status string indicating the new status ("delete", "approve", "spam", "hold").

*/