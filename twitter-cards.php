<?php
/**
 * Plugin Name: Twitter Cards
 * Plugin URI: https://github.com/niallkennedy/twitter-cards
 * Description: Add Twitter Cards markup to individual posts.
 * Author: Niall Kennedy
 * Author URI: http://www.niallkennedy.com/
 * Version: 1.0.4
 */

if ( ! class_exists( 'Twitter_Cards' ) ):
/**
 * Add Twitter Card markup to document <head>
 *
 * @since 1.0
 * @version 1.0.4
 */
class Twitter_Cards {
	/**
	 * Attach Twitter cards markup to wp_head if single post view
	 *
	 * @since 1.0
	 */
	public static function init() {
		if ( is_single() )
			add_action( 'wp_head', 'Twitter_Cards::markup' );
	}

	/**
	 * Build a Twitter Card object. Possibly output markup
	 *
	 * @since 1.0
	 */
	public static function markup() {
		global $post;
		$card_type = apply_filters('twitter_cards_cardtype', 'summary', $post);

		if ( ! class_exists( 'Twitter_Card_WP' ) )
			require_once( dirname(__FILE__) . '/class-twitter-card-wp.php' );
		
		$card = apply_filters('twitter_cards_carddata', new Twitter_Card_WP($card_type), $card_type, $post);

		if ( apply_filters( 'twitter_cards_htmlxml', 'html' ) === 'xml' )
			echo $card->asXML();
		else
			echo $card->asHTML();
	}
	
	public static function setup_card_data($card, $card_type, $post) {
		global $post;
		
		if ( ! ( isset( $post ) && isset( $post->ID ) ) )
			return $type;
		
		setup_postdata($post);
		
		$post_type = get_post_type($post);
		
		$card->setURL( get_permalink( $post_id ) );
		if ( post_type_supports( $post_type, 'title' ) ) {
			$card->setTitle( get_the_title() );
		}
		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			// one line, no HTML
			$description = self::make_description( $post );
			if ( $description )
				$card->setDescription( $description );
			unset( $description );
		}
		
		$post_thumbnail_url = false;
		if ( post_type_supports( $post_type, 'thumbnail' ) && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() )
			list( $post_thumbnail_url, $post_thumbnail_width, $post_thumbnail_height ) = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );

		if ( $post_thumbnail_url )
			$card->setImage( $post_thumbnail_url, $post_thumbnail_width, $post_thumbnail_height );
		
		return $card;
	}
	
	public static function card_type($type, $post) {
		global $post;
		
		if ( ! ( isset( $post ) && isset( $post->ID ) ) )
			return $type;
		
		setup_postdata($post);

		$post_id = absint( $post->ID );
		if ( ! $post_id )
			return $type;

		$post_type = get_post_type( $post );
		if ( ! $post_type )
			return $type;
			
		$post_format = get_post_format( $post_id );

		// does current post type and the current theme support post thumbnails?
		$post_thumbnail_url = false;
		if ( post_type_supports( $post_type, 'thumbnail' ) && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() )
			list( $post_thumbnail_url, $post_thumbnail_width, $post_thumbnail_height ) = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );

		// treat as photo card if post type image
		// why not also check for post format video? No real easy way in WordPress Core to ask for an iframe version of the video embedded in your post, meaning you'll have to tap into a filter and may as well set the card value alongside required attributes such as player
		if ( $post_format === 'image' && $post_thumbnail_url && $post_thumbnail_width >= 280 && $post_thumbnail_height >= 150 )
			$type = 'photo';
		
		return $type;
	}

	/**
	 * Create a description from post excerpt or content. Prep for Twitter display.
	 * Twitter will truncate the description at 200 characters. We will not enforce this character count to allow for a maximum character change or other consuming agents.
	 *
	 * @since 1.0
	 * @param stdClass $post WordPress post object
	 * @return string description string
	 */
	public static function make_description( $post ) {
		if ( ! ( isset( $post ) && isset( $post->post_excerpt ) && isset( $post->post_content ) ) )
			return '';

		// did the publisher specify a custom excerpt? use it
		if ( ! empty( $post->post_excerpt ) )
			$description = apply_filters( 'get_the_excerpt', $post->post_excerpt );
		else
			$description = $post->post_content;

		$description = trim( $description );
		if ( ! $description )
			return '';

		// basic filters from wp_trim_excerpt() that should apply to both excerpt and content
		// note: the_content filter is so polluted with extra third-party stuff we purposely avoid to better represent page content
		$description = strip_shortcodes( $description ); // remove any shortcodes that may exist, such as an image macro
		$description = str_replace( ']]>', ']]&gt;', $description );
		$description = trim( $description );

		if ( ! $description )
			return '';

		// use the built-in WordPress function for localized support of words vs. characters
		// Twitter asks for 200 characters. look for 300 to provide a buffer, allow for change, and support possible uses by other consuming agents
		// assume ~4 characters per word, 75 words max if 300 characters
		$description = wp_trim_words( $description, 75, '' );

		if ( $description )
			return $description;

		return '';
	}
}
add_action( 'wp', 'Twitter_Cards::init' );
add_filter('twitter_cards_cardtype', 'Twitter_Cards::card_type', 10, 2);
add_filter('twitter_cards_carddata', 'Twitter_Cards::setup_card_data', 10, 3);

endif;
?>
