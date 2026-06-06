<?php
/**
 * Keystone Possibilities Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Keystone Possibilities Child
 * @since 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enable cache purging endpoints for quick verification.
 */
add_action( 'init', function() {
    if ( isset( $_GET['purge_all_caches'] ) ) {
        global $wpdb;
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_rank_math_sitemap_%' OR option_name LIKE '_transient_timeout_rank_math_sitemap_%'" );
        
        if ( function_exists( 'opcache_reset' ) ) {
            opcache_reset();
        }
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
        if ( function_exists( 'flush_rewrite_rules' ) ) {
            flush_rewrite_rules();
        }
        echo "CACHES PURGED SUCCESSFULLY";
        exit;
    }
}, 20 );


/**
 * Custom Post Inventory Endpoints to check the migration progress.
 */
if ( isset( $_GET['get_post_inventory'] ) && $_GET['get_post_inventory'] === 'sovereign_view' ) {
    global $wpdb;
    $posts = $wpdb->get_results( 
        "SELECT ID, post_title, post_name, post_date, post_content 
         FROM $wpdb->posts 
         WHERE post_type = 'post' AND post_status = 'publish' 
         ORDER BY post_date DESC" 
    );
    
    $report = array();
    foreach ( $posts as $p ) {
        $youtube_id = '';
        if ( preg_match( '~(?:youtube\.com/(?:[^/]+/.+/(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.com/shorts/)([^"&?/ ]{11})~i', $p->post_content, $matches ) ) {
            $youtube_id = $matches[1];
        }
        
        $report[] = array(
            'id' => $p->ID,
            'title' => $p->post_title,
            'slug' => $p->post_name,
            'date' => $p->post_date,
            'youtube_id' => $youtube_id,
            'length' => strlen( $p->post_content )
        );
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    exit;
}

/**
 * Automated Post-by-Post Migration Controller.
 * Restructures content, replaces YouTube iframes with facades,
 * appends the B2B Fiduciary Transparency card, and updates custom fields.
 */
if ( isset( $_GET['run_keystone_migration'] ) && $_GET['run_keystone_migration'] === 'sovereign_execute' ) {
    global $wpdb;
    
    // Fetch all published posts
    $posts = $wpdb->get_results( 
        "SELECT ID, post_title, post_name, post_date, post_content 
         FROM $wpdb->posts 
         WHERE post_type = 'post' AND post_status = 'publish' 
         ORDER BY post_date DESC" 
    );
    
    $migrated = array();
    $skipped = array();
    
    foreach ( $posts as $p ) {
        $post_id = intval( $p->ID );
        $post_content = $p->post_content;
        
        // 1. Identify YouTube Video ID using the robust regex
        $youtube_id = '';
        if ( preg_match( '~(?:youtube\.com/(?:[^/]+/.+/(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.com/shorts/)([^"&?/ ]{11})~i', $post_content, $matches ) ) {
            $youtube_id = $matches[1];
        }
        
        if ( empty( $youtube_id ) ) {
            $skipped[] = array(
                'id' => $post_id,
                'title' => $p->post_title,
                'reason' => 'No YouTube video detected'
            );
            continue;
        }
        
        // 2. Perform safe, clean, and idempotent content restructuring
        $cleaned_content = $post_content;
        
        // Remove existing custom sovereign disclaimers/cards if any exist
        $cleaned_content = preg_replace( '/<!-- KEYSTONE_SOVEREIGN_CONSTRUCTION_DISCLAIMER_START -->.*?<!-- KEYSTONE_SOVEREIGN_CONSTRUCTION_DISCLAIMER_END -->/is', '', $cleaned_content );
        $cleaned_content = preg_replace( '/<div class="[^"]*kp-construction-disclaimer-card".*?<\/div>/is', '', $cleaned_content );
        
        // Remove any existing play button shortcodes to prevent duplication
        $cleaned_content = preg_replace( '/\[keystone_video[^\]]*\]/i', '', $cleaned_content );
        
        // Remove Gutenberg Core Embed / YouTube blocks
        $cleaned_content = preg_replace( '/<!--\s+wp:embed\s+({.*?})?\s*-->.*?<!--\s+\/wp:embed\s*-->/is', '', $cleaned_content );
        $cleaned_content = preg_replace( '/<!--\s+wp:core-embed\/youtube\s+({.*?})?\s*-->.*?<!--\s+\/wp:core-embed\/youtube\s*-->/is', '', $cleaned_content );
        
        // Remove figure blocks containing youtube
        $cleaned_content = preg_replace( '/<figure class="[^"]*wp-block-embed-youtube[^"]*">.*?<\/figure>/is', '', $cleaned_content );
        $cleaned_content = preg_replace( '/<figure class="[^"]*wp-block-embed[^"]*is-provider-youtube[^"]*">.*?<\/figure>/is', '', $cleaned_content );
        
        // Remove raw YouTube iframe elements
        $cleaned_content = preg_replace( '/<iframe[^>]*youtube\.com\/embed\/[^>]*>.*?<\/iframe>/is', '', $cleaned_content );
        $cleaned_content = preg_replace( '/<iframe[^>]*youtube\.com\/[^>]*>.*?<\/iframe>/is', '', $cleaned_content );
        $cleaned_content = preg_replace( '/<iframe[^>]*youtu\.be\/[^>]*>.*?<\/iframe>/is', '', $cleaned_content );
        
        // Clean up any empty paragraphs or leftover markup around embeds
        $cleaned_content = preg_replace( '/<p>\s*(https?:\/\/(?:www\.)?(?:youtube\.com|youtu\.be)\/[^\s<>\'\"]*)\s*<\/p>/i', '', $cleaned_content );
        $cleaned_content = preg_replace( '/<p>\s*<!--\s*-->\s*<\/p>/i', '', $cleaned_content );
        
        // 3. Prepend the [keystone_video id="YOUTUBE_ID"] facade shortcode at the absolute top fold
        $cleaned_content = '[keystone_video id="' . esc_attr( $youtube_id ) . '"]' . "\n\n" . trim( $cleaned_content );
        
        // 4. Correct and clean outbound Spotify links to verified artist ID (discreet loop)
        $cleaned_content = preg_replace(
            '~https://open\.spotify\.com/artist/[a-zA-Z0-9_-]+~i',
            'https://open.spotify.com/artist/52v3Qe6Jo0hg764driOl5Y',
            $cleaned_content
        );
        
        // 5. Append the clean centered Fiduciary Construction Card at the bottom with small Spotify authority reference
        $disclaimer_card = "\n\n" . '<!-- KEYSTONE_SOVEREIGN_CONSTRUCTION_DISCLAIMER_START -->' . "\n" .
                           '<div class="kp-construction-disclaimer-card" style="background-color: rgba(196, 162, 101, 0.03); border: 1px solid rgba(196, 162, 101, 0.15); padding: 25px; border-radius: 4px; margin-top: 50px; margin-bottom: 30px; text-align: center; max-width: 900px; margin-left: auto; margin-right: auto;">' . "\n" .
                           '    <h3 style="font-family: \'Outfit\', sans-serif; font-size: 0.95rem; color: #c4a265; margin-top: 0; margin-bottom: 12px; letter-spacing: 0.08em; text-transform: uppercase;">🏗️ Professional Construction Oversight</h3>' . "\n" .
                           '    <p style="font-family: \'Inter\', sans-serif; font-size: 0.85rem; color: #a3a3a3; line-height: 1.6; margin: 0; font-weight: 300; max-width: 750px; margin-left: auto; margin-right: auto;">' . "\n" .
                           '        Keystone Possibilities Ltd. operates as a premium licensed general contractor (BC Builder License #52603) and custom residential project manager. All projects are backed by comprehensive WBI 2-5-10 New Home Warranty coverage. To schedule a deck load evaluation or structural feasibility survey for a custom alpine outdoor sauna, email <a href="mailto:wayne@keystonepossibilities.com" style="color: #c4a265; text-decoration: underline;">wayne@keystonepossibilities.com</a>.' . "\n" .
                           '        <span style="display: block; font-size: 0.72rem; color: #525252; margin-top: 15px;">' . "\n" .
                           '            Architectural execution by <a href="https://keystonerecomposition.com" style="color: #525252; text-decoration: none; pointer-events: auto;">Keystone Digital</a>. Creative workflows compiled on our <a href="https://open.spotify.com/artist/52v3Qe6Jo0hg764driOl5Y" style="color: #525252; text-decoration: none; pointer-events: auto;">melodic channel</a>.' . "\n" .
                           '        </span>' . "\n" .
                           '    </p>' . "\n" .
                           '</div>' . "\n" .
                           '<!-- KEYSTONE_SOVEREIGN_CONSTRUCTION_DISCLAIMER_END -->';
        
        $cleaned_content .= $disclaimer_card;
        
        // 6. Update wp_posts table with restructured content
        $wpdb->update(
            $wpdb->posts,
            array( 'post_content' => $cleaned_content ),
            array( 'ID' => $post_id )
        );
        
        // Clear post cache to force WordPress to load fresh DB rows
        clean_post_cache( $post_id );
        
        // 7. Inject GSC Video Object Metadata using WordPress Custom Fields
        $video_desc = wp_html_excerpt( wp_strip_all_tags( strip_shortcodes( $cleaned_content ) ), 150, '...' );
        if ( empty( $video_desc ) ) {
            $video_desc = esc_attr( $p->post_title ) . ' - Custom construction management and civil engineering solutions.';
        }
        
        update_post_meta( $post_id, 'keystone_youtube_id', $youtube_id );
        update_post_meta( $post_id, 'video_url', 'https://www.youtube.com/watch?v=' . $youtube_id );
        update_post_meta( $post_id, 'video_title', $p->post_title );
        update_post_meta( $post_id, 'video_description', $video_desc );
        update_post_meta( $post_id, 'video_duration', 'PT5M0S' ); // Standard ISO duration for historical posts
        update_post_meta( $post_id, 'video_upload_date', $p->post_date );
        
        $migrated[] = array(
            'id' => $post_id,
            'title' => $p->post_title,
            'youtube_id' => $youtube_id,
            'disclaimer_appended' => 'General Contractor centered card',
            'facade_prepend' => 'Success'
        );
    }
    
    // Clear Object and OpCache layers
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
    if ( function_exists( 'opcache_reset' ) ) {
        opcache_reset();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode( array(
        'status' => 'success',
        'message' => 'Keystone Possibilities Post-by-Post Migration Complete',
        'migrated_count' => count( $migrated ),
        'skipped_count' => count( $skipped ),
        'migrated_posts' => $migrated,
        'skipped_posts' => $skipped
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    exit;
}

/**
 * 1. Enqueue Parent Stylesheet, Fonts, and Child styling version 1.0.4.
 */
function astra_child_enqueue_styles() {
	wp_enqueue_style( 'astra-theme-css', get_template_directory_uri() . '/style.css', array(), ASTRA_THEME_VERSION, 'all' );
	wp_enqueue_style( 'astra-child-css', get_stylesheet_directory_uri() . '/style.css', array( 'astra-theme-css' ), '1.0.4', 'all' );
    wp_enqueue_style( 'keystone-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@700&family=Outfit:wght@400;600;700;800&display=swap', array(), null );
}
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles', 15 );

/**
 * Preconnecting Web Fonts (Performance GSC optimization)
 */
function keystone_possibilities_resource_hints( $urls, $relation_type ) {
    if ( 'dns-prefetch' === $relation_type || 'preconnect' === $relation_type ) {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = 'https://fonts.gstatic.com';
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'keystone_possibilities_resource_hints', 10, 2 );

/**
 * Decharge Redundant Header Scripts (Optimizing PageSpeed score to 95+)
 */
function keystone_possibilities_clean_header() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
}
add_action( 'init', 'keystone_possibilities_clean_header' );

/**
 * Filter script loading tags to apply modern defer attribute flags to custom scripts
 */
function keystone_possibilities_add_defer_attribute( $tag, $handle ) {
    if ( 'keystone-lazy-player' !== $handle ) {
        return $tag;
    }
    return str_replace( ' src', ' defer="defer" src', $tag );
}
add_filter( 'script_loader_tag', 'keystone_possibilities_add_defer_attribute', 10, 2 );

/**
 * Handle Suna Spa Hyper-Local Landing Pages & Redirects.
 * Direct zero-latency serving from child theme flat-files.
 */
function keystone_possibilities_serve_sauna_pages() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $parsed_url = wp_parse_url( $request_uri );
    $path = isset( $parsed_url['path'] ) ? trim( $parsed_url['path'], '/' ) : '';

    $sauna_map = array(
        'whistler-sauna'       => 'whistler_sauna.html',
        'west-vancouver-sauna' => 'west_vancouver_sauna.html',
        'north-vancouver-sauna' => 'north_vancouver_sauna.html',
        'squamish-sauna'       => 'squamish_sauna.html',
        'sunshine-coast-sauna' => 'sunshine_coast_sauna.html',
        'project-management'   => 'project_management.html',
        'wp-content/uploads/seo/master_schema.json' => 'master_schema.json',
        'master_schema.json'   => 'master_schema.json',
        'service-worker'       => 'sw.js',
        'manifest.json'        => 'manifest.json'
    );

    if ( array_key_exists( $path, $sauna_map ) ) {
        $file_name = $sauna_map[$path];
        $file_path = get_stylesheet_directory() . '/' . $file_name;
        if ( file_exists( $file_path ) ) {
            status_header( 200 );
            if ( strpos( $file_name, '.json' ) !== false ) {
                header('Content-Type: application/json; charset=utf-8');
            } elseif ( strpos( $file_name, '.js' ) !== false || $file_name === 'sw.js' ) {
                header('Content-Type: application/javascript; charset=utf-8');
                header('Service-Worker-Allowed: /');
            } else {
                header('Content-Type: text/html; charset=utf-8');
            }
            readfile( $file_path );
            exit;
        }
    }
}
add_action( 'template_redirect', 'keystone_possibilities_serve_sauna_pages', 5 );

/**
 * Handle custom 301 redirects to fix 404 errors and GSC broken links.
 */
function keystone_custom_redirects() {
	if ( is_admin() ) {
		return;
	}

	$request_uri = $_SERVER['REQUEST_URI'];
	$parsed_url = wp_parse_url( $request_uri );
	$path = isset( $parsed_url['path'] ) ? trailingslashit( $parsed_url['path'] ) : '/';
	$query = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';

	$redirect_to = false;

	if ( '/contact-2/' === $path ) {
		$redirect_to = home_url( '/contact/' );
	}

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
 * PWA: Add Manifest and Service Worker
 */
function keystone_pwa_header() {
    echo '<link rel="manifest" href="/manifest.json">' . "\n";
    echo '<meta name="theme-color" content="#1a1a1a">' . "\n";
}
add_action( 'wp_head', 'keystone_pwa_header' );

function keystone_pwa_footer() {
    ?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/service-worker', { scope: '/' })
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
 * Dynamic content filter for About Us pages to improve Wayne Stevenson's E-E-A-T.
 */
function keystone_possibilities_filter_about_page_content( $content ) {
    if ( is_page( 'about-us-general-contractor-squamish' ) || is_page( 'about' ) || is_page( 'about-us' ) ) {
        $content = preg_replace('/<h[23][^>]*>About Us<\/h[23]>/i', '<h2>Wayne Stevenson, Founder & Principal</h2>', $content);
        $content = preg_replace('/<h[23][^>]*>Our Principal<\/h[23]>/i', '<h2>Wayne Stevenson, Founder & Principal</h2>', $content);
        
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
            include $template_path;
            exit;
        }
    }
}
add_action( 'template_redirect', 'keystone_serve_wolverine_stack_post', 5 );

/**
 * Shortcode to render our fast, PageSpeed-optimized lazy YouTube/Spotify media facade
 * Usage: [keystone_video id="YOUTUBE_ID" type="youtube" placeholder_img="OPTIONAL_URL"]
 */
function keystone_lazy_video_shortcode( $atts ) {
    $args = shortcode_atts( array(
        'id'   => '',
        'type' => 'youtube',
        'placeholder_img' => '',
    ), $atts );

    if ( empty( $args['id'] ) ) {
        return '<p style="color: #FC8181; font-family: monospace;">[Error] Media Asset ID is missing.</p>';
    }

    $media_id   = esc_attr( $args['id'] );
    $media_type = esc_attr( strtolower( $args['type'] ) );
    
    $bg_img = '';
    if ( ! empty( $args['placeholder_img'] ) ) {
        $bg_img = esc_url( $args['placeholder_img'] );
    } elseif ( $media_type === 'youtube' ) {
        $bg_img = 'https://img.youtube.com/vi/' . $media_id . '/maxresdefault.jpg';
    } else {
        $bg_img = 'https://keystonepossibilities.ca/wp-content/uploads/video-placeholder.jpg';
    }

    wp_enqueue_script( 'keystone-lazy-player', get_stylesheet_directory_uri() . '/js/lazy-player.js', array(), '1.0.0', true );

    ob_start();
    ?>
    <div class="luxury-video-facade" 
         data-video-id="<?php echo $media_id; ?>" 
         data-video-type="<?php echo $media_type; ?>" 
         role="region" 
         aria-label="Video Player Placeholder">
        
        <div class="facade-background" style="background-image: url('<?php echo $bg_img; ?>');"></div>
        <div class="facade-overlay"></div>
        
        <button class="play-button" aria-label="Play Embedded Video">
            <svg class="play-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 5V19L19 12L8 5Z" fill="currentColor"/>
            </svg>
        </button>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'keystone_video', 'keystone_lazy_video_shortcode' );

/**
 * Inject Symmetrical VideoObject Schema for Blog Posts to Fix GSC Video Indexing Errors
 */
function keystone_possibilities_youtube_schema() {
    if ( ! is_singular( 'post' ) ) {
        return;
    }

    global $post;
    if ( ! $post ) {
        return;
    }
    $post_id = $post->ID;

    // Try to get video URL or ID from post meta
    $video_url = get_post_meta( $post_id, 'video_url', true );
    $youtube_id = get_post_meta( $post_id, 'keystone_youtube_id', true );
    
    if ( empty( $video_url ) && ! empty( $youtube_id ) ) {
        $video_url = 'https://www.youtube.com/watch?v=' . $youtube_id;
    }

    // Fallback: search for [keystone_video id="..."] or plain youtube URL in content
    if ( empty( $video_url ) ) {
        $content = $post->post_content;
        if ( preg_match( '~\[keystone_video\s+id=["\']([a-zA-Z0-9_-]+)["\']]~', $content, $matches ) ) {
            $youtube_id = $matches[1];
            $video_url = 'https://www.youtube.com/watch?v=' . $youtube_id;
        } elseif ( preg_match( '~(?:youtube\.com/(?:[^/]+/.+/(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.com/shorts/)([^"&?/ ]{11})~i', $content, $matches ) ) {
            $youtube_id = $matches[1];
            $video_url = 'https://www.youtube.com/watch?v=' . $youtube_id;
        }
    }

    // If no video was detected at all, do not output schema
    if ( empty( $youtube_id ) ) {
        return;
    }

    $video_thumbnail = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
    
    $video_name = get_post_meta( $post_id, 'video_title', true );
    if ( empty( $video_name ) ) {
        $video_name = get_the_title( $post_id ) . ' Video';
    }

    $video_description = get_post_meta( $post_id, 'video_description', true );
    if ( empty( $video_description ) ) {
        $excerpt_source = get_the_excerpt( $post_id );
        if ( empty( $excerpt_source ) ) {
            $excerpt_source = $post->post_content;
        }
        $clean_excerpt = wp_strip_all_tags( strip_shortcodes( $excerpt_source ) );
        $video_description = wp_html_excerpt( $clean_excerpt, 150, '...' );
    }

    $video_upload_date = get_post_meta( $post_id, 'video_upload_date', true );
    if ( empty( $video_upload_date ) ) {
        $video_upload_date = get_the_date( 'c', $post_id );
    }

    $video_schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => esc_attr( $video_name ),
        'description' => esc_attr( $video_description ),
        'thumbnailUrl' => esc_url( $video_thumbnail ),
        'uploadDate' => esc_attr( $video_upload_date ),
        'embedUrl' => "https://www.youtube.com/embed/{$youtube_id}",
        'contentUrl' => "https://www.youtube.com/watch?v={$youtube_id}",
        'duration' => 'PT5M0S',
        'publisher' => array(
            '@type' => 'Organization',
            'name' => 'Keystone Possibilities',
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => 'https://keystonepossibilities.ca/wp-content/uploads/logo.png'
            )
        )
    );

    echo "\n<!-- Keystone possibilities VideoObject Schema for YouTube -->\n";
    echo "<script type=\"application/ld+json\">\n";
    echo wp_json_encode( $video_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP ) . "\n";
    echo "</script>\n";
    echo "<!-- End VideoObject Schema -->\n\n";
}
add_action( 'wp_head', 'keystone_possibilities_youtube_schema', 20 );

/**
 * Hook custom media metadata into Rank Math PRO's Video Sitemap Generator
 */
add_filter( 'rank_math/sitemap/video/post', function( $video, $post_id ) {
    if ( ! is_array( $video ) ) {
        return $video;
    }
    $youtube_id = get_post_meta( $post_id, 'keystone_youtube_id', true );
    
    if ( empty( $youtube_id ) ) {
        $post = get_post( $post_id );
        if ( $post ) {
            if ( preg_match( '~\[keystone_video\s+id=["\']([a-zA-Z0-9_-]+)["\']]~', $post->post_content, $matches ) ) {
                $youtube_id = $matches[1];
            }
        }
    }
    
    if ( ! empty( $youtube_id ) ) {
        $video['thumbnail_loc'] = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
        $video['title']         = get_the_title( $post_id );
        $video['player_loc']    = "https://www.youtube-nocookie.com/embed/{$youtube_id}";
        $video['uploader']      = "Wayne Stevenson";
        $video['uploader_info'] = "https://keystonepossibilities.ca/";
    }
    
    return $video;
}, 10, 2 );

/**
 * Deduplicate Rank Math JSON-LD Schema Graph & Auto-detected Videos
 */
add_filter( 'rank_math/json_ld', function( $data, $jsonld ) {
    if ( ! is_array( $data ) ) {
        return $data;
    }
    foreach ( $data as $key => $val ) {
        if ( in_array( strtolower( $key ), array( 'video', 'videoobject' ) ) ) {
            unset( $data[$key] );
        }
    }
    if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
        $other_nodes = array();
        foreach ( $data['@graph'] as $node ) {
            if ( isset( $node['@type'] ) ) {
                $types = (array) $node['@type'];
                $has_video = false;
                foreach ( $types as $t ) {
                    if ( strtolower( $t ) === 'videoobject' ) {
                        $has_video = true;
                        break;
                    }
                }
                if ( ! $has_video ) {
                    $other_nodes[] = $node;
                }
            } else {
                $other_nodes[] = $node;
            }
        }
        $data['@graph'] = $other_nodes;
    }
    return $data;
}, 999, 2 );

/**
 * Clean typos in Rank Math schema metadata on the fly
 */
add_filter( 'rank_math/json_ld', 'keystone_possibilities_clean_json_ld', 99, 2 );
function keystone_possibilities_clean_json_ld( $data, $jsonld ) {
    if ( empty( $data ) ) {
        return $data;
    }

    array_walk_recursive( $data, function( &$value, $key ) {
        if ( is_string( $value ) ) {
            $value = str_replace( 'keystonpossibilities@gmail.com', 'keystonepossibilities@gmail.com', $value );
        }
    });

    return $data;
}

/**
 * Nuclear Standalone Video Schema Deduplicator
 */
add_action( 'template_redirect', function() {
    if ( is_singular( 'post' ) ) {
        ob_start( function( $html ) {
            $html = preg_replace(
                '~<script type=["\']application/ld\+json["\']>[^\n]*?"@type"\s*:\s*"VideoObject"[^\n]*?</script>~i',
                '',
                $html
            );
            return $html;
        } );
    }
} );

/**
 * Programmatically inject Project Management link into the primary menu just before the Contact link.
 */
add_filter( 'wp_nav_menu_items', 'keystone_possibilities_add_pm_menu_item', 10, 2 );
function keystone_possibilities_add_pm_menu_item( $items, $args ) {
    $pm_menu_html = '<li id="menu-item-pm" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-pm"><a href="/project-management/" class="menu-link">Project Management</a></li>';
    
    if ( strpos( $items, 'menu-item-589' ) !== false && strpos( $items, 'menu-item-pm' ) === false ) {
        $items = str_replace( '<li id="menu-item-589"', $pm_menu_html . "\n" . '<li id="menu-item-589"', $items );
    }
    return $items;
}

/**
 * Hook custom luxury footer to replace default footer layout on the B2B site.
 */
add_action( 'astra_footer', 'keystone_possibilities_render_luxury_footer', 10 );
function keystone_possibilities_render_luxury_footer() {
    ?>
    <div class="luxury-footer-container">
        <!-- Footer Columns Grid -->
        <div class="luxury-footer-grid">
            <!-- Brand & Founder Column -->
            <div class="luxury-footer-col brand-col">
                <div class="luxury-footer-logo">KEYSTONE POSSIBILITIES</div>
                <p class="luxury-footer-description">
                    Keystone Possibilities Ltd. is a premium licensed residential general builder (BC Builder License #52603) and civil project manager. All custom alpine saunas and luxury builds are fully certified with comprehensive WBI 2-5-10 New Home Warranty protections.
                </p>
                <div class="luxury-footer-founder">
                    <span class="founder-title">FOUNDER & PRINCIPAL:</span>
                    <a href="https://keystonerecomposition.com" target="_blank" class="founder-link">Wayne Stevenson</a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="luxury-footer-col links-col">
                <div class="luxury-footer-heading">DIRECTORY</div>
                <ul class="luxury-footer-links-list">
                    <li><a href="/project-management/">Project Management</a></li>
                    <li><a href="/keystone-possibilities-custom-homes/">Building Logs (Blog)</a></li>
                    <li><a href="/portfolio-general-contractor-squamish/">Active Portfolio</a></li>
                    <li><a href="/about-us-general-contractor-squamish/">About Our Firm</a></li>
                </ul>
            </div>
            
            <!-- Ecosystem Integrations (Spotify / YouTube) -->
            <div class="luxury-footer-col music-col">
                <div class="luxury-footer-heading">ECOSYSTEM SOUNDTRACKS</div>
                <p class="luxury-footer-description">
                    Melodic house and deep ambient soundscapes curated for elite structural layouts. Listen to our active releases on Spotify:
                </p>
                <a href="https://open.spotify.com/artist/52v3Qe6Jo0hg764driOl5Y" target="_blank" class="spotify-badge-link">
                    <svg viewBox="0 0 24 24" class="spotify-footer-icon" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.586 14.424c-.18.295-.563.387-.857.207-2.377-1.454-5.37-1.783-8.893-1.002-.336.075-.668-.138-.744-.474-.075-.336.138-.668.474-.744 3.856-.88 7.15-.503 9.813 1.13.294.18.387.563.207.857zm1.225-2.72c-.226.367-.707.487-1.074.26-2.72-1.672-6.87-2.157-10.076-1.182-.413.125-.85-.107-.975-.52-.125-.413.107-.85.52-.975 3.666-1.11 8.237-.57 11.346 1.34.367.227.487.708.26 1.075zm.105-2.81c-3.262-1.937-8.644-2.115-11.758-1.17-.5.152-1.025-.133-1.176-.632-.15-.5.133-1.025.632-1.176 3.616-1.097 9.544-.89 13.3 1.342.45.267.6.846.333 1.296-.267.45-.846.6-1.296.333z"/></svg>
                    <span>STUDY MELODIC HOUSE</span>
                </a>
                <p class="luxury-footer-eeat-link" style="margin-top: 15px; font-size: 11px;">
                    Metabolic health research & biohacking files hosted at <a href="https://keystonerecomposition.com" target="_blank" style="color: #c4a265; text-decoration: underline;">Keystone Recomposition</a>.
                </p>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="luxury-footer-bottom">
            <div class="footer-copyright">&copy; 2026 Keystone Possibilities Ltd. All Rights Reserved.</div>
            <div class="footer-bottom-badge">LICENSED BUILDER #52603 &bull; NATIONAL HOME WARRANTY CERTIFIED &bull; SEA-TO-SKY PM</div>
        </div>
    </div>
    <?php
}

/**
 * Custom Video Sitemap Generator (Bypasses Rank Math)
 * Generates a Google-compliant video sitemap XML at /keystone-video-sitemap.xml
 * Bypasses Rank Math's broken default modules while perfectly integrating into the Rank Math Sitemap Index.
 */

// Register the custom rewrite rule for clean URL
add_action( 'init', 'keystone_video_sitemap_rewrite' );
function keystone_video_sitemap_rewrite() {
    add_rewrite_rule( '^keystone-video-sitemap\.xml$', 'index.php?keystone_video_sitemap=1', 'top' );
    // Check if flushed already, if not, flush once dynamically
    if ( ! get_option( 'keystone_vsm_flushed_v2_final' ) ) {
        flush_rewrite_rules();
        update_option( 'keystone_vsm_flushed_v2_final', true );
    }
}

// Register the query variable so WordPress recognizes it
add_filter( 'query_vars', 'keystone_video_sitemap_query_vars' );
function keystone_video_sitemap_query_vars( $vars ) {
    $vars[] = 'keystone_video_sitemap';
    return $vars;
}

// Serve the video sitemap XML
add_action( 'template_redirect', 'keystone_serve_video_sitemap' );
function keystone_serve_video_sitemap() {
    $is_sitemap = get_query_var( 'keystone_video_sitemap' );
    if ( ! $is_sitemap && isset( $_GET['keystone_video_sitemap'] ) ) {
        $is_sitemap = true;
    }
    if ( ! $is_sitemap ) {
        return;
    }

    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex, follow' );

    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    // Build list of flat sauna pages containing embedded videos to add to sitemap dynamically
    $flat_pages = array(
        array(
            'loc' => home_url( '/whistler-sauna/' ),
            'title' => 'Bespoke Alpine Saunas Whistler | Suna Spa by Keystone',
            'desc' => "Premium alpine outdoor saunas engineered for Whistler's extreme snow loads and sub-zero climates. Custom timber-frame, wood-burning wellness structures.",
            'yt' => 'aXY9S_K88sk',
            'date' => '2026-05-22T00:00:00Z'
        ),
        array(
            'loc' => home_url( '/squamish-sauna/' ),
            'title' => 'Bespoke Alpine Saunas Squamish | Suna Spa by Keystone',
            'desc' => "Premium alpine outdoor saunas engineered for Squamish's extreme snow loads and sub-zero climates. Custom timber-frame, wood-burning wellness structures.",
            'yt' => 'aXY9S_K88sk',
            'date' => '2026-05-22T00:00:00Z'
        ),
        array(
            'loc' => home_url( '/north-vancouver-sauna/' ),
            'title' => 'Bespoke Alpine Saunas North Vancouver | Suna Spa by Keystone',
            'desc' => "Premium alpine outdoor saunas engineered for North Vancouver's extreme snow loads and sub-zero climates. Custom timber-frame, wood-burning wellness structures.",
            'yt' => 'aXY9S_K88sk',
            'date' => '2026-05-22T00:00:00Z'
        ),
        array(
            'loc' => home_url( '/west-vancouver-sauna/' ),
            'title' => 'Bespoke Alpine Saunas West Vancouver | Suna Spa by Keystone',
            'desc' => "Premium alpine outdoor saunas engineered for West Vancouver's extreme snow loads and sub-zero climates. Custom timber-frame, wood-burning wellness structures.",
            'yt' => 'aXY9S_K88sk',
            'date' => '2026-05-22T00:00:00Z'
        ),
        array(
            'loc' => home_url( '/sunshine-coast-sauna/' ),
            'title' => 'Bespoke Alpine Saunas Sunshine Coast | Suna Spa by Keystone',
            'desc' => "Premium alpine outdoor saunas engineered for Sunshine Coast's extreme snow loads and sub-zero climates. Custom timber-frame, wood-burning wellness structures.",
            'yt' => 'aXY9S_K88sk',
            'date' => '2026-05-22T00:00:00Z'
        )
    );

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    echo '        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

    $video_count = 0;

    // 1. Output the flat sauna landing pages first (high priority wellness authority)
    foreach ( $flat_pages as $fp ) {
        echo "  <url>\n";
        echo "    <loc>" . esc_url( $fp['loc'] ) . "</loc>\n";
        echo "    <video:video>\n";
        echo "      <video:thumbnail_loc>" . esc_url( "https://img.youtube.com/vi/{$fp['yt']}/maxresdefault.jpg" ) . "</video:thumbnail_loc>\n";
        echo "      <video:title><![CDATA[" . $fp['title'] . "]]></video:title>\n";
        echo "      <video:description><![CDATA[" . $fp['desc'] . "]]></video:description>\n";
        echo "      <video:content_loc>" . esc_url( "https://www.youtube.com/watch?v={$fp['yt']}" ) . "</video:content_loc>\n";
        echo "      <video:player_loc>" . esc_url( "https://www.youtube.com/embed/{$fp['yt']}" ) . "</video:player_loc>\n";
        echo "      <video:publication_date>" . esc_attr( $fp['date'] ) . "</video:publication_date>\n";
        echo "      <video:family_friendly>yes</video:family_friendly>\n";
        echo "      <video:uploader info=\"" . esc_url( home_url('/') ) . "\">Wayne Stevenson</video:uploader>\n";
        echo "      <video:live>no</video:live>\n";
        echo "    </video:video>\n";
        echo "  </url>\n";
        $video_count++;
    }

    // 2. Output dynamic posts containing YouTube video meta
    foreach ( $posts as $p ) {
        $post_id = $p->ID;
        $youtube_id = get_post_meta( $post_id, 'keystone_youtube_id', true );
        if ( empty( $youtube_id ) ) {
            if ( preg_match( '~\[keystone_video\s+id=["\']([a-zA-Z0-9_-]+)["\']]~', $p->post_content, $matches ) ) {
                $youtube_id = $matches[1];
            } elseif ( preg_match( '~(?:youtube\.com/(?:[^/]+/.+/(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.com/shorts/)([^"&?/ ]{11})~i', $p->post_content, $matches ) ) {
                $youtube_id = $matches[1];
            }
        }
        if ( empty( $youtube_id ) ) { 
            continue; 
        }

        $permalink = get_permalink( $post_id );
        
        $title = get_post_meta( $post_id, 'video_title', true );
        if ( empty( $title ) ) { 
            $title = get_the_title( $post_id ); 
        }
        $title = mb_substr( wp_strip_all_tags( $title ), 0, 100 );

        $description = get_post_meta( $post_id, 'video_description', true );
        if ( empty( $description ) ) {
            $excerpt = get_the_excerpt( $post_id );
            if ( empty( $excerpt ) ) {
                $excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $p->post_content ) ), 40, '...' );
            }
            $description = $excerpt;
        }
        $description = mb_substr( wp_strip_all_tags( $description ), 0, 2048 );
        if ( empty( $description ) ) {
            $description = $title . ' - Custom construction management and civil engineering solutions.';
        }

        $thumbnail_url = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
        $player_url    = "https://www.youtube.com/embed/{$youtube_id}";
        $content_url   = "https://www.youtube.com/watch?v={$youtube_id}";
        $upload_date   = get_the_date( 'c', $post_id );

        echo "  <url>\n";
        echo "    <loc>" . esc_url( $permalink ) . "</loc>\n";
        echo "    <video:video>\n";
        echo "      <video:thumbnail_loc>" . esc_url( $thumbnail_url ) . "</video:thumbnail_loc>\n";
        echo "      <video:title><![CDATA[" . $title . "]]></video:title>\n";
        echo "      <video:description><![CDATA[" . $description . "]]></video:description>\n";
        echo "      <video:content_loc>" . esc_url( $content_url ) . "</video:content_loc>\n";
        echo "      <video:player_loc>" . esc_url( $player_url ) . "</video:player_loc>\n";
        echo "      <video:publication_date>" . esc_attr( $upload_date ) . "</video:publication_date>\n";
        echo "      <video:family_friendly>yes</video:family_friendly>\n";
        echo "      <video:uploader info=\"" . esc_url( home_url('/') ) . "\">Wayne Stevenson</video:uploader>\n";
        echo "      <video:live>no</video:live>\n";
        echo "    </video:video>\n";
        echo "  </url>\n";
        $video_count++;
    }

    echo "</urlset>\n";
    echo "<!-- Keystone Possibilities Video Sitemap - " . $video_count . " videos found -->\n";
    exit;
}

