<?php

/**
 * Administration class
 * 
 * @package Comment_Rating_Field_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class Comment_Rating_Field_Pro_Admin
{

    /**
     * Holds the class object.
     *
     * @since   3.2.6
     *
     * @var     object
     */
    public static $instance;

    /**
     * Holds the base class object.
     *
     * @since   3.3.5
     *
     * @var     object
     */
    private $base;

    /**
     * Holds the success and error messages
     *
     * @since   3.4.9
     *
     * @var     array
     */
    public $notices = array(
        'success'   => array(),
        'error'     => array(),
    );

    /**
     * Constructor
     *
     * @since   3.2.0
     */
    public function __construct()
    {

        // Admin CSS, JS, Menu and Meta Boxes
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts_css'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    /**
     * Enqueues CSS and JS
     *
     * @since   3.2.0
     */
    public function admin_scripts_css()
    {

        // Get base instance
        $this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

        // Get current screen
        $screen = get_current_screen();

        // CSS - always load
        wp_enqueue_style($this->base->plugin->name . '-admin', $this->base->plugin->url . 'assets/css/admin.css', false, $this->base->plugin->version);
        wp_enqueue_style($this->base->plugin->name . '-frontend', $this->base->plugin->url . 'assets/css/frontend.css');

        // JS - always load
        wp_enqueue_script($this->base->plugin->name . '-admin', $this->base->plugin->url . 'assets/js/min/admin-min.js', array('jquery'), $this->base->plugin->version, true);
        wp_localize_script($this->base->plugin->name . '-admin', 'crfp', array(
            'ajax_url'            => admin_url('admin-ajax.php'),
            'delete_rating_field' => __('Are you sure you want to delete this rating field? This cannot be undone, and any existing ratings associated with this field will be lost.', $this->base->plugin->name),
            'delete_ratings'      => __('Are you sure you want to delete all ratings made for this Post? This includes ratings on Comments for this Post.  This action cannot be undone.', $this->base->plugin->name),
            'deleted_ratings'     => __('Ratings deleted successfully.', $this->base->plugin->name),
            'nonce'               => wp_create_nonce($this->base->plugin->name . '_nonce'),
            'post_id'             => ((isset($_GET['post']) && isset($_GET['action'])) ? $_GET['post'] : 0),
        ));

        // Plugin Admin
        if (strpos($screen->base, $this->base->plugin->name) !== false) {
            // These scripts are registered in _modules/dashboard/dashboard.php
            wp_enqueue_script('wpzinc-admin-conditional');
            wp_enqueue_script('wpzinc-admin');

            // JS
            wp_enqueue_script('wp-color-picker');

            // CSS
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style($this->base->plugin->name . '-admin', $this->base->plugin->url . 'assets/css/admin.css', false, $this->base->plugin->version);
        }

        // Comments List
        // (Yes, the edit-comments is correct here!)
        if (strpos($screen->base, 'edit-comments') !== false) {
            wp_enqueue_style($this->base->plugin->name, $this->base->plugin->url . 'assets/css/frontend.css');
        }

        // Edit Comment
        if (strpos($screen->base, 'comment') !== false) {
            // JS
            wp_enqueue_script($this->base->plugin->name, $this->base->plugin->url . 'assets/js/min/frontend-min.js', array('jquery'), $this->base->plugin->version, true);
        }
    }

    /**
     * Add the Plugin to the WordPress Administration Menu
     *
     * @since   3.2.0
     */
    public function admin_menu()
    {

        // Get base instance
        $this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

        // Menus
        add_menu_page($this->base->plugin->displayName, $this->base->plugin->displayName, 'manage_options', $this->base->plugin->name . '-settings', array($this, 'fields_screen'), 'dashicons-star-filled');
        add_submenu_page($this->base->plugin->name . '-settings', __('Settings', $this->base->plugin->name), __('Settings', $this->base->plugin->name), 'manage_options', $this->base->plugin->name . '-settings', array($this, 'fields_screen'));
    }

    /**
     * Output the Field Groups Screen
     * Save POSTed data from the Administration Panel into a WordPress option
     *
     * @since   3.2.0
     */
    public function fields_screen()
    {

        // Save group
        $result = $this->save_group();
        if (is_wp_error($result)) {
            // Error - add to array of errors for output
            $this->notices['error'][] = $result;
        }
        if (is_numeric($result)) {
            $this->notices['success'][] = __('Field group saved successfully.', $this->base->plugin->name);
        }

        // Get group from DB
        $group = Review_Comment_Integration_Pro_Groups::get_instance()->get_by_id(1);

        // Get roles
        $roles = Comment_Rating_Field_Pro_Common::get_instance()->get_user_roles();

        // View
        $view = 'views/admin/groups-form.php';

        // Load View
        include_once($this->base->plugin->folder . $view);
    }

    /**
     * Save Group and associated Fields
     *
     * @since   3.2.0
     *
     * @return  mixed   Error String on error | Group ID on success
     */
    public function save_group()
    {

        // Check if a POST request was made
        if (!isset($_POST['submit'])) {
            return false;
        }

        // Run security checks
        // Missing nonce 
        if (!isset($_POST[$this->base->plugin->name . '_nonce'])) {
            return __('Nonce field is missing. Field Group NOT saved.', $this->base->plugin->name);
        }

        // Invalid nonce
        if (!wp_verify_nonce($_POST[$this->base->plugin->name . '_nonce'], 'save_group')) {
            return __('Invalid nonce specified. Field Group NOT saved.', $this->base->plugin->name);
        }

        // Build group from POST data
        $group = Review_Comment_Integration_Pro_Groups::get_instance()->map_post_data($_POST);

        // Save Group
        $id = ((isset($_REQUEST['id']) && !empty($_REQUEST['id'])) ? absint($_REQUEST['id']) : '');
        $id = Review_Comment_Integration_Pro_Groups::get_instance()->save($group, $id);

        // Bail if save group failed
        if (is_wp_error($id)) {
            return $id->get_error_message();
        }

        // Get group we just saved
        $existing_group = Review_Comment_Integration_Pro_Groups::get_instance()->get_by_id($id);

        // Build array of POSTed field IDs
        $fieldIDs = array();
        foreach ($group['fields'] as $field) {
            $fieldIDs[] = $field['fieldID'];
        }

        // Iterate through the existing group fields
        // Any fields which are in the existing group data 
        // but aren't in the POST data need to be deleted from the DB
        if (is_array($existing_group['fields']) && count($existing_group['fields']) > 0) {
            foreach ($existing_group['fields'] as $existing_group_field) {
                // Iterate through POST data to find field ID
                if (!in_array($existing_group_field['fieldID'], $fieldIDs)) {
                    Comment_Rating_Field_Pro_Fields::get_instance()->delete($existing_group_field['fieldID']);
                }
            }
        }

        // Save Fields
        foreach ($group['fields'] as $field) {
            $field['groupID'] = $id;
            $result = Comment_Rating_Field_Pro_Fields::get_instance()->save($field, $field['fieldID']);
            if (is_wp_error($result)) {
                return $result->get_error_message();
            }
        }

        // OK
        return $id;
    }

    /**
     * Adds the rating field, if required, to the comments form in the WordPress Admin
     *
     * @since   3.2.0
     *
     * @param   object  $comment    Comment
     */
    public function display_rating_fields($comment)
    {

        // Check if post can have ratings
        $group = $this->postCanHaveRating($comment->comment_post_ID);
        if (!$group) {
            _e('No Rating Fields apply to this Post.', $this->base->plugin->name);
            return;
        }

        // Get comment meta
        $ratings = get_comment_meta($comment->comment_ID, 'crfp', true);
        if (!is_array($ratings) || count($ratings) == 0) {
            _e('No ratings were left by this user.', $this->base->plugin->name);
            return;
        }

        // Localize JS
        wp_localize_script($this->base->plugin->name, 'crfp', array(
            'disable_replies'       => $group['ratingInput']['disableReplies'],
            'enable_half_ratings'   => $group['ratingInput']['enableHalfRatings'],
        ));

        // Output fields
        // @TODO improve routine to copy frontend
        foreach ($group['fields'] as $key => $field) {
            // Get rating
            $rating = 0;
            foreach ($ratings as $fieldID => $rating) {
                if ($fieldID == $field['fieldID']) {
                    break; // $rating now set to what we want
                }
            }
?>
            <div class="option">
                <p class="crfp-field" data-required="<?php echo $field['required']; ?>" data-required-text="<?php echo $field['required_text']; ?>" data-cancel-text="<?php echo $field['cancel_text']; ?>">
                    <strong><?php echo $field['label']; ?></strong>

                    <?php
                    if ($group['ratingInput']['enableHalfRatings']) {
                    ?>
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="0.5" <?php echo (($rating == 0.5) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="1" <?php echo (($rating == 1) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="1.5" <?php echo (($rating == 1.5) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="2" <?php echo (($rating == 2) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="2.5" <?php echo (($rating == 2.5) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="3" <?php echo (($rating == 3) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="3.5" <?php echo (($rating == 3.5) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="4" <?php echo (($rating == 4) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="4.5" <?php echo (($rating == 4.5) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="5" <?php echo (($rating == 5) ? ' checked="checked"' : ''); ?> />
                    <?php
                    } else {
                    ?>
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="1" <?php echo (($rating == 1) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="2" <?php echo (($rating == 2) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="3" <?php echo (($rating == 3) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="4" <?php echo (($rating == 4) ? ' checked="checked"' : ''); ?> />
                        <input name="rating-star-<?php echo $field['fieldID']; ?>" type="radio" class="star" value="5" <?php echo (($rating == 5) ? ' checked="checked"' : ''); ?> />
                    <?php
                    }
                    ?>
                    <input type="hidden" name="crfp-rating[<?php echo $field['fieldID']; ?>]" value="<?php echo $rating; ?>" />
                </p>
            </div>
<?php
        }
    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since   3.2.6
     *
     * @return  object Class.
     */
    public static function get_instance()
    {

        if (!isset(self::$instance) && !(self::$instance instanceof self)) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}
