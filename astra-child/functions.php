<?php
/**
 * Keystone Possibilities Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Keystone Possibilities Child
 * @since 1.0.0
 */

/**
 * Enqueue parent theme styles.
 */
function astra_child_enqueue_styles() {
	wp_enqueue_style( 'astra-theme-css', get_template_directory_uri() . '/style.css', array(), ASTRA_THEME_VERSION, 'all' );
	wp_enqueue_style( 'astra-child-css', get_stylesheet_directory_uri() . '/style.css', array( 'astra-theme-css' ), '1.0.0', 'all' );
}
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles', 15 );

/**
 * Handle custom 301 redirects to fix 404 errors and GSC broken links.
 */
function keystone_custom_redirects() {
	// Only process on frontend
	if ( is_admin() ) {
		return;
	}

	$request_uri = $_SERVER['REQUEST_URI'];

	// Parse the URL to separate path and query string
	$parsed_url = wp_parse_url( $request_uri );
	$path = isset( $parsed_url['path'] ) ? trailingslashit( $parsed_url['path'] ) : '/';
	$query = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';

	$redirect_to = false;

	// Specific redirect for contact page
	if ( '/contact-2/' === $path ) {
		$redirect_to = home_url( '/contact/' );
	}

	// List of broken slugs to redirect to the homepage
	$homepage_redirects = array(
		'/1121/',
		'/fulton/',
		'/saint-a/',
		'/foundation/',
		'/project-manager/',
		'/2025/10/07/step-/',
		'/2025/11/13/a-bc-/',
		'/20171020_153133/',
		'/20171020_153133-1/',
		'/final-logo-ks/',
		'/final-logo-ks4/',
		'/final-logo-ks-2/',
		'/final-logo-ks4-w/',
		'/final-logo-ks4-w-1/',
		'/noun-framing-203197/',
		'/cropped-final-logo-ks-jpg/',
		'/cropped-final-logo-ks-png/',
		'/screenshot-2023-10-10-at-4-37-35-pm/',
	);

	if ( in_array( $path, $homepage_redirects, true ) ) {
		$redirect_to = home_url( '/' );
	}

	if ( $redirect_to ) {
		// Append query string if it exists to preserve parameters like UTM tags
		$redirect_url = $redirect_to . $query;
		wp_safe_redirect( $redirect_url, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'keystone_custom_redirects' );

/**
 * Redirect attachment pages to parent post, direct file URL, or homepage.
 */
function keystone_attachment_redirect() {
	if ( is_admin() ) {
		return;
	}

	if ( is_attachment() ) {
		global $post;
		if ( ! empty( $post->post_parent ) ) {
			wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
			exit;
		} else {
			$attachment_url = wp_get_attachment_url( $post->ID );
			if ( $attachment_url ) {
				wp_safe_redirect( $attachment_url, 301 );
				exit;
			} else {
				wp_safe_redirect( home_url( '/' ), 301 );
				exit;
			}
		}
	}
}
add_action( 'template_redirect', 'keystone_attachment_redirect' );

/**
 * Add noindex, follow to search result pages to prevent indexing of search queries.
 */
function keystone_noindex_search_results() {
	if ( is_search() ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}
add_action( 'wp_head', 'keystone_noindex_search_results' );

/**
 * Inject VideoObject Schema for Blog Posts to Fix GSC Video Indexing Errors
 */
function keystone_inject_video_schema() {
    if ( is_single() ) {
        global $post;
        
        // Only output if the post has video content or youtube link
        $content = $post->post_content;
        $has_video = preg_match('/youtube\.com|youtu\.be|<video/i', $content);
        
        if ( $has_video ) {
            $post_url = get_permalink();
            $post_title = get_the_title();
            $post_date = get_the_date('c');
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'full');
            
            // Fallbacks
            if ( ! $thumbnail_url ) {
                $thumbnail_url = home_url( '/wp-content/uploads/default-thumbnail.jpg' );
            }
            
            $description = wp_trim_words( strip_tags( $content ), 20 );
            if ( empty( $description ) ) {
                $description = $post_title;
            }
            
            $schema = array(
                '@context' => 'https://schema.org',
                '@type'    => 'VideoObject',
                'name'     => $post_title,
                'description' => $description,
                'thumbnailUrl' => array(
                    $thumbnail_url
                ),
                'uploadDate' => $post_date,
                'contentUrl' => $post_url,
                'embedUrl'   => $post_url,
                'publisher' => array(
                    '@type' => 'Organization',
                    'name'  => 'Keystone Possibility',
                    'logo'  => array(
                        '@type' => 'ImageObject',
                        'url'   => home_url( '/wp-content/uploads/logo.png' )
                    )
                )
            );
            
            echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        }
    }
}
add_action( 'wp_head', 'keystone_inject_video_schema' );

/**
 * PWA: Add Manifest and Service Worker
 */
function keystone_pwa_header() {
    echo '<link rel="manifest" href="' . get_stylesheet_directory_uri() . '/manifest.json">' . "\n";
    echo '<meta name="theme-color" content="#1a1a1a">' . "\n";
}
add_action( 'wp_head', 'keystone_pwa_header' );

function keystone_pwa_footer() {
    ?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?php echo get_stylesheet_directory_uri(); ?>/sw.js')
            .then(function(registration) {
                console.log('PWA ServiceWorker registered with scope: ', registration.scope);
            }, function(err) {
                console.log('PWA ServiceWorker registration failed: ', err);
            });
        });
    }
    </script>
    <?php
}
add_action( 'wp_footer', 'keystone_pwa_footer' );

