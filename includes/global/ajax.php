<?php

/**
 * AJAX class
 * 
 * @package Comment_Rating_Field_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class Comment_Rating_Field_Pro_AJAX
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
     * @since   3.5.1
     *
     * @var     object
     */
    private $base;

    /**
     * Constructor
     *
     * @since   3.2.0
     */
    public function __construct()
    {

        // TinyMCE
        add_action('wp_ajax_comment_rating_field_pro_output_tinymce_modal', array($this, 'output_tinymce_modal'));
    }

    /**
     * Loads the view for the TinyMCE modal.
     *
     * @since   3.5.1
     */
    public function output_tinymce_modal()
    {

        // Get base instance
        $this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

        // Load View
        require_once($this->base->plugin->folder . '/views/admin/tinymce.php');
        die();
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
