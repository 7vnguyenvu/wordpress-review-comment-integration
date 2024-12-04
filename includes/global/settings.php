<?php
/**
 * Settings class
 * 
 * @package   Comment_Rating_Field_Pro
 * @author    Tim Carr
 * @version   3.5.0
 */
class Comment_Rating_Field_Pro_Settings {

    /**
     * Holds the class object.
     *
     * @since   3.5.0
     *
     * @var     object
     */
    public static $instance;

    /**
     * The settings key prefix to use in the WordPress options table
     *
     * @since   3.5.0
     *
     * @var     string
     */
    public $key_prefix = 'crfp-settings';

    /**
     * Returns a setting from the Options table
     *
     * @since   3.5.0
     *
     * @param   string  $key    Setting Key
     * @return  mixed           Setting Value
     */ 
    public function get_setting( $key ) {

        // Get settings
        $settings = $this->get_settings();

        // Get setting
        $setting = ( isset( $settings[ $key ] ) ? $settings[ $key ] : '' );

        // Allow devs / addons to filter setting
        $setting = apply_filters( 'comment_rating_field_pro_settings_get_setting', $setting, $key );

        // Return
        return $setting;

    }

    /**
     * Returns all settings
     *
     * @since   3.5.0
     *
     * @return  array              Settings
     */
    public function get_settings() {

        // Get settings
        $settings = get_option( $this->key_prefix );

        // Get default settings
        $defaults = $this->get_default_settings();

        // If no settings exists, fallback to the defaults
        if ( ! $settings ) {
            $settings = $defaults;
        } else {
            // Iterate through the defaults, checking if the settings have the same key
            // If not, add the setting key with the default value
            // This ensures that on a Plugin upgrade where new defaults are introduced,
            // they are immediately available for use without the user needing to save their
            // settings.
            foreach ( $defaults as $default_key => $default_value ) {
                if ( ! isset( $settings[ $default_key ] ) ) {
                    $settings[ $default_key ] = $default_value;
                }
            }
        }

        // Stripslashes
        $settings = stripslashes_deep( $settings );

        // Filter settings
        $settings = apply_filters( 'comment_rating_field_pro_settings_get_settings', $settings );

        // Return
        return $settings;

    }

    /**
     * Saves a single setting for the given Key
     *
     * @since   3.5.0
     *
     * @param   string  $key    Setting Key
     * @param   mixed   $value  Setting Value
     * @return  bool            Success
     */
    public function update_setting( $key, $value ) {

        // Get settings
        $settings = $this->get_settings();

        // Filter setting
        $value = apply_filters( 'comment_rating_field_pro_settings_update_setting', $value, $key );

        // Update single setting
        $settings[ $key ] = $value;

        // Update settings
        return $this->update_settings( $settings );

    }

    /**
     * Saves all settings
     *
     * @since   3.5.0
     *
     * @param    array   $settings   Settings
     * @return   bool                Success
     */
    public function update_settings( $settings ) {

        // Allow devs / addons to filter settings
        $settings = apply_filters( 'comment_rating_field_pro_settings_update_settings', $settings );

        // Update settings
        update_option( $this->key_prefix, $settings );
        
        // update_option won't return true if no settings were changed; we can trust this operation works
        return true;

    }

    /**
     * Deletes a single setting for the given Key
     *
     * @since   3.5.0
     *
     * @param   string  $key    Key
     * @return  bool            Success
     */
    public function delete_setting( $key ) {

        // Get settings
        $settings = $this->get_settings();

        // Delete single setting
        if ( isset( $settings[ $key ] ) ) {
            unset( $settings[ $key ] );
        }

        // Allow devs / addons to filter settings
        $settings = apply_filters( 'comment_rating_field_pro_settings_delete_setting', $settings, $key );

        // Update settings
        return $this->update_settings( $settings );

    }

    /**
     * Deletes all settings
     *
     * @since   3.5.0
     *
     * @return  bool            Success
     */
    public function delete_settings() {

        // Delete settings
        delete_option( $this->key_prefix );

        // Allow devs / addons to run any other actions now
        do_action( 'comment_rating_field_pro_settings_delete_settings' );

        return true;

    }

    /**
     * Returns the default settings
     *
     * @since   3.5.0
     *
     * @return  array   Settings
     */
    private function get_default_settings() {

        $defaults = array(
            'sort_posts_page'               => false,
            'sort_post_type_archives'       => array(),
            'sort_taxonomy_archives'        => array(),
            'schema_disable_comment_text'   => false,
            'schema_disable_shortcode'      => false,
        );

        // Filter defaults
        $defaults = apply_filters( 'comment_rating_field_pro_get_default_settings', $defaults );

        return $defaults;

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since   3.5.0
     *
     * @return  object Class.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

}