<?php

/**
 * Common class
 * 
 * @package Comment_Rating_Field_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class Comment_Rating_Field_Pro_Common
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
     * Constructor
     *
     * @since   3.2.3
     */
    public function __construct()
    {

        // Localization
        add_action('plugins_loaded', array($this, 'load_language_files'));
    }

    /**
     * Loads plugin textdomain
     *
     * @since   3.2.0
     */
    public function load_language_files()
    {

        load_plugin_textdomain('review-comment-integration-pro-plugin', false, 'review-comment-integration-pro-plugin/languages/');
    }

    /**
     * Helper method to retrieve public Post Types
     *
     * @since   3.2.0
     *
     * @return  array   Public Post Types
     */
    public function get_post_types()
    {

        // Get public Post Types
        $types = get_post_types(array(
            'public' => true,
        ), 'objects');

        // Filter out excluded post types
        $excluded_types = $this->get_excluded_post_types();
        if (is_array($excluded_types)) {
            foreach ($excluded_types as $excluded_type) {
                unset($types[$excluded_type]);
            }
        }

        // Remove any post types which don't have comments enabled
        foreach ($types as $key => $type) {
            if (!post_type_supports($type->name, 'comments')) {
                unset($types[$key]);
            }
        }

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_post_types', $types);
    }

    /**
     * Helper method to retrieve excluded Post Types
     *
     * @since   3.2.0
     *
     * @return  array   Excluded Post Types
     */
    public function get_excluded_post_types()
    {

        // Get excluded Post Types
        $types = array(
            'revision',
            'nav_menu_item',
        );

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_excluded_post_types', $types);
    }

    /**
     * Helper method to retrieve all Taxonomies
     *
     * @since   3.2.0
     *
     * @param   string  $post_type  Post Type
     * @return  array               Taxonomies
     */
    public function get_taxonomies()
    {

        // Get all taxonomies
        $taxonomies = get_taxonomies();

        // Get information for each taxonomy
        foreach ($taxonomies as $index => $taxonomy) {
            $taxonomies[$index] = get_taxonomy($taxonomy);
        }

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_taxonomies', $taxonomies);
    }

    /**
     * Helper method to retrieve Taxonomies for the given Post Type
     *
     * @since   3.2.0
     *
     * @param   string  $post_type  Post Type
     * @return  array               Taxonomies
     */
    public function get_post_type_taxonomies($post_type = '')
    {

        // Get Post Type Taxonomies
        $taxonomies = get_object_taxonomies($post_type, 'objects');

        // Filter out excluded taxonomies
        $excluded_types = $this->get_excluded_taxonomies();
        if (is_array($excluded_types)) {
            foreach ($excluded_types as $excluded_type) {
                unset($taxonomies[$excluded_type]);
            }
        }

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_post_type_taxonomies', $taxonomies, $post_type);
    }

    /**
     * Helper method to retrieve excluded Taxonomies
     *
     * @since   3.2.0
     *
     * @return  array Taxonomies
     */
    public function get_excluded_taxonomies()
    {

        // Get excluded Taxonomies
        $taxonomies = array(
            'post_tag',
            'nav_menu',
            'link_category',
            'post_format',
        );

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_excluded_taxonomies', $taxonomies);
    }

    /**
     * Helper method to retrieve the available maximum rating options
     *
     * @since   3.3.5
     *
     * @return  array   Maximum Rating Options
     */
    public function get_max_rating_options()
    {

        // Build ratings
        $ratings = array();
        for ($i = 3; $i <= 10; $i++) {
            $ratings[$i] = sprintf(__('%s Stars', 'review-comment-integration-pro-plugin'), $i);
        }

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_max_rating_options', $ratings);
    }

    /**
     * Helper method to retrieve the available precision options
     *
     * @since   3.5.1
     *
     * @return  array   Maximum Rating Options
     */
    public function get_precision_options()
    {

        // Build precision options
        $precision = array();
        for ($i = 0; $i <= 2; $i++) {
            $precision[$i] = sprintf(__('%s Decimal Places', 'review-comment-integration-pro-plugin'), $i);
        }

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_precision_options', $precision);
    }

    /**
     * Helper method to return the IP address of the current user
     *
     * Checks various $_SERVER keys to try and get the most accurate result
     *
     * @since   3.2.0
     *
     * @return  string  IP Address
     */
    public function get_user_ip_address()
    {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_user_ip_address', $ip, $_SERVER);
    }

    /**
     * Helper method to retrieve all WordPress Roles
     *
     * @since   3.5.0
     *
     * @return  array   Roles
     */
    public function get_user_roles()
    {

        // Define roles
        $roles = get_editable_roles();

        // Remove excluded roles
        $excluded_roles = $this->get_excluded_user_roles();
        foreach ($roles as $role_name => $role) {
            if (in_array($role_name, $excluded_roles)) {
                unset($roles[$role_name]);
            }
        }

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_user_roles', $roles);
    }

    /**
     * Helper method to retrieve all excluded WordPress Roles
     *
     * @since   3.5.0
     *
     * @return  array   Excluded Roles
     */
    public function get_excluded_user_roles()
    {

        // Return filtered results
        return apply_filters('comment_rating_field_pro_common_get_excluded_user_roles', array());
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