/**
 * Dynamic content filter for About Us pages to improve Wayne Stevenson's E-E-A-T and Knowledge Panel visibility.
 */
function keystone_possibilities_filter_about_page_content( $content ) {
    if ( is_page( 'about-us-general-contractor-squamish' ) || is_page( 'about' ) || is_page( 'about-us' ) ) {
        // We will dynamically swap out generic headings for the H2/H3 tag "Wayne Stevenson, Founder & Principal"
        // and inject 3-4 natural mentions of his name for keyword density.
        
        // Replace any generic H2 or H3 tag with the target tag
        $content = preg_replace('/<h[23][^>]*>About Us<\/h[23]>/i', '<h2>Wayne Stevenson, Founder & Principal</h2>', $content);
        $content = preg_replace('/<h[23][^>]*>Our Principal<\/h[23]>/i', '<h2>Wayne Stevenson, Founder & Principal</h2>', $content);
        
        // Ensure Wayne Stevenson's name is mentioned at least 3-4 times naturally in context.
        // If his name isn't already in the content enough, inject a highly E-E-A-T professional bio blurb at the top.
        if ( substr_count( strtolower( $content ), 'wayne stevenson' ) < 3 ) {
            $bio_intro = '<p><strong>Wayne Stevenson</strong> is the Founder and Principal of Keystone Possibilities. With over two decades of engineering-grade oversight and a comprehensive background in civil construction and metabolic health modeling, <strong>Wayne Stevenson</strong> brings rigorous risk mitigation to every luxury custom home build. As a licensed BC Builder (#52603), <strong>Wayne Stevenson</strong> directly manages subcontractor bids to provide absolute fiduciary transparency.</p>';
            $content = $bio_intro . $content;
        }
    }
    return $content;
}
add_filter( 'the_content', 'keystone_possibilities_filter_about_page_content' );

/**
 * Intercept requests to /wolverine-stack/ and serve the wolverine stack blog post programmatically.
 */
function keystone_serve_wolverine_stack_post() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $parsed_url = wp_parse_url( $request_uri );
    $path = isset( $parsed_url['path'] ) ? trim( $parsed_url['path'], '/' ) : '';

    if ( 'wolverine-stack' === $path ) {
        $template_path = get_stylesheet_directory() . '/wolverine-post-template.php';
        if ( file_exists( $template_path ) ) {
            // Serve template
            include $template_path;
            exit;
        }
    }
}
add_action( 'template_redirect', 'keystone_serve_wolverine_stack_post', 5 );