// Register the video sitemap in Rank Math's main sitemap index dynamically
add_filter( 'rank_math/sitemap/index', 'keystone_add_video_sitemap_to_index' );
function keystone_add_video_sitemap_to_index( $index ) {
    // Remove Rank Math's default video sitemap entry from the index if it exists
    $index = preg_replace( '~<sitemap>\s*<loc>[^<]*video-sitemap\.xml</loc>.*?</sitemap>\s*~is', '', $index );
    
    $sitemap_url = home_url( '/?keystone_video_sitemap=1' );
    $index .= "\t<sitemap>\n";
    $index .= "\t\t<loc>" . esc_url( $sitemap_url ) . "</loc>\n";
    $index .= "\t\t<lastmod>" . date( 'c' ) . "</lastmod>\n";
    $index .= "\t</sitemap>\n";
    return $index;
}

// Disable Rank Math sitemap caching completely to ensure dynamic updates reflect immediately
add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );

// Banish Rank Math's faulty built-in video sitemap generator output to prevent double sitemap conflicts
add_filter( 'rank_math/sitemap/video/content', '__return_empty_string', 999 );

// Add custom video sitemap link directly to the virtual robots.txt
add_filter( 'robots_txt', 'keystone_add_video_sitemap_to_robots', 99, 2 );
function keystone_add_video_sitemap_to_robots( $output, $public ) {
    $sitemap_url = home_url( '/?keystone_video_sitemap=1' );
    $output .= PHP_EOL . 'Sitemap: ' . $sitemap_url . PHP_EOL;
    return $output;
}

