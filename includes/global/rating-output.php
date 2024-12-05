<?php

/**
 * Rating Output class
 * 
 * @package Comment_Rating_Field_Pro
 * @author Tim Carr
 * @version 1.0
 */
class Comment_Rating_Field_Pro_Rating_Output
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
	 * Holds the base class object.
	 *
	 * @since 	3.3.5
	 *
	 * @var 	object
	 */
	private $base;

	/**
	 * Holds the Group
	 *
	 * @since 	3.2.0
	 *
	 * @var 	array
	 */
	public $group;

	/**
	 * Constructor
	 *
	 * @since 3.2.0
	 */
	public function __construct()
	{

		// Actions and Filters
		if (!is_admin()) {
			add_action('wp', array($this, 'register_comment_form_hooks'));

			// Non-singular Actions and Filters
			add_action('wp_enqueue_scripts', array($this, 'css'), 10);
			add_action('wp_head', array($this, 'custom_css'), 99);
			add_filter('the_excerpt', array($this, 'display_average_rating_excerpt')); // Displays Average Rating for Excerpt
			add_filter('the_excerpt_rss', array($this, 'display_average_rating_rss')); // Displays Average Rating for RSS Feeds
		}

		// Admin-specific
		if (is_admin()) {
			add_filter('comment_text', array($this, 'display_comment_rating')); // Displays Rating on Comments
		}
	}

	/**
	 * Sort Posts by their average rating on Post Type Archives.
	 *
	 * See https://www.wpzinc.com/documentation/review-comment-integration-pro-plugin/developers-sort-post-type-archives-average-rating/
	 * for how to enable this.
	 *
	 * @since 		3.2.7
	 * @deprecated 	3.5.0
	 *
	 * @param 		$query 	WP_Query 	WordPress Query
	 * @return 				WP_Query 	WordPress Query
	 */
	public function sort_posts_by_rating($query)
	{

		// Warn developers that this function is deprecated
		if (function_exists('_deprecated_function')) {
			_deprecated_function(
				'Comment_Rating_Field_Pro_Rating_Output::get_instance()->sort_posts_by_rating',
				'3.5.0',
				'Comment_Rating_Field_Pro_Rating_Query::get_instance()->sort_posts_by_rating'
			);
		}

		// Pass query on to the Query Class
		return Comment_Rating_Field_Pro_Query::get_instance()->sort_posts_by_rating($query);
	}

	/**
	 * Registers actions and filters if a group is found matching the singular Post, Page or CPT
	 *
	 * @since 	3.2.0
	 */
	public function register_comment_form_hooks()
	{

		global $post;

		// Bail if no Post object exists
		if (!$post) {
			return;
		}

		// Bail if this Post has an override to disable rating fields
		$disabled = $this->post_rating_fields_disabled($post->ID);
		if ($disabled) {
			return;
		}

		// Find group
		$this->group = Review_Comment_Integration_Pro_Groups::get_instance()->get_group_by_post_id($post->ID);
		if (!$this->group) {
			return;
		}

		// If not a feed and not a singular Post/Page/CPT, bail
		if (!is_comment_feed() && !is_singular()) {
			return;
		}

		// Because we have found a group, we need to output JS and CSS and register our other hooks
		add_action('wp_enqueue_scripts', array($this, 'scripts'));

		/**
		 * Rating Output
		 */
		// If we're on a comments feed, register some different filters
		if (is_comment_feed()) {
			add_filter('comment_text_rss', array($this, 'display_comment_rating_rss'));
			add_action('comment_text', array($this, 'display_comment_rating_rss'));
		} else {
			add_filter('wp_list_comments_args', array($this, 'wp_list_comments_args')); // Inject a callback prior to outputting Comments
			add_filter('comments_array', array($this, 'filter_comments_by_rating'), 10, 2); // Filter Comments by Rating
			add_filter('get_comment_text', array($this, 'display_comment_rating')); // Displays Ratings on Comments
			add_filter('the_content', array($this, 'display_average_rating_content')); // Displays Average Rating for Content
		}

		/**
		 * Rating Input
		 */
		// Check if the group is set to limit by role
		$user_can_comment = Review_Comment_Integration_Pro_Groups::get_instance()->user_can_comment($this->group);
		if (!$user_can_comment) {
			return;
		}

		// Register actions to display the comment field, depending on the group settings
		// For Jetpack, we can only output the fields after the comment form, otherwise
		// they won't display
		if (class_exists('Jetpack_Comments')) {
			add_action('comment_form_after', array($this, 'display_rating_fields'));
			return;
		}

		switch ($this->group['ratingInput']['position']) {
				/**
			 * Before All Fields
			 */
			case 'above':
				// Before All Fields
				add_action('comment_form_logged_in_after', array($this, 'display_rating_fields')); // Logged in
				add_action('comment_form_before_fields', array($this, 'display_rating_fields')); // Guest
				break;

				/**
				 * Before Comment Field
				 */
			case 'middle':
				// Before Comment Field
				add_action('comment_form_logged_in_after', array($this, 'display_rating_fields')); // Logged in
				add_action('comment_form_after_fields', array($this, 'display_rating_fields')); // Guest
				break;

				/**
				 * After Comment Field
				 */
			default:
				add_filter('comment_form_field_comment', array($this, 'display_rating_fields'));
				break;
		}
	}

	/**
	 * Register or enqueue any CSS
	 *
	 * @since 	3.2.0
	 */
	public function css()
	{

		// Get base instance
		$this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

		// Enqueue CSS and Custom CSS
		wp_enqueue_style($this->base->plugin->name, $this->base->plugin->url . 'assets/css/frontend.css', array(), false);

		// Allow devs to run their own actions now
		do_action('comment_rating_field_pro_rating_output_css');
	}

	/**
	 * Loads CSS for RSS Feeds
	 *
	 * @since 	3.3.5
	 */
	public function rss_css()
	{

		// Get base instance
		$this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

		// Output XML stylesheet link
		echo '<?xml-stylesheet type="text/xsl" href="' . $this->base->plugin->url . 'assets/css/rss.xsl"?>';

		// Allow devs to run their own actions now
		do_action('comment_rating_field_pro_rating_output_rss_css');
	}

	/**
	 * Register or enqueue any JS
	 *
	 * @since 	3.2.0
	 */
	public function scripts()
	{

		global $post;

		wp_enqueue_script('jquery');
		wp_enqueue_script($this->base->plugin->name, $this->base->plugin->url . 'assets/js/min/frontend-min.js', array('jquery'), $this->base->plugin->version, true);
		wp_localize_script($this->base->plugin->name, 'crfp', array(
			'ajax_url'            	=> admin_url('admin-ajax.php'),
			'disable_replies' 		=> $this->group['ratingInput']['disableReplies'],
			'enable_half_ratings' 	=> $this->group['ratingInput']['enableHalfRatings'],
			'nonce'                 => wp_create_nonce($this->base->plugin->name . '_nonce'),
			'post_id'               => (is_singular() ? $post->ID : 0),
		));

		// Allow devs to run their own actions now
		do_action('comment_rating_field_pro_rating_output_scripts');
	}

	/**
	 * Output each group's custom CSS
	 *
	 * @since 	3.2.0
	 */
	public function custom_css()
	{

		// Load group
		$groups = Review_Comment_Integration_Pro_Groups::get_instance()->get_all('name', 'ASC', -1);

		// Check groups exist
		if (!is_array($groups) || count($groups) == 0) {
			return;
		}

		// Iterate through groups, outputting CSS customisations
		ob_start();
?>
		<style type="text/css">
			<?php
			foreach ($groups as $group) {
				// Manually force size if not set
				if (!isset($group['css']['starSize']) || empty($group['css']['starSize'])) {
					$group['css']['starSize'] = 16;
				}
			?>div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?> {
				min-height: <?php echo $group['css']['starSize']; ?>px;
			}

			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>span,
			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>a {
				line-height: <?php echo $group['css']['starSize']; ?>px;
			}

			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>span.rating-always-on {
				width: <?php echo ($group['css']['starSize'] * $group['ratingInput']['maxRating']); ?>px;
				height: <?php echo $group['css']['starSize']; ?>px;
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starBackgroundColor']); ?>&size=<?php echo $group['css']['starSize']; ?>);
			}

			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>span.crfp-rating {
				display: block !important;
				margin-right: 0;
				width: <?php echo ($group['css']['starSize'] * $group['ratingInput']['maxRating']); ?>px;
				height: <?php echo $group['css']['starSize']; ?>px;
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starColor']); ?>&size=<?php echo $group['css']['starSize']; ?>);
			}

			div.rating-container span.crfp-rating {
				display: inline-block !important;
				margin-right: .4rem;
				width: <?php echo ($group['css']['starSize'] * $group['ratingInput']['maxRating']); ?>px;
				height: <?php echo $group['css']['starSize']; ?>px;
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starColor']); ?>&size=<?php echo $group['css']['starSize']; ?>);
			}

			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.star-rating a {
				width: <?php echo $group['css']['starSize']; ?>px;
				max-width: <?php echo $group['css']['starSize']; ?>px;
				height: <?php echo $group['css']['starSize']; ?>px;
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starBackgroundColor']); ?>&size=<?php echo $group['css']['starSize']; ?>);
			}

			p.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.star-rating {
				width: <?php echo $group['css']['starSize']; ?>px;
				height: <?php echo $group['css']['starSize']; ?>px;
			}

			p.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.star-rating a {
				width: <?php echo $group['css']['starSize']; ?>px;
				max-width: <?php echo $group['css']['starSize']; ?>px;
				height: <?php echo $group['css']['starSize']; ?>px;
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starBackgroundColor']); ?>&size=<?php echo $group['css']['starSize']; ?>);
			}

			p.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.star-rating-hover a {
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starInputColor']); ?>&size=<?php echo $group['css']['starSize']; ?>);
			}

			p.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.star-rating-on a {
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starColor']); ?>&size=<?php echo $group['css']['starSize']; ?>);
			}

			p.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.rating-cancel {
				width: <?php echo $group['css']['starSize'] * 2; ?>px;
				height: <?php echo $group['css']['starSize'] * 2; ?>px;
			}

			p.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.rating-cancel a {
				width: <?php echo $group['css']['starSize'] * 2; ?>px;
				height: <?php echo $group['css']['starSize'] * 2; ?>px;
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=delete&color=<?php echo str_replace('#', '', $group['css']['starBackgroundColor']); ?>&size=<?php echo $group['css']['starSize'] * 2; ?>);
			}

			p.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.rating-cancel.star-rating-hover a {
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=delete&color=<?php echo str_replace('#', '', $group['css']['starInputColor']); ?>&size=<?php echo $group['css']['starSize'] * 2; ?>) !important;
			}

			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.crfp-bar .bar {
				background-color: <?php echo $group['css']['starBackgroundColor']; ?>;
			}

			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>div.crfp-bar .bar .fill {
				background-color: <?php echo $group['css']['starColor']; ?>;
			}

			div.rating-container.crfp-group-<?php echo $group['groupID'] . ' '; ?>span.label {
				margin-right: .5rem;
			}

			/* COMMENT ANALYTICS */
			/* Review box */
			.review-box-average {
				display: flex;
				box-shadow: 0px 0px 5px -2px rgba(0, 0, 0, 0.5);
			}

			/* Review box - star average */
			.average-star {
				width: 20%;
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 8px;
				font-size: 2rem;
				font-weight: bold;
			}

			.average-star>span {
				margin-top: 4px;
			}

			.average-star>i {
				color: <?php echo $group['css']['starColor']; ?>;
			}

			/* Review box - view list */
			.average-view-list {
				flex-grow: 1;
				border-left: thin solid rgba(0, 0, 0, 0.1);
				border-right: thin solid rgba(0, 0, 0, 0.1);
				padding: 0.7rem;
			}

			.average-view-list .view-list-row {
				display: flex;
				justify-content: space-between;
				gap: 8px;
			}

			.average-view-list .progress {
				flex-grow: 1;
				position: relative;
			}

			.average-view-list .view-list-row :last-child {
				width: 22%;
				text-align: right;
			}

			.average-view-list .progress::before {
				content: "";
				background-color: #f2f2f2;
				width: 100%;
				height: 8px;
				border-radius: 999999px;
				position: absolute;
				top: 50%;
				transform: translatey(-50%);
			}

			.average-view-list .progress::after {
				content: "";
				background-color: <?php echo $group['css']['starColor']; ?>;
				width: var(--value);
				height: 8px;
				border-radius: 999999px;
				position: absolute;
				top: 50%;
				transform: translatey(-50%);
			}

			/* Review box - button */
			.average-button {
				width: 30%;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				padding: 0.7rem;
			}

			.average-button :first-child {
				margin: 0;
				padding: 4px 0;
			}

			.average-button :last-child {
				margin: 0;
				padding: 0 1rem;
				background-color: <?php echo $group['css']['starColor']; ?>;
				color: #fff;
				border-radius: 0.4rem;
			}

			@media screen and (max-width: 600px) {
				.review-box-average {
					flex-direction: column;
					position: relative;
				}

				.average-star>span {
					margin-left: 20px;
				}

				.average-view-list {
					font-size: 0.7em;
				}

				.average-button {
					position: absolute;
					right: 0;
					align-items: end;
					width: 60%;
					font-size: 0.7em;
					padding-top: 0;
				}
			}


			/* COMMENT FORM*/
			#commentform .crfp-field.crfp-group-1 {
				text-align: center;
			}

			#commentform .crfp-field.crfp-group-1 .star-rating-applied,
			#commentform .crfp-field.crfp-group-1 .star-rating-on,
			#commentform .crfp-field.crfp-group-1 .star-rating-live {
				width: <?php echo $group['css']['starSize'] * 2; ?>px !important;
				height: <?php echo $group['css']['starSize'] * 2; ?>px !important;
				margin-left: 4px;
				margin-right: 4px;
			}

			#commentform .crfp-field.crfp-group-1 .star-rating-applied a,
			#commentform .crfp-field.crfp-group-1 .star-rating-on a,
			#commentform .crfp-field.crfp-group-1 .star-rating-live a {
				width: <?php echo $group['css']['starSize'] * 2; ?>px;
				max-width: <?php echo $group['css']['starSize'] * 2; ?>px;
				height: <?php echo $group['css']['starSize'] * 2; ?>px;
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starBackgroundColor']); ?>&size=<?php echo $group['css']['starSize'] * 2; ?>);
			}

			#commentform .crfp-field.crfp-group-1 .star-rating-hover a {
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starInputColor']); ?>&size=<?php echo $group['css']['starSize'] * 2; ?>);
			}


			#commentform .crfp-field.crfp-group-1 .star-rating-on a {
				background-image: url(<?php echo $this->base->plugin->url; ?>/views/global/svg.php?svg=star&color=<?php echo str_replace('#', '', $group['css']['starColor']); ?>&size=<?php echo $group['css']['starSize'] * 2; ?>);
			}

			#commentform .form-submit {
				text-align: center;
			}

			#commentform .form-submit input {
				width: 60%;
				border-radius: 4px;
				margin-right: 0;
			}


			<?php
			}
			?>
		</style>
