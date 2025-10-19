<?php
/**
 * Template for Property Gallery Slider with SwiperJS
 * Can be used in theme templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the gallery images
$gallery_images = isset($gallery_images) ? $gallery_images : get_property_gallery_images();

if (empty($gallery_images)) {
    echo '<div class="property-gallery-slider no-images"></div>';
    return;
}

// Default settings (can be overridden)
$show_navigation = isset($show_navigation) ? $show_navigation : true;
$show_pagination = isset($show_pagination) ? $show_pagination : true;
$show_thumbnails = isset($show_thumbnails) ? $show_thumbnails : true;
$slider_height = isset($slider_height) ? $slider_height : 400;
$image_size = isset($image_size) ? $image_size : 'large';
$thumbnail_size = isset($thumbnail_size) ? $thumbnail_size : 'medium';
$container_class = isset($container_class) ? $container_class : 'property-gallery-slider';
?>

<div class="<?php echo esc_attr($container_class); ?>">
    <div class="slider-container">

        <?php if ($show_thumbnails && count($gallery_images) > 1): ?>
        <!-- Thumbnail Slider -->
        <div class="thumbnail-slider swiper">
            <div class="swiper-wrapper">
                <?php foreach ($gallery_images as $image_id): ?>
                <?php 
                    $thumb_url = wp_get_attachment_image_url($image_id, $thumbnail_size);
                    $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    ?>

                <?php if ($thumb_url): ?>
                <div class="swiper-slide">
                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($image_alt); ?>">
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Image Slider -->
        <div class="main-slider swiper">
            <div class="swiper-wrapper">
                <?php foreach ($gallery_images as $image_id): ?>
                <?php 
                    $image_url = wp_get_attachment_image_url($image_id, $image_size);
                    $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    $image_title = get_the_title($image_id);
                    ?>

                <?php if ($image_url): ?>
                <div class="swiper-slide">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>"
                        title="<?php echo esc_attr($image_title); ?>"
                        style="height: <?php echo intval($slider_height); ?>px;">
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (($show_navigation || $show_pagination) && count($gallery_images) > 1): ?>
        <!-- Custom Navigation and Pagination -->
        <div class="main-pagation">
            <?php if ($show_navigation): ?>
            <div class="left-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M25.5 15C25.5 15.6213 24.9963 16.125 24.375 16.125L8.41812 16.125L14.6547 22.0641C15.1026 22.4947 15.1166 23.2069 14.6859 23.6548C14.2553 24.1026 13.5431 24.1166 13.0953 23.6859L4.84525 15.8109C4.62466 15.5988 4.5 15.306 4.5 15C4.5 14.694 4.62466 14.4012 4.84525 14.1891L13.0953 6.31406C13.5431 5.88342 14.2553 5.89739 14.6859 6.34525C15.1166 6.79312 15.1026 7.5053 14.6547 7.93594L8.41812 13.875L24.375 13.875C24.9963 13.875 25.5 14.3787 25.5 15Z"
                        fill="white" />
                </svg>
            </div>
            <?php endif; ?>

            <?php if ($show_pagination): ?>
            <div class="dash-pagination">
                <!-- Dashes will be created by JavaScript -->
            </div>
            <?php endif; ?>

            <?php if ($show_navigation): ?>
            <div class="right-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M4.5 15C4.5 14.3787 5.00368 13.875 5.625 13.875L21.5819 13.875L15.3453 7.93593C14.8974 7.50529 14.8834 6.79312 15.3141 6.34525C15.7447 5.89738 16.4569 5.88342 16.9047 6.31406L25.1547 14.1891C25.3753 14.4012 25.5 14.694 25.5 15C25.5 15.306 25.3753 15.5988 25.1547 15.8109L16.9047 23.6859C16.4569 24.1166 15.7447 24.1026 15.3141 23.6547C14.8834 23.2069 14.8974 22.4947 15.3453 22.0641L21.5819 16.125L5.625 16.125C5.00368 16.125 4.5 15.6213 4.5 15Z"
                        fill="white" />
                </svg>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>