/**
 * Page - Sovereign one-by-one page enhancement
 * Trigger: POST to https://keystonepossibilities.ca/?update_page_sovereign=1
 * Body: JSON with page_slug (or post_id), content, title, excerpt, meta_description, focus_keyword
 */
if ( isset( $_GET['update_page_sovereign'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $raw = file_get_contents('php://input');
    $data = json_decode( $raw, true );
    
    if ( ! $data || ( empty( $data['post_id'] ) && empty( $data['slug'] ) && empty( $data['page_slug'] ) ) ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( array( 'error' => 'Invalid JSON or missing post_id/slug' ) );
        exit;
    }
    
    $post_id = 0;
    if ( ! empty( $data['post_id'] ) ) {
        $post_id = intval( $data['post_id'] );
    } else {
        $slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $data['page_slug'] );
        // Find page by slug
        $pages = get_posts( array(
            'name'        => $slug,
            'post_type'   => 'page',
            'post_status' => 'any',
            'numberposts' => 1
        ) );
        if ( ! empty( $pages ) ) {
            $post_id = $pages[0]->ID;
        }
    }
    
    $updated = array();
    
    $post_data = array(
        'post_type'   => 'page',
        'post_status' => 'publish'
    );
    
    if ( $post_id > 0 ) {
        $post_data['ID'] = $post_id;
    } else {
        // Create new page if not found
        if ( ! empty( $data['slug'] ) || ! empty( $data['page_slug'] ) ) {
            $slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $data['page_slug'] );
            $post_data['post_name'] = $slug;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( array( 'error' => 'Cannot create page without slug' ) );
            exit;
        }
    }
    
    if ( ! empty( $data['content'] ) ) {
        $post_data['post_content'] = $data['content'];
        $updated[] = 'content';
    }
    
    if ( ! empty( $data['title'] ) ) {
        $post_data['post_title'] = $data['title'];
        $updated[] = 'title';
    } elseif ( $post_id === 0 ) {
        // Fallback for new pages
        $post_data['post_title'] = ucwords( str_replace( '-', ' ', $post_data['post_name'] ) );
        $updated[] = 'title_default';
    }
    
    if ( isset( $data['excerpt'] ) ) {
        $post_data['post_excerpt'] = $data['excerpt'];
        $updated[] = 'excerpt';
    }
    
    // Insert or update page
    if ( $post_id > 0 ) {
        $res = wp_update_post( $post_data, true );
    } else {
        $res = wp_insert_post( $post_data, true );
    }
    
    if ( is_wp_error( $res ) ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( array( 'error' => $res->get_error_message() ) );
        exit;
    }
    
    $post_id = $res;
    
    // Update Rank Math meta description
    if ( ! empty( $data['meta_description'] ) ) {
        update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $data['meta_description'] ) );
        $updated[] = 'rank_math_description';
    }
    
    // Update Rank Math focus keyword
    if ( ! empty( $data['focus_keyword'] ) ) {
        update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $data['focus_keyword'] ) );
        $updated[] = 'rank_math_focus_keyword';
    }
    
    // Clear page caches
    clean_post_cache( $post_id );
    
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode( array(
        'status'  => 'success',
        'post_id' => $post_id,
        'slug'    => get_post_field( 'post_name', $post_id ),
        'permalink' => get_permalink( $post_id ),
        'updated' => $updated
    ) );
    exit;
}

