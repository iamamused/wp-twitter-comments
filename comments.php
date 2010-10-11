<?php
	// Do not delete these lines
	if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME'])) {
		die ('Please do not load this page directly. Thanks!');
	}

	if ( post_password_required() ) {
		return;
	}
?>

<?php if ( have_comments() ) : ?>

	<section class="comments" id="comments">
		<h2><?php comments_number('No comments', 'One comment', '% comments' );?></h2>

		<dialog>
			<?php wp_list_comments('callback=html5boilerplate_comment'); ?>
		</dialog>
		
		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
		<nav>
			<ul>
				<li><?php previous_comments_link('&laquo; Older Comments'); ?></li>
				<li><?php next_comments_link('Newer Comments &raquo;' ) ?></li>
			</ul>
		</nav>
		<?php endif; ?>
	</section>

<?php else : // This is displayed if there are no comments so far ?>

		<?php if ( comments_open() ) : ?>
		<!-- If there are no comments and comments are open -->
		<?php else : // comments are closed ?>
		<!-- If there are no comments and comments are closed -->
		<?php endif; ?>

<?php endif; ?>

<?php if ( comments_open() ) : ?>

	<section id="respond" class="post-comments comment-form">
		<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post">
		<dl>	
			<dt><label for="comment">Tweet your thoughts</label></dt>
			<dd>
				<textarea name="comment" id="comment" cols="100%" rows="3" tabindex="4"></textarea>
				<p>120 char or less please. The link to this post will be appended automatically.</p>
				</dd>
			<dt><strong>Note: I will not now, or ever, tweet without your permission.</strong></dt>
			<dd><input name="submit" type="submit" id="submit" tabindex="5" value="Authorize / Tweet" /></dd>
		</dl>
	
		<?php comment_id_fields(); ?>
		<?php do_action('comment_form', $post->ID); ?>
	
		</form>
	</div>
</div>

<hr />

<?php endif; ?>