<?php
		// Get output
		$css = ob_get_clean();

		// Allow devs to filter the output CSS
		$css = apply_filters('comment_rating_field_pro_rating_output_custom_css', $css, $groups);

		// Output minified CSS
		echo $this->minify($css);
	}

	/**
	 * Helper method to minify a string of data.
	 *
	 * @since 	3.2.0
	 *
	 * @param 	string 	$string  	String of data to minify.
	 * @return 	string 	$string 	Minified string of data.
	 */
	public function minify($string)
	{

		$clean = preg_replace('!/\*.*?\*/!s', '', $string);
		$clean = preg_replace('/\n\s*\n/', "\n", $clean);
		$clean = str_replace(array("\r\n", "\r", "\t", "\n", '  ', '    ', '     '), '', $clean);

		return $clean;
	}

	/**
	 * Output Comment sorting options above the comments list.
	 *
	 * @since 	3.5.0
	 *
	 * @param 	array 	$args 	wp_list_comments() arguments
	 * @return 	array 			wp_list_comments() arguments
	 */
	public function wp_list_comments_args($args)
	{

		global $post, $in_comment_loop;

		// If we're not in the comments loop, bail
		if (!isset($in_comment_loop) || !$in_comment_loop) {
			return $args;
		}

		// If no group, bail
		if (!$this->group) {
			return $args;
		}

		// Output Comment Sorting Dropdown
		$this->output_comment_sorting_dropdown($post->ID, $args['style']);

		// Return original arguments
		return $args;
	}

	/**
	 * Output Comment sorting options
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 		Post ID
	 * @param 	string 	$output_style 	Output Style (ul|ol|div)
	 */
	public function output_comment_sorting_dropdown($post_id, $output_style)
	{

		// Get base instance
		$this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

		// Get group
		$group = Review_Comment_Integration_Pro_Groups::get_instance()->get_group_by_post_id($post_id);

		// Bail if the group doesn't have sorting options enabled
		if (!$group['ratingOutputComments']['showSortingOptions']) {
			return;
		}

		// Get sorting option
		$sort = (isset($_REQUEST['sort']) ? sanitize_text_field($_REQUEST['sort']) : '');

		// Load view
		include_once($this->base->plugin->folder . '/views/global/comment-list-sorting.php');
	}

	/**
	 * If a query var to filter comments is set, build a new comments array comprising
	 * of just the comments we want.
	 *
	 * Also sorts comments if the sort query var is specified
	 *
	 * @since 	3.2.0
	 *
	 * @param 	array 	$comments 	Comments
	 * @param 	int 	$post_id 	Post ID
	 * @return 	array 				Comments
	 */
	public function filter_comments_by_rating($comments, $post_id)
	{

		// Bail if neither a rating or sort parameter was provided
		if (!isset($_GET['rating']) && !isset($_GET['sort'])) {
			return $comments;
		}

		// Build our custom comment arguments
		$comment_args = array(
			'order'   	=> 'ASC',
			'orderby' 	=> 'comment_date_gmt',
			'status'  	=> 'approve',
			'post_id' 	=> $post_id,
		);

		// Show comments with specific rating
		if (isset($_GET['rating'])) {
			$comment_args['meta_key'] 	= 'crfp-average-rating';
			$comment_args['meta_value'] = (string) sanitize_text_field($_GET['rating']);
		}

		// Sort comments
		if (isset($_GET['sort']) && !empty($_GET['sort'])) {
			// Get the orderby and order parameters
			list($orderby, $order) = explode('_', sanitize_text_field($_GET['sort']));

			// Depending on the orderby parameter, add query arguments
			switch ($orderby) {
					/**
				 * Rating
				 */
				case 'rating':
					$comment_args['meta_key'] 	= 'crfp-average-rating';
					$comment_args['orderby'] 	= 'meta_value_num';
					$comment_args['order'] 		= $order;
					break;

					/**
					 * Allow devs to sort now
					 */
				default:
					$comment_args = apply_filters('comment_rating_field_pro_rating_output_filter_comments_by_rating_sort', $comment_args, $orderby, $order, sanitize_text_field($_GET['sort']));
					break;
			}
		}

		// Run the query and return the comments
		$comments = get_comments($comment_args);

		return $comments;
	}

	/**
	 * Main function to display average rating on excerpts
	 *
	 * Called on every excerpt, so we need to check if a group exists for each Post
	 *
	 * @since 	3.2.0
	 *
	 * @param 	string 	$excerpt 	Post Excerpt
	 * @return 	string 		 		Post Excerpt with Average Rating HTML
	 */
	public function display_average_rating_excerpt($excerpt)
	{

		global $post;

		/**
		 * Check if we're in the loop
		 * If not, return excerpt
		 * This prevents us generating HTML multiple times, which might happen if an SEO plugin scans the_content
		 * for its own usage.
		 */
		if (!in_the_loop()) {
			return $excerpt;
		}

		// Find group
		$group = Review_Comment_Integration_Pro_Groups::get_instance()->get_group_by_post_id($post->ID);
		if (!$group) {
			return $excerpt;
		}

		// Build rating HTML
		$html = $this->build_average_rating_html($post->ID, $group, 'excerpt', $excerpt);

		// Filter the output
		$html = apply_filters('crfp_display_post_rating_excerpt', $html, $group, $excerpt, $post->ID, $post);

		// Return
		return $html;
	}

	/**
	 * Main function to display average rating on content
	 *
	 * Called when on a singular Post/Page/CPT and we already know there is a group available
	 *
	 * @since 3.2.0
	 *
	 * @param string $content Post Content
	 * @return string 		 Post Content with Average Rating HTML
	 */
	public function display_average_rating_content($content)
	{

		global $post;

		/**
		 * Check if we're in the loop
		 * If not, return content
		 * This prevents us generating HTML multiple times, which might happen if an SEO plugin scans the_content
		 * for its own usage.
		 */
		if (!in_the_loop()) {
			return $content;
		}

		// Build rating HTML
		$html = $this->build_average_rating_html($post->ID, $this->group, 'content', $content);

		// Filter the output
		$html = apply_filters('crfp_display_post_rating_content', $html, $this->group, $content, $post->ID, $post);

		// Return
		return $html;
	}

	/**
	 * Main function to display average rating on RSS Feeds
	 *
	 * Called on every feed item, so we need to check if a group exists for each Post
	 *
	 * @since 	3.2.7
	 *
	 * @param 	string 	$excerpt 	RSS Post Excerpt
	 * @return 	string 		 		RSS Post Excerpt with Average Rating HTML
	 */
	public function display_average_rating_rss($excerpt)
	{

		global $post;

		/**
		 * Check if we're in the loop
		 * If not, return excerpt
		 * This prevents us generating HTML multiple times, which might happen if an SEO plugin scans the_content
		 * for its own usage.
		 */
		if (!in_the_loop()) {
			return $excerpt;
		}

		// Find group
		$group = Review_Comment_Integration_Pro_Groups::get_instance()->get_group_by_post_id($post->ID);
		if (!$group) {
			return $excerpt;
		}

		// Bail if rating output on RSS is disabled
		if ($group['ratingOutputRSS']['enabled'] == 0) {
			return $excerpt;
		}

		// Get average rating and total rating
		$totalRatings 			= $this->get_post_total_ratings($post->ID);
		$averageRating 			= $this->get_post_average_rating($post->ID);

		// Build output
		$html = '<p>' . $averageRating . '/5';
		if ($group['ratingOutputRSS']['totalRatings'] == 1) {
			$html .= ' ' . $group['ratingOutputRSS']['totalRatingsBefore'] . ' ' . $totalRatings . ' ' . $group['ratingOutputRSS']['totalRatingsAfter'];
		}
		$html .= '</p>';

		// Filter the output
		$html = apply_filters('crfp_display_average_rating_rss', $html, $group, $excerpt, $totalRatings, $averageRatings);

		// Prepend or append rating to excerpt
		// Append average rating before or after content
		switch ($group['ratingOutputRSS']['position']) {
				/**
			 * Above Content
			 */
			case 'above':
				return $html . $excerpt;
				break;

				/**
				 * Below Content
				 */
			case '':
			default:
				return $excerpt . $html;
				break;
		}
	}

	/**
	 * Returns the average rating HTML markup, which is used by:
	 * - content
	 * - excerpt
	 * - shortcode
	 *
	 * @since 	3.2.0
	 *
	 * @param 	int 	$post_id 					Post ID
	 * @param 	array 	$group 						Rating Group
	 * @param 	string 	$type 						Content Type (excerpt|content|shortcode)
	 * @param 	string 	$content 					Existing Content
	 * @param 	bool 	$disable_schema_markup 		Disable Schema Markup (overrides Group settings)
	 * @return 	string 								Average Rating HTML with Content
	 */
	public function build_average_rating_html($post_id, $group, $type, $content = '', $disable_schema_markup = false)
	{

		// Get post
		$post = get_post($post_id);

		// Define the setting group to use
		$setting_group = 'ratingOutput' . ($type == 'rss' ? 'RSS' : ucfirst($type));

		// Get rating data
		$totals 				= $this->get_post_totals($post_id);
		$averageRatings 		= $this->get_post_averages($post_id);
		$totalRatings 			= $this->get_post_total_ratings($post_id);
		$averageRating 			= $this->get_post_average_rating($post_id);
		$ratingSplit 			= $this->get_post_rating_split($post_id);
		$ratingSplitPercentages = $this->get_post_rating_split_percentages($post_id);

		// Define a blank array of empty ratings, if we need it for our rating split or rating split by percentage
		$blank_rating_arr = array();
		if ($group['ratingInput']['enableHalfRatings']) {
			// Blank ratings should include half ratings
			for ($i = 0.5; $i <= $group['ratingInput']['maxRating']; $i += 0.5) {
				$blank_rating_arr[(string) $i] = 0;
			}
		} else {
			// Blank ratings should not include half ratings
			for ($i = 1; $i <= $group['ratingInput']['maxRating']; $i++) {
				$blank_rating_arr[(string) $i] = 0;
			}
		}

		// If style = 'Filled and Empty Colors', ensure that the rating split and rating split percentage
		// has all ratings (0, 1, 2, 3 etc) defined.  If any are not defined, set them to zero, so an empty
		// bar will show and therefore honor this setting.
		if ($group[$setting_group]['style'] == 'grey') {
			// For the rating split, ensure that each rating (0, 1, 2, 3 etc) = 0 if
			// no key is defined for that rating.
			foreach ($blank_rating_arr as $i => $zero) {
				if (!isset($ratingSplit[(string) $i])) {
					$ratingSplit[(string) $i] = $zero;
				}
			}

			// For the rating split percentage, ensure that each rating (0, 1, 2, 3 etc) = 0 if
			// no key is defined for that rating.
			foreach ($blank_rating_arr as $i => $zero) {
				if (!isset($ratingSplitPercentages[(string) $i])) {
					$ratingSplitPercentages[(string) $i] = $zero;
				}
			}
		}

		// Sort the rating split and rating split percentages, lowest to highest
		ksort($ratingSplit);
		ksort($ratingSplitPercentages);

		// Bail if output is set to never display
		if ($group[$setting_group]['enabled'] == 0) {
			return $content;
		}

		// Bail if output is conditional on ratings existing
		if ($group[$setting_group]['enabled'] == 1) {
			// If no ratings, bail
			if ($totalRatings == 0) {
				return $content;
			}

			// If ratings, check they are for fields in this group
			$ratingsForGroupFields = false;
			foreach ($group['fields'] as $field) {
				if (isset($totals[$field['fieldID']])) {
					$ratingsForGroupFields = true;
					break;
				}
			}
			if (!$ratingsForGroupFields) {
				return $content;
			}
		}

		// Start Display
		$html = '';

		// Filter the schema item name
		$item_name = apply_filters('comment_rating_field_pro_rating_output_build_average_rating_html_schema_title', strip_tags($post->post_title), $group);

		// Start
		$html = '<div class="review-comment-integration-pro-plugin';

		// Add CSS Classes, if specified
		if (isset($group[$setting_group]['cssClass']) && !empty($group[$setting_group]['cssClass'])) {
			$html .= ' ' . $group[$setting_group]['cssClass'];
		}
		$html .= '"';

		// Add ID, if specified
		if (isset($group[$setting_group]['cssID']) && !empty($group[$setting_group]['cssID'])) {
			$html .= ' id="' . $group[$setting_group]['cssID'] . '"';
		}

		// Schema Type        
		// Don't output schema on excerpts
		if (!empty($group['schema_type']) && $type != 'excerpt' && !$disable_schema_markup) {
			$html .= ' itemscope itemtype="http://schema.org/' . $group['schema_type'] . '">';
		} else {
			// Close opening tag
			$html .= '>';
		}

		// Output schema for the name
		if (!$disable_schema_markup) {
			$html .= '<meta itemprop="name" content="' . $item_name . '" />';
		}

		// Show Average  
		if ($group[$setting_group]['average'] > 0) {
			// Open Average
			$html .= '<div class="rating-container crfp-group-' . $group['groupID'] . '"';

			// Output schema if not an excerpt
			if ($type != 'excerpt' && !$disable_schema_markup) {
				$html .= ' itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
							<meta itemprop="ratingValue" content="' . $averageRating . '" />
							<meta itemprop="reviewCount" content="' . $totalRatings . '" />
							<span itemprop="itemReviewed" itemscope itemtype="http://schema.org/CreativeWorkSeries">
								<meta itemprop="name" content="' . $item_name . '" />
							</span>';
			} else {
				$html .= '>';
			}

			// Average Label + Link to Comments: Before Rating
			if (!empty($group[$setting_group]['averageLabel'])) {
				$html .= '<span class="label">';

				if (!isset($group[$setting_group]['averageLabelPosition']) || empty($group[$setting_group]['averageLabelPosition'])) {
					if ($group[$setting_group]['linkToComments']) {
						$html .= '<a href="' . get_permalink($post_id) . '#comments">' . $group[$setting_group]['averageLabel'] . '</a>';
					} else {
						$html .= $group[$setting_group]['averageLabel'];
					}
				}

				$html .= '</span>';
			}

			// Average Bars or Stars
			switch ($group[$setting_group]['average']) {
					/**
				 * Bars
				 */
				case 2:
					// Iterate through each rating from highest to lowest
					foreach (array_reverse($ratingSplitPercentages, true) as $rating => $percentage) {
						// Make bar clickable if comment filtering is enabled
						if (isset($group[$setting_group]['filterComments']) && $group[$setting_group]['filterComments'] == 1) {
							// Build URL with ?rating arg
							$url = add_query_arg(array(
								'rating' => $rating,
							), get_permalink($post_id)) . '#comments';

							// HTML
							$html .= '<div class="crfp-bar rating-' . $rating . ((($group[$setting_group]['enabled'] == 2 and $totalRatings == 0) or $group[$setting_group]['style'] == 'grey') ? ' rating-always-on' : 'rating-filled-only') . '">
								<span class="label">
									<a href="' . $url . '" title="' . __('View', 'review-comment-integration-pro-plugin') . ' ' . $rating . ' ' . __('reviews', 'review-comment-integration-pro-plugin') . '">
										' . $rating . ' ' . __('stars', 'review-comment-integration-pro-plugin') . '
									</a>
								</span>
								<a class="bar" href="' . $url . '" title="' . __('View', 'review-comment-integration-pro-plugin') . ' ' . $rating . ' ' . __('reviews', 'review-comment-integration-pro-plugin') . '">
									<span class="fill" style="width:' . $percentage . '%;">&nbsp;</span>
								</a>
								<a class="count" href="' . $url . '" title="' . __('View', 'review-comment-integration-pro-plugin') . ' ' . $rating . ' ' . __('reviews', 'review-comment-integration-pro-plugin') . '">
									' . (isset($ratingSplit[$rating]) ? $ratingSplit[$rating] : 0) . '
								</a>
							</div>';
						} else {
							$html .= '<div class="crfp-bar rating-' . $rating . ((($group[$setting_group]['enabled'] == 2 and $totalRatings == 0) or $group[$setting_group]['style'] == 'grey') ? ' rating-always-on' : 'rating-filled-only') . '">
								<span class="label">' . $rating . ' ' . __('stars', 'review-comment-integration-pro-plugin') . '</span>
								<span class="bar">
									<span class="fill" style="width:' . $percentage . '%;">&nbsp;</span>
								</span>
								<span class="count">' . (isset($ratingSplit[$rating]) ? $ratingSplit[$rating] : 0) . '</span>
							</div>';
						}
					}

					break;

					/**
					 * Stars
					 */
				case 1:
					$html .= '<span>' . $averageRating . '/5 </span>';
					$html .= '
			       		<span class="' . ((($group[$setting_group]['enabled'] == 2 && $totalRatings == 0) || $group[$setting_group]['style'] == 'grey') ? 'rating-always-on' : 'rating-filled-only') . '">
							<span class="crfp-rating crfp-rating-' . str_replace('.', '-', $averageRating) . '" style="width:' . ($averageRating * $group['css']['starSize']) . 'px">';
					$html .= $averageRating;
					$html .= '
							</span>
						</span>
						<a href="#comments"> (' . $totalRatings . ' đánh giá)</a>';
					break;
			}

			// Show Rating Number
			$rating_number = '';
			switch ($group[$setting_group]['showRatingNumber']) {

					/**
				 * Percentage
				 */
				case 2:
					$rating_number = '<span class="crfp-rating-number-percentage">' . (($averageRating / $group['ratingInput']['maxRating']) * 100) . '%</span>';
					break;

					/**
					 * Number
					 */
				case 1:
					$rating_number = '<span class="crfp-rating-number">' . $averageRating . '</span>';
					break;
			}
			$rating_number = apply_filters('comment_rating_field_pro_rating_output_build_average_rating_html_show_rating_number_average', $rating_number, $group, $averageRating);
			$html .= $rating_number;

			if (!empty($group[$setting_group]['averageLabel'])) {
				if (isset($group[$setting_group]['averageLabelPosition']) && $group[$setting_group]['averageLabelPosition'] == 'after') {
					if ($group[$setting_group]['linkToComments']) {
						$html .= '<a href="' . get_permalink($post_id) . '#comments">' . $group[$setting_group]['averageLabel'] . '</a>';
					} else {
						$html .= $group[$setting_group]['averageLabel'];
					}
				}
			}

			// Total Ratings
			if ($group[$setting_group]['totalRatings']) {
				// Get before/after text
				$total_ratings_before = isset($group[$setting_group]['totalRatingsBefore']) ? $group[$setting_group]['totalRatingsBefore'] : __('from', 'review-comment-integration-pro-plugin');
				$total_ratings_after = isset($group[$setting_group]['totalRatingsAfter']) ? $group[$setting_group]['totalRatingsAfter'] : __('ratings', 'review-comment-integration-pro-plugin');

				// Append Total Ratings to HTML
				$html .= '
				   	<span class="total">
				   		' . $total_ratings_before . '
				   		<span>' . $totalRatings . '</span>
				   		' . $total_ratings_after . '
				   	</span>';
			}

			// Close Div
			$html .= '
			</div>';
		}

		// Show Breakdown
		switch ($group[$setting_group]['showBreakdown']) {
				/**
			 * Stars
			 */
			case 1:
				// Iterate through fields
				foreach ($group['fields'] as $field) {
					// Average Rating for Field
					if (!isset($averageRatings[$field['fieldID']])) {
						$averageRatings[$field['fieldID']] = 0;
					}

					// Field
					$html .= '
					<div class="rating-container crfp-group-' . $group['groupID'] . ' crfp-stars">
						<span class="label">' . $field['label'] . '</span>
						<span class="' . ((($group[$setting_group]['enabled'] == 2 && $totalRatings == 0) || $group[$setting_group]['style'] == 'grey') ? 'rating-always-on' : 'rating-filled-only') . '">
					    	<span class="crfp-rating crfp-rating-' . str_replace('.', '-', $averageRatings[$field['fieldID']]) . '" style="width:' . ($averageRatings[$field['fieldID']] * $group['css']['starSize']) . 'px">
					    		' . $averageRatings[$field['fieldID']] . ' Show Breakdown Stars
					    	</span>
					   	</span>';

					// Show Rating Number
					$rating_number = '';
					switch ($group[$setting_group]['showRatingNumber']) {

							/**
						 * Percentage
						 */
						case 2:
							$rating_number = '<span class="crfp-rating-number-percentage">' . (($averageRatings[$field['fieldID']] / $group['ratingInput']['maxRating']) * 100) . '%</span>';
							break;

							/**
							 * Number
							 */
						case 1:
							$rating_number = '<span class="crfp-rating-number">' . $averageRatings[$field['fieldID']] . '</span>';
							break;
					}
					$rating_number = apply_filters('comment_rating_field_pro_rating_output_build_average_rating_html_show_rating_number_breakdown', $rating_number, $group, $averageRatings[$field['fieldID']], $field);
					$html .= $rating_number;

					// Close Field
					$html .= '</div>';
				}
				break;
		}

		// Close Schema Type
		$html .= '</div>';

		// Filter HTML
		$html = apply_filters('comment_rating_field_pro_rating_output_build_average_rating_html', $html, $group);

		// Append average rating before or after content
		switch ($group[$setting_group]['position']) {
				/**
			 * Above Content
			 */
			case 'above':
				return $html . $content;
				break;

				/**
				 * Below Content
				 */
			case '':
			default:
				return $html . $content;
				// 				return $content . $html;
				break;
		}
	}

	/**
	 * Main function to display rating on a comment
	 *
	 * Called from both the frontend and admin, so if $this->group isn't populated, we'll try to populate it again
	 *
	 * @since 	3.2.0
	 *
	 * @param 	string 	$comment 		Comment Text
	 * @param 	int 	$post_id 		Post ID
	 * @param 	int 	$comment_id 	Comment ID
	 * @return 	string 					Comment Text with Rating
	 */
	public function display_comment_rating($comment, $post_id = '', $comment_id = '')
	{

		global $post;

		// If a Post ID and Comment ID were specified, the user has manually called this function,
		// so we always need to fetch the group.
		// Always get the group if we're in the WordPress Admin, as we'll be viewing comments from different
		// Posts on a single screen (vs. the frontend, where we only view comments for a single Post)
		if (!empty($post_id) && !empty($comment_id) || is_admin()) {
			$fetch_group = true;
		} else {
			$fetch_group = false;
		}

		// Get post and comment ID, if none have been supplied (expected if called via the comment_text hook)
		if (empty($post_id)) {
			$post_id = $post->ID;
		}
		if (empty($comment_id)) {
			$comment_id = get_comment_ID();
		}

		// Fetch the rating group now if we need to
		if ($fetch_group || empty($this->group)) {
			// Find group
			$this->group = Review_Comment_Integration_Pro_Groups::get_instance()->get_group_by_post_id($post_id);
		}

		// Check if we have a group. If not, bail
		if (!$this->group) {
			return $comment;
		}

		// Build comment rating HTML
		$html = $this->build_comment_rating_html($post_id, $comment_id, $this->group, $comment);

		// Filter
		$html = apply_filters('comment_rating_field_pro_rating_output_display_comment_rating', $html, $this->group, $comment_id, $comment, $post_id, $post);

		// Return
		return $html;
	}

	/**
	 * Main function to display rating on a comment in a comment RSS feed.
	 *
	 * @since 	3.3.5
	 *
	 * @param 	string 	$comment_text 	Comment Text
	 * @return 	string 					Comment Text
	 */
	public function display_comment_rating_rss($comment_text)
	{

		global $post;

		// Get post and comment ID
		$post_id 	= $post->ID;
		$comment_id = get_comment_ID();

		// Check if we have a group. If not, bail
		if (!$this->group) {
			return $comment_text;
		}

		// Build comment rating RSS output
		$rss = $this->build_comment_rating_rss($post_id, $comment_id, $this->group, $comment_text);

		// Filter
		$rss = apply_filters('comment_rating_field_pro_rating_output_display_comment_rating_rss', $rss, $this->group, $comment_id, $comment_text, $post_id, $post);

		// Return
		return $rss;
	}

	/**
	 * Returns the average rating HTML markup for individual comments
	 *
	 * @since 	3.2.0
	 *
	 * @param 	int 	$post_id 		Post ID
	 * @param 	int 	$comment_id 	Comment ID
	 * @param 	array 	$group 			Rating Group
	 * @param 	string 	$content 		Existing Comment
	 * @return 	string 					Average Rating HTML with Comment
	 */
	public function build_comment_rating_html($post_id, $comment_id, $group, $content = '')
	{

		// Set key to get display settings from
		$setting_group = 'ratingOutputComments';

		// Get rating data
		$comment = get_comment($comment_id);
		$ratings = get_comment_meta($comment_id, 'crfp', true);
		$averageRating = get_comment_meta($comment_id, 'crfp-average-rating', true);

		// Bail if no rating was left on this comment
		if (!is_array($ratings)) {
			return $content;
		}

		// Bail if output is set to never display
		if ($group[$setting_group]['enabled'] == 0) {
			return $content;
		}

		// Bail if output is conditional on ratings existing
		if ($group[$setting_group]['enabled'] == 1) {
			// If no ratings, bail
			if ($averageRating == 0) {
				return $content;
			}

			// If ratings, check they are for fields in this group
			$ratingsForGroupFields = false;
			foreach ($group['fields'] as $field) {
				if (isset($ratings[$field['fieldID']])) {
					$ratingsForGroupFields = true;
					break;
				}
			}
			if (!$ratingsForGroupFields) {
				return $content;
			}
		}

		// If we're in the WordPress Administration interface, force some display settings
		// so that stars render correctly in the Comments WP_List_Table
		if (is_admin()) {
			$group['css']['starSize'] = 16;
		}

		// Start Display
		$html = '';

		// Display Average       
		if ($group[$setting_group]['average']) {
			$html .= '
	        	<div class="rating-container crfp-group-' . $group['groupID'] . ' crfp-average-rating">';

			// Average Label Before Rating
			if (!empty($group[$setting_group]['averageLabel'])) {
				if (!isset($group[$setting_group]['averageLabelPosition']) || empty($group[$setting_group]['averageLabelPosition'])) {
					$html .= '<span class="label">
		        		' . $group[$setting_group]['averageLabel'] . '
		        	</span>';
				}
			}

			$html .= '
				<span class="' . ((($group[$setting_group]['enabled'] == 2 && $averageRating == 0) || $group[$setting_group]['style'] == 'grey') ? 'rating-always-on' : 'rating-filled-only') . '">
			    	<span class="crfp-rating crfp-rating-' . str_replace('.', '-', $averageRating) . '" style="width:' . ($averageRating * $group['css']['starSize']) . 'px">
			    		' . $averageRating . ' Display Average
			    	</span>
				</span>';

			// Show Rating Number
			$rating_number = '';
			switch ($group[$setting_group]['showRatingNumber']) {

					/**
				 * Percentage
				 */
				case 2:
					$rating_number = '<span class="crfp-rating-number-percentage">' . (($averageRating / $group['ratingInput']['maxRating']) * 100) . '%</span>';
					break;

					/**
					 * Number
					 */
				case 1:
					$rating_number = '<span class="crfp-rating-number">' . $averageRating . '</span>';
					break;
			}
			$rating_number = apply_filters('comment_rating_field_pro_rating_output_build_comment_rating_html_show_rating_number_average', $rating_number, $group, $averageRating);
			$html .= $rating_number;

			// Average Label After Rating
			if (!empty($group[$setting_group]['averageLabel'])) {
				if (isset($group[$setting_group]['averageLabelPosition']) && $group[$setting_group]['averageLabelPosition'] == 'after') {
					$html .= '<span class="label">
		        		' . $group[$setting_group]['averageLabel'] . '
		        	</span>';
				}
			}

			// Close average
			$html .= '</div>';
		}

		// Display Breakdown
		if ($group[$setting_group]['showBreakdown']) {
			// Iterate through fields
			foreach ($group['fields'] as $field) {
				// Rating for Field
				if (!isset($ratings[$field['fieldID']])) {
					$ratings[$field['fieldID']] = 0;
				}

				// Field
				$html .= '
				<div class="rating-container crfp-group-' . $group['groupID'] . ' crfp-rating-breakdown">
					<span class="label">' . $field['label'] . '</span>
					<span class="' . ((($group[$setting_group]['enabled'] == 2 && $ratings[$field['fieldID']] == 0) || $group[$setting_group]['style'] == 'grey') ? 'rating-always-on' : 'rating-filled-only') . '">
				    	<span class="crfp-rating crfp-rating-' . str_replace('.', '-', $ratings[$field['fieldID']]) . '" style="width:' . ($ratings[$field['fieldID']] * $group['css']['starSize']) . 'px">
				    		' . $ratings[$field['fieldID']] . ' Display Breakdown
				    	</span>
				   	</span>';

				// Show Rating Number
				$rating_number = '';
				switch ($group[$setting_group]['showRatingNumber']) {

						/**
					 * Percentage
					 */
					case 2:
						$rating_number = '<span class="crfp-rating-number-percentage">' . (($ratings[$field['fieldID']] / $group['ratingInput']['maxRating']) * 100) . '%</span>';
						break;

						/**
						 * Number
						 */
					case 1:
						$rating_number = '<span class="crfp-rating-number">' . $ratings[$field['fieldID']] . '</span>';
						break;
				}
				$rating_number = apply_filters('comment_rating_field_pro_rating_output_build_comment_rating_html_show_rating_number_breakdown', $rating_number, $group, $ratings[$field['fieldID']], $field);
				$html .= $rating_number;

				// Close field
				$html .= '</div>';
			}
		}

		// Markup the comment, unless markup is disabled in the settings
		$schema_disable_comment_text = Comment_Rating_Field_Pro_Settings::get_instance()->get_setting('schema_disable_comment_text');
		if (!$schema_disable_comment_text) {
			$content = '
	        <div class="rating-container">
	        	<div class="crfp-rating-text" style="white-space: pre-line;">  
					<span class="crfp-custom-inline-block crfp-rating crfp-rating-' . str_replace('.', '-', $averageRating) . '"  style="width:' . ($averageRating * $group['css']['starSize']) . 'px">
			    		' . $averageRating . ' Display Average
			    	</span> ' . $content . '
				</div>
	        </div>';
		}

		// Filter comment
		$content = apply_filters('comment_rating_field_pro_rating_output_build_comment_rating_html_comment_text', $content, $comment);

		// Filter HTML
		$html = apply_filters('crfp_display_comment_rating', $html);

		// Strip newlines from $html, as WordPress will convert these to <br> in comments
		$html = str_replace(array("\r", "\n"), '', $html);
		$content = str_replace(array("\r", "\n"), '', $content);

		// Append average rating before or after content
		switch ($group[$setting_group]['position']) {

				/**
			 * Above
			 */
			case 'above':
				return $html . $content;
				break;

				/**
				 * Below
				 */
			case '':
			default:
				// 				return $content . $html;
				return $content;
				break;
		}
	}

	/**
	 * Returns the average rating RSS markup for individual comments
	 *
	 * @since 	3.3.5
	 *
	 * @param 	int 	$post_id 		Post ID
	 * @param 	int 	$comment_id 	Comment ID
	 * @param 	array 	$group 			Rating Group
	 * @param 	string 	$content 		Existing Comment
	 * @return 	string 					Average Rating HTML with Comment
	 */
	public function build_comment_rating_rss($post_id, $comment_id, $group, $content = '')
	{

		// Set key to get display settings from
		$setting_group = 'ratingOutputRSSComments';

		// Get rating data
		$comment = get_comment($comment_id);
		$ratings = get_comment_meta($comment_id, 'crfp', true);
		$averageRating = get_comment_meta($comment_id, 'crfp-average-rating', true);

		// Bail if no rating was left on this comment
		if (!is_array($ratings)) {
			return $content;
		}

		// Bail if output is set to never display
		if ($group[$setting_group]['enabled'] == 0) {
			return $content;
		}

		// Bail if output is conditional on ratings existing
		if ($group[$setting_group]['enabled'] == 1) {
			// If no ratings, bail
			if ($averageRating == 0) {
				return $content;
			}

			// If ratings, check they are for fields in this group
			$ratingsForGroupFields = false;
			foreach ($group['fields'] as $field) {
				if (isset($ratings[$field['fieldID']])) {
					$ratingsForGroupFields = true;
					break;
				}
			}
			if (!$ratingsForGroupFields) {
				return $content;
			}
		}

		// Start Display
		$rss = "\n";

		// Display Average       
		if ($group[$setting_group]['average']) {
			$rss .= $group[$setting_group]['averageLabel'] . ' ' . $averageRating . '/' . $group['ratingInput']['maxRating'] . "\n";
		}

		// Display Breakdown
		if ($group[$setting_group]['showBreakdown']) {
			// Iterate through fields
			foreach ($group['fields'] as $field) {
				// Rating for Field
				if (!isset($ratings[$field['fieldID']])) {
					$ratings[$field['fieldID']] = 0;
				}

				// Field
				$rss .= $field['label'] . ' ' . $ratings[$field['fieldID']] . '/' . $group['ratingInput']['maxRating'] . "\n";
			}
		}

		// Apply filters
		$rss = apply_filters('comment_rating_field_pro_rating_output_build_comment_rating_rss', $rss, $group);

		// Append average rating before or after content
		switch ($group[$setting_group]['position']) {

				/**
			 * Above
			 */
			case 'above':
				return $rss . $content;
				break;

				/**
				 * Below
				 */
			case '':
			default:
				// 				return $content . $rss;
				return $rss . $content;
				break;
		}
	}

	/**
	 * Main function to display rating fields on a comments form
	 *
	 * Called by:
	 * - add_action. $html will be an array of fields
	 * - add_filter('comment_form_field_comment'), which sends us the comment form field HTML markup, so we must return this too.
	 *
	 * @since 	3.2.0
	 *
	 * @param 	mixed 	$html 	Array of fields | HTML markup
	 * @return 	mixed 			Array of fields | HTML markup
	 */
	public function display_rating_fields($comment_field_html = '')
	{

		// Check if the group is set to limit by role, and if so whether the user can post a rating
		// We do this check here so that developers using the display_rating_field() function honor this group setting
		$user_can_comment = Review_Comment_Integration_Pro_Groups::get_instance()->user_can_comment($this->group);
		if (!$user_can_comment) {
			return $comment_field_html;
		}

		// Get markup and apply filters to it
		$html = $this->build_comment_form_html($this->group);
		$html = apply_filters('crfp_display_rating_field', $html, $this->group);

		// If $comment_field_html is a non-empty string, then this is called using add_filter, so we always want
		// to return the comment field first, then the rating field.
		// Otherwise, OUTPUT the rating field.
		if (isset($comment_field_html) && !is_array($comment_field_html) && !empty($comment_field_html)) {
			// Return comment fields and our HTML
			return $comment_field_html . $html;
		} else {
			// Just output HTML
			echo $html;
		}
	}

	/**
	 * Main function to create comment rating inputs.
	 *
	 * When a $comment_id is specified (i.e. when editing a rating), rating fields will have their values
	 * defined based on the comment's rating.
	 *
	 * @since 	3.2.0
	 *
	 * @param 	array 	$group 			Field Group
	 * @param 	mixed 	$comment_id 	Comment ID (optional)
	 * @return 	string 					HTML Form Markup
	 */
	public function build_comment_form_html($group, $comment_id = false)
	{

		// If a Comment ID is specified, get its ratings now
		$ratings = get_comment_meta($comment_id, 'crfp', true);

		// Output rating fields
		$html = '';
		foreach ($group['fields'] as $key => $field) {
			// Define rating value, depending on whether an existing rating value has been defined or not
			$value = 0;
			if (is_array($ratings) && isset($ratings[$field['fieldID']])) {
				$value = $ratings[$field['fieldID']];
			}

			$html .= '<p class="crfp-field crfp-group-' . $group['groupID'] . '" data-required="' . $field['required'] . '" data-required-text="' . $field['required_text'] . '" data-cancel-text="' . $field['cancel_text'] . '">
		        <label for="rating-star-' . $field['fieldID'] . '">' . $field['label'] . '</label>';

			if ($group['ratingInput']['enableHalfRatings']) {
				for ($i = 0.5; $i <= $group['ratingInput']['maxRating']; $i += 0.5) {
					$html .= '<input name="rating-star-' . $field['fieldID'] . '" type="radio" class="star' . ($field['required'] ? ' required' : '') . '" value="' . ((string) $i) . '"' . checked($value, ((string) $i), false) . ' />';
				}
			} else {
				for ($i = 1; $i <= $group['ratingInput']['maxRating']; $i++) {
					$html .= '<input name="rating-star-' . $field['fieldID'] . '" type="radio" class="star' . ($field['required'] ? ' required' : '') . '" value="' . ((string) $i) . '"' . checked($value, ((string) $i), false) . ' />';
				}
			}

			$html .= '<input type="hidden" name="crfp-rating[' . $field['fieldID'] . ']" value="' . $value . '" class="crfp-rating-hidden" data-field-id="' . $field['fieldID'] . '" />
		    </p>';
		}

		return $html;
	}

	/**
	 * Returns the Total Number of Ratings left for the given Post ID
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 	Post ID
	 * @return 	int 				Number of Ratings
	 */
	public function get_post_total_ratings($post_id)
	{

		// Get data
		$result = get_post_meta($post_id, 'crfp-total-ratings', true);

		// If rating data is empty, set it to zero
		if (empty($result) || !$result) {
			$result = 0;
		}

		// Filter
		$result = apply_filters('comment_rating_field_pro_rating_output_get_post_total_ratings', $result, $post_id);

		// Return
		return $result;
	}

	/**
	 * Returns the Average Rating for the given Post ID
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 	Post ID
	 * @return 	int 				Number of Ratings
	 */
	public function get_post_average_rating($post_id)
	{

		// Get data
		$result = get_post_meta($post_id, 'crfp-average-rating', true);

		// If rating data is empty, set it to zero
		if (empty($result) || !$result) {
			$result = 0;
		}

		// Filter
		$result = apply_filters('comment_rating_field_pro_rating_output_get_post_average_rating', $result, $post_id);

		// Return
		return $result;
	}

	/**
	 * Returns the sum of all ratings for each available Rating, for the given Post ID
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 	Post ID
	 * @return 	array 				Sum of all ratings for each available Rating
	 */
	public function get_post_totals($post_id)
	{

		// Get data
		$result = get_post_meta($post_id, 'crfp-totals', true);

		// If rating data is empty, set it to an array
		if (empty($result) || !$result) {
			$result = array();
		}

		// Filter
		$result = apply_filters('comment_rating_field_pro_rating_output_get_post_totals', $result, $post_id);

		// Return
		return $result;
	}

	/**
	 * Returns the Average Ratings for each Field for the given Post ID
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 	Post ID
	 * @return 	array 				Average Ratings
	 */
	public function get_post_averages($post_id)
	{

		// Get data
		$result = get_post_meta($post_id, 'crfp-averages', true);

		// If rating data is empty, set it to an array
		if (empty($result) || !$result) {
			$result = array();
		}

		// Filter
		$result = apply_filters('comment_rating_field_pro_rating_output_get_post_averages', $result, $post_id);

		// Return
		return $result;
	}

	/**
	 * Returns the Number of Ratings made for each available Rating, for the given Post ID
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 	Post ID
	 * @return 	array 				Number of Ratings for each available Rating (1, 2, 3 etc)
	 */
	public function get_post_rating_split($post_id)
	{

		// Get data
		$result = get_post_meta($post_id, 'crfp-rating-split', true);

		// If rating data is empty, set it to an array
		if (empty($result) || !$result) {
			$result = array();
		}

		// Filter
		$result = apply_filters('comment_rating_field_pro_rating_output_get_post_rating_split', $result, $post_id);

		// Return
		return $result;
	}

	/**
	 * Returns the Percentage of Ratings made for each available Rating, for the given Post ID
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 	Post ID
	 * @return 	array 				Percentage of Ratings for each available Rating (1, 2, 3 etc)
	 */
	public function get_post_rating_split_percentages($post_id)
	{

		// Get data
		$result = get_post_meta($post_id, 'crfp-rating-split-percentages', true);

		// If rating data is empty, set it to an array
		if (empty($result) || !$result) {
			$result = array();
		}

		// Filter
		$result = apply_filters('comment_rating_field_pro_rating_output_get_post_rating_split_percentages', $result, $post_id);

		// Return
		return $result;
	}

	/**
	 * Returns whether Rating Fields have been disabled for the individual Post
	 *
	 * @since 	3.5.0
	 *
	 * @param 	int 	$post_id 	Post ID
	 * @return 	bool 				Ratings Disabled for Post
	 */
	public function post_rating_fields_disabled($post_id)
	{

		return (bool) get_post_meta($post_id, 'crfp-disabled', true);
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
