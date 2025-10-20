<!DOCTYPE html>
<html>
<head>
    <title>AJAX Sort Widget Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test-container { max-width: 800px; margin: 0 auto; }
        .property-item { 
            border: 1px solid #ddd; 
            padding: 20px; 
            margin: 10px 0; 
            border-radius: 5px;
        }
        .sort-widget { 
            background: #f5f5f5; 
            padding: 20px; 
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .debug-info {
            background: #ffe6e6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>AJAX Property Sort Widget Test</h1>
        
        <div class="sort-widget">
            <h3>Sort Widget Test:</h3>
            
            <!-- Test the widget shortcode -->
            <?php 
            if (function_exists('do_shortcode')) {
                echo do_shortcode('[property_sort]');
            } else {
                echo '<p>WordPress shortcode function not available. This test needs to be run in a WordPress environment.</p>';
            }
            ?>
        </div>
        
        <div class="debug-info">
            <h4>Debug Information:</h4>
            <p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?></p>
            <p><strong>GET Parameters:</strong> <?php var_dump($_GET); ?></p>
            
            <?php if (function_exists('get_posts')): ?>
                <?php 
                $properties = get_posts(array(
                    'post_type' => 'property',
                    'posts_per_page' => 5,
                    'post_status' => 'publish'
                ));
                ?>
                <p><strong>Total Properties Found:</strong> <?php echo count($properties); ?></p>
                
                <?php if (count($properties) > 0): ?>
                    <p><strong>Sample Property Data:</strong></p>
                    <ul>
                    <?php foreach ($properties as $prop): ?>
                        <li>
                            <?php echo $prop->post_title; ?> 
                            (ID: <?php echo $prop->ID; ?>)
                            
                            <?php 
                            $property_type = get_field('property_type', $prop->ID);
                            $land_size = get_field('land_size_sq_feet', $prop->ID);
                            $city = get_field('what_is_the_street_address_city', $prop->ID);
                            ?>
                            
                            - Type: <?php echo $property_type ?: 'N/A'; ?>
                            - Size: <?php echo $land_size ?: 'N/A'; ?>
                            - City: <?php echo $city ?: 'N/A'; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: red;"><strong>No properties found!</strong> Make sure you have some property posts published.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>WordPress functions not available.</p>
            <?php endif; ?>
        </div>
        
        <div id="properties-container" class="elementor-loop-container">
            <p>Property results will appear here when you select a sort option...</p>
        </div>
        
        <script>
            // Add some debug logging for JavaScript
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(function($) {
                    console.log('jQuery loaded, AJAX sort widget should be functional');
                    
                    // Monitor for AJAX responses
                    $(document).on('ajaxPropertySortComplete', function(event, data, sortType) {
                        console.log('Sort completed:', sortType, data);
                    });
                });
            } else {
                console.log('jQuery not loaded!');
            }
        </script>
    </div>
</body>
</html>