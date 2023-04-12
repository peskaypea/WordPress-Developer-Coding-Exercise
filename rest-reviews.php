<?php
/*
Plugin Name: Rest Reviews
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
}