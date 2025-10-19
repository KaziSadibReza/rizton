 <?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 ?>
 <div id="property-gallery-container">
     <div id="property-gallery-images" class="gallery-images">
         <?php if (!empty($gallery_images)): ?>
         <?php foreach ($gallery_images as $image_id): ?>
         <?php $image_url = wp_get_attachment_image_url($image_id, 'thumbnail'); ?>
         <?php if ($image_url): ?>
         <div class="gallery-image" data-image-id="<?php echo esc_attr($image_id); ?>">
             <img src="<?php echo esc_url($image_url); ?>" alt="">
             <div class="gallery-image-actions">
                 <span class="remove-gallery-image" data-image-id="<?php echo esc_attr($image_id); ?>">&times;</span>
             </div>
         </div>
         <?php endif; ?>
         <?php endforeach; ?>
         <?php endif; ?>
     </div>
     <div class="gallery-actions">
         <button type="button" id="add-gallery-images" class="button button-secondary">Add Images</button>
         <p class="description">Add multiple images for the property gallery</p>
     </div>
     <input type="hidden" id="property-gallery-ids" name="property_gallery"
         value="<?php echo esc_attr(implode(',', $gallery_images)); ?>">
 </div>