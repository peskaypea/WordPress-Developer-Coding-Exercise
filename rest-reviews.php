<?php
/*
Plugin Name: Php Developer
Description: Fetches reviews from an external REST API and displays them in a list.
Version: 1.0.0
Author: Zane 
Author URI: https://github.com/peskaypea
*/

function external_review_fetcher_shortcode(){
   // Check if data is already cached
   $cache_key = 'rest_reviews_data';
   $cache = wp_cache_get( $cache_key );

   if ( false === $cache ) {
       // Data not cached, fetch reviews from external API
       $reviews_url = plugin_dir_url( __FILE__ ) . 'data.json';
       $reviews_response = wp_remote_get( $reviews_url );

       if ( is_wp_error( $reviews_response ) ) {
           return '<p>Error: ' . $reviews_response->get_error_message() . '</p>';
       }

       $reviews = json_decode( wp_remote_retrieve_body( $reviews_response ), true );

       if ( json_last_error() !== JSON_ERROR_NONE ) {
           return '<p>Error: Invalid JSON format returned from API.</p>';
       }

        // Sanitize and validate user input
        foreach ( $reviews['toplists']['575'] as &$review ) {
            $review['logo'] = esc_url_raw( $review['logo'] );
            $review['play_url'] = esc_url_raw( $review['play_url'] );
            $review['brand_id'] = absint( $review['brand_id'] );
            $review['info']['rating'] = floatval( $review['info']['rating'] );
            $review['info']['bonus'] = sanitize_text_field( $review['info']['bonus'] );
            $review['terms_and_conditions'] = wp_kses_post( $review['terms_and_conditions'] );
            foreach ( $review['info']['features'] as &$feature ) {
                $feature = sanitize_text_field( $feature );
            }
        }

       // Cache the data for future use
       wp_cache_set( $cache_key, $reviews, '', 60 * 60 * 6 ); // cache for 6 hours
   } else {
    // Data is cached, use it instead of fetching from API
    $reviews = $cache;
}

// Generate HTML for reviews
if ( ! empty( $reviews ) && isset( $reviews['toplists']['575'] ) ) {
    function generate_star_rating_html($rating) {
        // Generate star rating HTML based on rating value
        $html = '';
        $checked_stars = min(round($rating), 5);
        for ($i = 0; $i < $checked_stars; $i++) {
            $html .= '<span class="fa fa-star checked"></span>';
        }
        for ($i = $checked_stars; $i < 5; $i++) {
            $html .= '<span class="far fa-star unchecked"></span>';
        }
        return $html;
    }

    $html = '<div>';
    $html .= '<div class="igame-container">';
    $html .= '<header class="igame-header">';
    $html .= '<p>Casino</p>';
    $html .= '<p>Bonus</p>';
    $html .= '<p>Features</p>';
    $html .= '<p>Play</p>';
    $html .= '</header>';

    // Loop through reviews and generate HTML for each review
    foreach ( $reviews['toplists']['575'] as $review ) {
        $html .= '<div class="igame-columns">';
        $html .= '<div class="igame-col-1">';
        $html .= '<img class="igame-img" src="' . $review['logo'] . '"/>';
        $html .= '<a class="igame-review" href="' . $review['play_url'] . '/'. $review['brand_id'] . '">'
        .'Review</a>';
        $html .= '</div>';

        $html .= '<div class="igame-col-2">';
        $html .= '<div>';
        $html .= generate_star_rating_html($review['info']['rating']);
        $html .= '</div>';
        $html .= '<p class="igame-col-2-p">' . $review['info']['bonus'] . '</p>';
        $html .= '</div>';

        $html .= '<div class="igame-col-3">';
        $html .= '<ul class="igame-col-3-ul">';
        foreach ( $review['info']['features'] as $feature ) {
            $html .= '<li>' . $feature . '</li>';
        };
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '<div class="igame-col-4">';
        $html .= '<a href="' . $review['play_url'] .'">';
        $html .= '<button class="igame-col-4-button">Play Now</button>';
        $html .= '</a>';
        $html .= '<p class="igame-col-4-p">' . $review['terms_and_conditions'] . '</p>';
        $html .= '</div>';

        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '</div>';
} else {
    $html = '<p>No reviews found.</p>';
}

return $html;
}

function myplugin_enqueue_styles() {
wp_enqueue_style( 'myplugin-style', plugin_dir_url( __FILE__ ) . 'style.css' );
wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css' );
}
add_action( 'wp_enqueue_scripts', 'myplugin_enqueue_styles' );
add_shortcode( 'external_review_fetcher', 'external_review_fetcher_shortcode' );