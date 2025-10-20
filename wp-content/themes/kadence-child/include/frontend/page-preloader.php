<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page Preloader Handler
 * Manages animated SVG preloader for page loads only (not AJAX calls)
 */
class Rizton_Page_Preloader {

    public function __construct() {
        // Only show preloader on initial page loads, not AJAX requests
        if ( ! wp_doing_ajax() && ! is_admin() ) {
            add_action( 'wp_head', array( $this, 'add_preloader_styles' ), 1 );
            add_action( 'wp_footer', array( $this, 'add_preloader_script' ), 99 );
            add_action( 'wp_body_open', array( $this, 'add_preloader_html' ) );
        }
    }

    /**
     * Add preloader CSS styles to head
     */
    public function add_preloader_styles() {
        ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
<style>
/* Preloader Styles */
#rizton-preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #0a1931;
    z-index: 99999;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: opacity 0.75s ease-in-out, visibility 0.75s ease-in-out;
    opacity: 1;
    visibility: visible;
}

#rizton-preloader.loaded {
    opacity: 0;
    visibility: hidden;
}

.rizton-logo-container {
    position: relative;
    width: 100px;
    height: 110px;
}

.rizton-logo-container svg {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
}

.rizton-logo-draw-path {
    fill: none;
    fill-opacity: 0;
    stroke: #3b82f6;
    stroke-width: 1;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transform-origin: center;
    animation:
        rizton-draw 2s ease-in-out forwards,
        rizton-fillAndScale 0.5s ease-in-out 2s forwards;
}

@keyframes rizton-draw {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes rizton-fillAndScale {
    to {
        fill-opacity: 1;
        fill: #1E57DF;
        transform: scale(1.05);
    }
}

/* Hide page content initially */
body.rizton-loading {
    overflow: hidden;
}

body.rizton-loading .site-main,
body.rizton-loading .site-content,
body.rizton-loading #main,
body.rizton-loading #content,
body.rizton-loading .elementor-section {
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.5s ease-in-out, visibility 0.5s ease-in-out;
}

body.rizton-loaded .site-main,
body.rizton-loaded .site-content,
body.rizton-loaded #main,
body.rizton-loaded #content,
body.rizton-loaded .elementor-section {
    opacity: 1;
    visibility: visible;
}

/* Ensure AJAX calls don't trigger preloader */
body.rizton-ajax-loading #rizton-preloader {
    display: none !important;
}
</style>
<?php
    }

    /**
     * Add preloader HTML to body
     */
    public function add_preloader_html() {
        ?>
<div id="rizton-preloader">
    <div class="rizton-logo-container">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="44" viewBox="0 0 40 44" fill="none"
            overflow="visible">
            <path class="rizton-logo-draw-path"
                d="M39.2665 13.0801V43.269H20.6012L13.1324 34.9501C11.698 33.3543 11.0745 31.1351 11.6544 29.0684C11.7721 28.6542 11.9291 28.2487 12.134 27.8607C13.2458 25.7373 15.7659 23.6183 21.386 23.6183L23.4221 36.1753C27.368 34.017 30.8735 31.3574 31.0043 25.8507C30.9781 20.9369 29.391 18.6392 26.4349 16.7644C24.438 15.4999 22.0836 14.9026 19.7117 14.9026H8.94245V43.269H0V13.0801L19.6333 0L39.2665 13.0801Z" />
        </svg>
    </div>
</div>
<script>
// Add loading class to body immediately (only for initial page load)
if (!document.body.classList.contains('rizton-ajax-loading')) {
    document.body.classList.add('rizton-loading');
}
</script>
<?php
    }

    /**
     * Add preloader JavaScript to footer
     */
    public function add_preloader_script() {
        ?>
<script>
jQuery(document).ready(function($) {
    // Only run preloader on initial page load, not AJAX calls
    if (document.body.classList.contains('rizton-ajax-loading')) {
        return;
    }

    // Set up SVG path animation
    const logoPath = document.querySelector('.rizton-logo-draw-path');
    if (logoPath) {
        const length = logoPath.getTotalLength();
        logoPath.style.strokeDasharray = length;
        logoPath.style.strokeDashoffset = length;
    }

    // Track loaded assets
    let assetsLoaded = {
        dom: false,
        images: false,
        scripts: false
    };

    // Check if all assets are loaded
    function checkAllLoaded() {
        if (assetsLoaded.dom && assetsLoaded.images && assetsLoaded.scripts) {
            hidePreloader();
        }
    }

    // Hide preloader function
    function hidePreloader() {
        setTimeout(() => {
            const preloader = document.getElementById('rizton-preloader');
            const body = document.body;

            if (preloader) {
                preloader.classList.add('loaded');
                body.classList.remove('rizton-loading');
                body.classList.add('rizton-loaded');

                // Remove preloader from DOM after transition
                preloader.addEventListener('transitionend', function() {
                    preloader.remove();
                }, {
                    once: true
                });
            }
        }, 3000); // Minimum 3 seconds to see animation
    }

    // DOM is ready
    $(document).ready(function() {
        assetsLoaded.dom = true;
        checkAllLoaded();
    });

    // All resources (images, scripts, etc.) are loaded
    $(window).on('load', function() {
        assetsLoaded.scripts = true;

        // Check for images
        const images = document.querySelectorAll('img');
        let imagesToLoad = images.length;

        if (imagesToLoad === 0) {
            assetsLoaded.images = true;
            checkAllLoaded();
        } else {
            let loadedImages = 0;
            images.forEach(img => {
                if (img.complete) {
                    loadedImages++;
                } else {
                    img.addEventListener('load', function() {
                        loadedImages++;
                        if (loadedImages === imagesToLoad) {
                            assetsLoaded.images = true;
                            checkAllLoaded();
                        }
                    });
                    img.addEventListener('error', function() {
                        loadedImages++;
                        if (loadedImages === imagesToLoad) {
                            assetsLoaded.images = true;
                            checkAllLoaded();
                        }
                    });
                }
            });

            // If all images were already loaded
            if (loadedImages === imagesToLoad) {
                assetsLoaded.images = true;
                checkAllLoaded();
            }
        }
    });

    // Fallback: Force hide preloader after 10 seconds
    setTimeout(() => {
        if (document.getElementById('rizton-preloader')) {
            hidePreloader();
        }
    }, 10000);

    // Prevent preloader on AJAX calls
    $(document).ajaxStart(function() {
        document.body.classList.add('rizton-ajax-loading');
    });

    $(document).ajaxComplete(function() {
        setTimeout(() => {
            document.body.classList.remove('rizton-ajax-loading');
        }, 100);
    });
});
</script>
<?php
    }
}

// Initialize the preloader (only on non-AJAX requests)
new Rizton_Page_Preloader();