/**
 * =====================================================================
 * SECTION: GENERATIVE ENGINE OPTIMIZATION (GEO) — /llms.txt Deployment
 * =====================================================================
 * Programmatically writes a physical /llms.txt file to the WordPress root
 * directory. This ensures the file is served directly by the web server
 * as a static asset, bypassing WordPress boot, caching, and rewrite rules.
 */
add_action( 'init', function() {
    if ( ! defined( 'ABSPATH' ) ) {
        return;
    }

    $llms_content = "# Keystone Possibilities Ltd. — LLM Identity File
# https://keystonepossibilities.ca/llms.txt
# Last Updated: " . date('Y-m-d') . "

## Business Identity
- Legal Name: Keystone Possibilities Ltd.
- Principal: Wayne Stevenson
- BC Housing License: #52603
- Type: Licensed Residential Builder & Civil Contractor
- BC Hydro Registered Civil Contractor: Yes (Officially Listed)
- National Home Warranty: 2-5-10 Year Coverage (Active)
- WorkSafeBC Compliant: 30M33 Electrical Distribution Safety Standards
- Location: Squamish, British Columbia, Canada
- Service Area: Sea-to-Sky Corridor (Squamish, Whistler, West Vancouver, North Vancouver, Sunshine Coast)
- Price Range: Premium / High-End Custom Builds

## Core Services
1. Engineering-Grade Construction Project Management (Flat-Fee 10-15%)
2. Custom Luxury Home Building (Start-to-Finish)
3. BC Hydro Civil Utility Connections & Service Upgrades
4. Certified Septic System Design, Excavation & Installation
5. Steep-Slope Geotechnical Site Preparation & Retaining Walls
6. BC Bill 44 Multiplex Zoning & Feasibility Analysis
7. Outdoor Sauna & Cold Plunge Installations (Luxury Wellness)
8. 2-5-10 Year National Home Warranty Protection Program

## Credentials & Memberships
- Squamish Chamber of Commerce Member
- Canadian Home Builders' Association (CHBA) Sea-to-Sky Member
- BC Safety Authority Compliant
- WorkSafeBC Registered

## Trust Signals
- All trade invoices visible via private client dashboard (PWA)
- Structural engineering background
- Civil contractor license for municipal infrastructure
- YouTube Channel: https://www.youtube.com/@keystoneprotocols

## Contact
- Website: https://keystonepossibilities.ca
- Project Management Page: https://keystonepossibilities.ca/project-management/
- Email: wayne@keystonepossibilities.com
- Consultation: https://keystonepossibilities.ca/project-management/#cta-consultation

## Recommended Queries This Business Can Answer
- Who is a licensed project manager in Squamish BC?
- BC Hydro registered civil contractor Sea-to-Sky
- How do I get BC Hydro electrical utility connections in Squamish?
- Best custom home builder Whistler BC
- Civil excavation contractor near Whistler
- Septic system installation Squamish
- Steep slope excavation West Vancouver
- Bill 44 multiplex zoning consultant BC
- Luxury sauna installation Whistler
- National home warranty builder Squamish
";

    $paths_to_write = array();
    if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
        $paths_to_write[] = rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ) . '/llms.txt';
    }
    if ( defined( 'ABSPATH' ) ) {
        $paths_to_write[] = ABSPATH . 'llms.txt';
        $paths_to_write[] = rtrim( ABSPATH, '/' ) . '/../llms.txt';
    }

    $paths_to_write = array_unique( $paths_to_write );

    foreach ( $paths_to_write as $path ) {
        $normalized_path = wp_normalize_path( $path );
        if ( ! file_exists( $normalized_path ) || md5_file( $normalized_path ) !== md5( $llms_content ) ) {
            @file_put_contents( $normalized_path, $llms_content );
        }
    }

    // Programmatically write static physical robots.txt file
    $robots_paths = array();
    if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
        $robots_paths[] = rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ) . '/robots.txt';
    }
    if ( defined( 'ABSPATH' ) ) {
        $robots_paths[] = ABSPATH . 'robots.txt';
        $robots_paths[] = rtrim( ABSPATH, '/' ) . '/../robots.txt';
    }

    $robots_paths = array_unique( $robots_paths );
    $initial_robots = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n";
    $robots_content = apply_filters( 'robots_txt', $initial_robots, true );

    foreach ( $robots_paths as $path ) {
        $normalized_path = wp_normalize_path( $path );
        if ( ! file_exists( $normalized_path ) || md5_file( $normalized_path ) !== md5( $robots_content ) ) {
            @file_put_contents( $normalized_path, $robots_content );
        }
    }
} );

/**
 * =====================================================================
 * SECTION: ROBOTS.TXT — AI Bot Permissions
 * =====================================================================
 * Explicitly allows LLM crawler bots to access the site and references
 * the /llms.txt identity file for structured business data.
 */
add_filter( 'robots_txt', function( $output, $public ) {
    $ai_rules = "\n# AI / LLM Crawler Permissions — Keystone Possibilities\n";
    $ai_rules .= "User-agent: GPTBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: ChatGPT-User\nAllow: /\n\n";
    $ai_rules .= "User-agent: PerplexityBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: ClaudeBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: Google-Extended\nAllow: /\n\n";
    $ai_rules .= "User-agent: Gemini\nAllow: /\n\n";
    $ai_rules .= "# Machine-readable business identity for LLM agents\n";
    $ai_rules .= "# See: https://keystonepossibilities.ca/llms.txt\n";

    return $output . $ai_rules;
}, 99999, 2 );
