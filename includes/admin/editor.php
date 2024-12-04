<?php

/**
 * Editor class
 * 
 * @package Comment_Rating_Field_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class Comment_Rating_Field_Pro_Editor
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
     * Holds the base object.
     *
     * @since   3.5.0
     *
     * @var     object
     */
    public $base;

    /**
     * Constructor
     *
     * @since   3.2.0
     */
    public function __construct()
    {

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'setup_tinymce_plugins'));
    }

    /**
     * Enqueues the Modal and QuickTags script for the Text Editor
     *
     * @since   1.5.2
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script('wpzinc-admin-modal');
    }

    /**
     * Setup calls to add a button and plugin to the Page Generator Pro WP_Editor
     *
     * @since   3.2.0
     */
    public function setup_tinymce_plugins()
    {

        // Check user has capabilites to edit posts or pages
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        // Check if rich editing is enabled for the user
        if (get_user_option('rich_editing') != 'true') {
            return;
        }

        // Add filters to register TinyMCE Plugins
        add_filter('mce_external_plugins', array($this, 'register_tinymce_plugins'));
        add_filter('mce_buttons', array($this, 'register_tinymce_buttons'));
    }

    /**
     * Register JS plugins for the TinyMCE Editor
     *
     * @since   3.2.0
     *
     * @param   array   $plugins    JS Plugins
     * @return  array 		        JS Plugins
     */
    public function register_tinymce_plugins($plugins)
    {

        // Get base instance
        $this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

        $plugins['crfp'] = $this->base->plugin->url . 'assets/js/min/editor_plugin-min.js';

        return $plugins;
    }

    /**
     * Registers buttons in the TinyMCE Editor
     *
     * @since   3.2.0
     *
     * @param   array   $buttons    Buttons
     * @return  array 		        Buttons
     */
    public function register_tinymce_buttons($buttons)
    {

        array_push($buttons, 'crfp');
        return $buttons;
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
