/**
 * Keystone Protocols - High-Performance Quiet Luxury Media Facade Engine
 * Completely bypasses heavy third-party initial script payloads to secure 100 Mobile PageSpeed rankings.
 * Utilizes native browser APIs, CSS hardware acceleration transitions, and dynamic DOM cleanup.
 *
 * @package Astra Child for Keystone
 * @since 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {
    const initVideoFacades = () => {
        // Query all premium video placeholders on the page
        const facades = document.querySelectorAll('.luxury-video-facade');
        
        facades.forEach(facade => {
            // Register isolated play click trigger
            facade.addEventListener('click', function handlePlayClick(event) {
                event.preventDefault();
                
                const videoId = this.getAttribute('data-video-id');
                const videoType = (this.getAttribute('data-video-type') || 'youtube').toLowerCase();
                
                if (!videoId) return;
                
                let targetSrc = '';
                
                // Configure parameters to guarantee immediate autoplay within standard browser policy controls
                if (videoType === 'youtube') {
                    // YouTube optimized no-cookie domain with strict relational constraints
                    targetSrc = `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&mute=1&rel=0&start=0&enablejsapi=1`;
                } else if (videoType === 'spotify') {
                    // Spotify video podcast embed format with deep-link time mapping
                    targetSrc = `https://open.spotify.com/embed/episode/${videoId}?utm_source=generator&t=0`;
                }
                
                if (targetSrc) {
                    const iframe = document.createElement('iframe');
                    iframe.setAttribute('src', targetSrc);
                    iframe.setAttribute('frameborder', '0');
                    iframe.setAttribute('allowfullscreen', 'true');
                    iframe.setAttribute('title', 'Keystone Video Player');
                    
                    // Hardware accelerated browser capability authorizations
                    iframe.setAttribute('allow', 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture');
                    
                    // Remove current click handler to prevent double execution triggers
                    this.removeEventListener('click', handlePlayClick);
                    
                    // Fade background elements out gently to prevent visual transition stutter
                    const background = this.querySelector('.facade-background');
                    const button = this.querySelector('.play-button');
                    const overlay = this.querySelector('.facade-overlay');
                    
                    if (background) background.style.opacity = '0';
                    if (button) button.style.opacity = '0';
                    if (overlay) overlay.style.opacity = '0';
                    
                    // Mount dynamic iframe structure
                    this.appendChild(iframe);
                    
                    // Clean up and destroy unneeded DOM nodes after fade animation is finished
                    // Shifting graphics entirely to iframe to avoid heavy multi-layer drawing CPU limits on mobile
                    setTimeout(() => {
                        if (background) background.remove();
                        if (button) button.remove();
                        if (overlay) overlay.remove();
                        console.log('[Keystone Player] Lazy media elements destroyed successfully.');
                    }, 600);
                }
            });
        });
    };
    
    initVideoFacades();
});
