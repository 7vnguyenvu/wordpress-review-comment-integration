<?php

/**
 * Install class
 * 
 * @package Comment_Rating_Field_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class Comment_Rating_Field_Pro_Install
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
     * Activation routine
     * - Installs database tables as necessary
     *
     * @since    3.2.0
     *
     * @param    bool    $network_wide   Network Wide activation
     */
    static public function activate($network_wide = false)
    {

        // Check if we are on a multisite install, activating network wide, or a single install
        if (is_multisite() && $network_wide) {
            // Multisite network wide activation
            // Iterate through each blog in multisite, creating table
            $sites = wp_get_sites(array(
                'limit' => 0
            ));
            foreach ($site as $site) {
                switch_to_blog($site->blog_id);
                Comment_Rating_Field_Pro_Fields::get_instance()->activate();
                Review_Comment_Integration_Pro_Groups::get_instance()->activate();

                // If no Group exists, create a Group and Field now
                $total = Review_Comment_Integration_Pro_Groups::get_instance()->total();
                if (!$total) {
                    // Create Group and Field
                    $result = Comment_Rating_Field_Pro_Install::get_instance()->create_group_and_field(array(
                        'type' => array(
                            'post' => 1,
                        ),
                    ));

                    // Bail if an error occured
                    if (is_wp_error($result)) {
                        wp_die($result->get_error_message());
                        return;
                    }
                }

                restore_current_blog();
            }
        } else {
            // Single Site
            Comment_Rating_Field_Pro_Fields::get_instance()->activate();
            Review_Comment_Integration_Pro_Groups::get_instance()->activate();

            // If no Group exists, create a Group and Field now
            $total = Review_Comment_Integration_Pro_Groups::get_instance()->total();
            if (!$total) {
                // Create Group and Field
                $result = Comment_Rating_Field_Pro_Install::get_instance()->create_group_and_field(array(
                    'type' => array(
                        'post' => 1,
                    ),
                ));

                // Bail if an error occured
                if (is_wp_error($result)) {
                    wp_die($result->get_error_message());
                    return;
                }
            }
        }
    }

    /**
     * Activation routine when a WPMU site is activated
     * - Installs database tables as necessary
     *
     * We run this because a new WPMU site may be added after the plugin is activated
     * so will need necessary database tables
     *
     * @since   3.2.3
     */
    static public function activate_wpmu_site($blog_id)
    {

        switch_to_blog($blog_id);
        $this->activate();
        restore_current_blog();
    }

    /**
     * If settings exist in the options table, migrate them to the Fields and Group tables.
     *
     * If no settings exist in the options table, and there is no Field or Group defined,
     * add them now.
     *
     * @since   3.2.0
     */
    public function upgrade()
    {

        // Get base instance
        $this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());

        // Get current installed version number
        $installed_version = get_option($this->base->plugin->name . '-version'); // false | 1.1.7

        // If the version number matches the plugin version, bail
        if ($installed_version == $this->base->plugin->version) {
            return;
        }

        // If an option exists, containing settings, use those to migrate to Groups and Fields
        $options = get_option('review-comment-integration-plugin');
        if (!empty($options)) {
            // Build Placement Options
            $placement_options = array(
                'type' => array(),
            );
            foreach ($options['enabled'] as $post_type => $enabled) {
                if ($post_type == 'average') {
                    continue;
                }

                $placement_options['type'][$post_type] = 1;
            }

            // Create Group and Field
            $result = $this->create_group_and_field($placement_options);
            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
                return;
            }

            // Migrate Ratings
            $result = $this->migrate_ratings();
            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
                return;
            }
        }

        // If the Group or Field table does not exist, create them and populate with a Group and Field
        $group_table_exists = Review_Comment_Integration_Pro_Groups::get_instance()->table_exists();
        if (!$group_table_exists) {
            // Create Group and Field
            $result = $this->create_group_and_field(array(
                'type' => array(
                    'post' => 1,
                ),
            ));

            // Bail if an error occured
            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
                return;
            }

            // Migrate Ratings
            $result = $this->migrate_ratings();
            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
                return;
            }
        }

        // Delete Options, as they're no longer used in either Free or Pro
        delete_option('review-comment-integration-plugin');

        // Update the version number, so we don't run this routine until the next version is released
        update_option($this->base->plugin->name . '-version', $this->base->plugin->version);
    }

    /**
     * Creates the first Group and Field.
     *
     * Called when upgrading from Free --> Free 3.5.0+, or on a fresh Free or Pro install
     * that has no Group or Field
     *
     * @since   3.5.1
     *
     * @param   array   $placement_options      Placement Options
     * @return  mixed                           WP_Error | true
     */
    private function create_group_and_field($placement_options)
    {

        // Create DB Tables
        Comment_Rating_Field_Pro_Fields::get_instance()->activate();
        Review_Comment_Integration_Pro_Groups::get_instance()->activate();

        // Create Group
        $group_id = Review_Comment_Integration_Pro_Groups::get_instance()->save(array(
            'name'                  => __('Rating', 'review-comment-integration-plugin'),
            'placementOptions'      => $placement_options,
            'schema_type'           => '',
            'css'                   => '',
            'ratingInput'           => '',
            'ratingOutputExcerpt'   => '',
            'ratingOutputContent'   => array(
                'enabled'       => 1,
                'average'       => 1,
                'averageLabel'  => __('Rating: ', 'review-comment-integration-plugin'),
            ),
            'ratingOutputRSS'       => '',
            'ratingOutputComments'  => array(
                'enabled'       => 1,
                'average'       => 1,
                'averageLabel'  => __('Rating: ', 'review-comment-integration-plugin'),
            ),
            'ratingOutputRSSComments' => '',
        ));

        // Bail if an error occured
        if (is_wp_error($group_id)) {
            return $group_id;
        }

        // Create Field
        $field_id = Comment_Rating_Field_Pro_Fields::get_instance()->save(array(
            'groupID'       => $group_id,
            'hierarchy'     => 1,
            'label'         => __('Rating: ', 'review-comment-integration-plugin'),
            'required'      => 0,
            'required_text' => '',
            'cancel_text'   => '',
        ));

        // Bail if an error occured
        if (is_wp_error($field_id)) {
            return $field_id;
        }

        return true;
    }

    /**
     * Migrates ratings stored in the crfp-rating Comment Meta Key, into the new
     * Group + Field style
     *
     * @since   3.5.1
     *
     * @return  mixed   WP_Error | true
     */
    private function migrate_ratings()
    {

        // Get Comment Meta
        $comments = new WP_Comment_Query(array(
            'meta_query' => array(
                array(
                    'key'       => 'crfp-rating',
                    'compare'   => 'EXISTS',
                ),
            ),
            'fields'    => 'ids',
        ));

        // If no Comments have rating requiring migration, bail
        if (!isset($comments->comments) || count($comments->comments) == 0) {
            return true;
        }

        foreach ($comments->comments as $comment_id) {
            // Get rating
            $rating = get_comment_meta($comment_id, 'crfp-rating', true);

            // Store ratings
            update_comment_meta($comment_id, 'crfp', array(
                1 => $rating,
            ));

            // Store average rating
            update_comment_meta($comment_id, 'crfp-average-rating', $rating);

            // Delete existing Comment Meta
            delete_comment_meta($comment_id, 'crfp-rating');

            // Recalculate Post Rating
            $result = Comment_Rating_Field_Pro_Rating_Input::get_instance()->update_post_rating_by_comment_id($comment_id);
        }

        return true;
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
