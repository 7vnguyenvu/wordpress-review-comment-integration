<?php

/**
 * Shortcode class
 * 
 * @package Comment_Rating_Field_Pro
 * @author Tim Carr
 * @version 1.0
 */
class Comment_Rating_Field_Pro_Shortcode
{

	/**
	 * Holds the class object.
	 *
	 * @since 	3.2.6
	 *
	 * @var 	object
	 */
	public static $instance;

	/**
	 * Constructor
	 *
	 * @since 	3.2.0
	 */
	public function __construct()
	{

		// Register shortcodes
		add_shortcode('crfp', array($this, 'shortcode'));
		add_filter('widget_text', array($this, 'shortcode_widget'));
	}

	/**
	 * If a query var to filter comments is set, build a new comments array comprising
	 * of just the comments we want.
	 *
	 * @since 	3.2.0
	 *
	 * @param 	array 	$comments 	Comments
	 * @param 	int 	$post_id 	Post ID
	 * @return 	array 				Filtered Comments
	 */
	public function filter_comments_by_rating($comments, $post_id)
	{

		// Check if our rating query var was set
		if (!isset($_GET['rating'])) {
			return $comments;
		}

		// Build our custom comment arguments
		$comment_args = array(
			'order'   	=> 'ASC',
			'orderby' 	=> 'comment_date_gmt',
			'status'  	=> 'approve',
			'post_id' 	=> $post_id,
			'meta_key'	=> 'crfp-average-rating',
			'meta_value' => (string) sanitize_text_field($_GET['rating']),
		);

		// Run the query and return the comments
		$comments = get_comments($comment_args);

		return $comments;
	}

	/**
	 * Outputs the average rating when the [crfp] shortcode is used
	 *
	 * @since 	3.2.0
	 *
	 * @param 	array 	$atts 	Shortcode Attributes
	 * @return 	string 			HTML
	 */
	public function shortcode($atts)
	{

		global $post;

		// Map attributes to CRFP-valid array
		// This is because the shortcode attribute keys are slightly different from CRFP's array keys
		$instance = array(
			'enabled' 			=> (isset($atts['enabled']) ? $atts['enabled'] : 0),
			'id'				=> (isset($atts['id']) ? $atts['id'] : ''),
			'class'				=> (isset($atts['class']) ? $atts['class'] : ''),
			'position' 			=> '', // N/A
			'style' 			=> (isset($atts['displaystyle']) ? $atts['displaystyle'] : 'grey'),
			'average' 			=> (isset($atts['displayaverage']) ? $atts['displayaverage'] : 0),
			'averageLabel' 		=> (isset($atts['averageratingtext']) ? $atts['averageratingtext'] : ''),
			'totalRatings' 		=> (isset($atts['displaytotalratings']) ? $atts['displaytotalratings'] : 0),
			'totalRatingsBefore' => (isset($atts['totalratingsbefore']) ? $atts['totalratingsbefore'] : 'from'),
			'totalRatingsAfter' => (isset($atts['totalratingsafter']) ? $atts['totalratingsafter'] : 'ratings'),
			'showBreakdown' 	=> (isset($atts['displaybreakdown']) ? $atts['displaybreakdown'] : 0),
			'showRatingNumber'  => (isset($atts['displayratingnumber']) ? $atts['displayratingnumber'] : 0),
			'filterComments' 	=> (isset($atts['filtercomments']) ? $atts['filtercomments'] : 0),
			'linkToComments' 	=> (isset($atts['displaylink']) ? $atts['displaylink'] : 0),
		);

		// Define the Post ID
		$post_id = false;
		if (isset($instance['id']) && !empty($instance['id'])) {
			$post_id = absint($instance['id']);
		} elseif (isset($post)) {
			$post_id = $post->ID;
		}

		// Bail if no Post ID was found
		if (!$post_id) {
			return '';
		}

		// Check if the Post ID has a rating group
		$group = Review_Comment_Integration_Pro_Groups::get_instance()->get_group_by_post_id($post_id);
		if (!$group) {
			return '';
		}

		// Override the group's shortcode settings with the ones specificed
		$group['ratingOutputShortcode'] = $instance;

		// Build rating HTML
		$disable_schema_markup = (bool) Comment_Rating_Field_Pro_Settings::get_instance()->get_setting('schema_disable_shortcode');
		$html = Comment_Rating_Field_Pro_Rating_Output::get_instance()->build_average_rating_html($post_id, $group, 'shortcode', '', $disable_schema_markup);
		$html = apply_filters('crfp_display_post_rating_shortcode', $html, $group, $instance);

		// Return
		return $html;
	}

	/**
	 * Renders all shortcodes in a text widget
	 *
	 * @since 	3.3.2
	 *
	 * @param 	string 	$text 	Widget Text
	 * @return 	string 			Widget Text, with processed shortcodes
	 */
	public function shortcode_widget($text)
	{

		global $wp_query;

		// shortcode() above will only run if we're in the loop,
		// to prevent SEO plugins going crazy. Let's trick
		// this widget block into thinking it's in the loop.
		$in_the_loop = $wp_query->in_the_loop;
		$wp_query->in_the_loop = true;

		// Render the shortcodes
		$text = do_shortcode($text);

		// Revert wp_query
		$wp_query->in_the_loop = $in_the_loop;

		// Return the processed text
		return $text;
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since 	3.2.6
	 *
	 * @return 	object Class.
	 */
	public static function get_instance()
	{

		if (!isset(self::$instance) && !(self::$instance instanceof self)) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}
