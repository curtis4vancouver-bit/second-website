<?php
/**
 * Bird image
 *
 * This pattern is used only to reference a dynamic image URL.
 * It does not appear in the inserter.
 */
return array(
	'title'    => __( 'Heading and bird image', 'twentytwentytwo' ),
	'inserter' => false,
	'content'  => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0px","bottom":"clamp(5rem, 8vw, 7rem)"}}},"layout":{"inherit":true}} -->
					<div class="wp-block-group alignfull" style="padding-top:0px;padding-bottom:clamp(5rem, 8vw, 7rem);"><!-- wp:heading {"align":"wide","style":{"typography":{"fontSize":"var(--wp--custom--typography--font-size--colossal, clamp(3.25rem, 8vw, 6.25rem))","lineHeight":"1.15"}}} -->
					<h2 class="alignwide" style="font-size:var(--wp--custom--typography--font-size--colossal, clamp(3.25rem, 8vw, 6.25rem));line-height:1.15">' . wp_kses_post( __( '<em>The Hatchery</em>: a blog about my adventures in bird watching', 'twentytwentytwo' ) ) . '</h2>
					<!-- /wp:heading --></div>
					<!-- /wp:group -->

					<!-- wp:image {"align":"wide","width":2000,"height":474,"sizeSlug":"full","linkDestination":"none"} -->
					<figure class="wp-block-image alignwide size-full is-resized"><img src="' . esc_url( get_template_directory_uri() ) . '/assets/images/flight-path-on-transparent-d.png" alt="' . esc_attr__( 'Illustration of a bird flying.', 'twentytwentytwo' ) . '" width="2000" height="474"/></figure>
					<!-- /wp:image -->',
);
