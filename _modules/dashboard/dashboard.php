<?php
/**
 * Dashboard Widget
 * 
 * @package     Dashboard
 * @author      Dashboard
 * @version     1.0.0
 */
class WPZincDashboardWidget {  

    /**
     * Holds the plugin object
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $plugin; 

    /**
     * Holds the exact path to this file's folder
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $dashboard_folder;

    /**
     * Holds the exact URL to this file's folder
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $dashboard_url;  

    /**
     * Constructor
     *
     * @since 1.0
     *
     * @param object $plugin Plugin Object (name, displayName, version, folder, url)
     */
    public function __construct( $plugin ) {

        // Set class vars
        $this->plugin           = $plugin;
        $this->dashboard_folder = plugin_dir_path( __FILE__ );
        $this->dashboard_url    = plugin_dir_url( __FILE__ );

        // Admin CSS, JS and Menu
        add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_css' ) );
        add_action( str_replace( '-', '_', $this->plugin->name ) . '_admin_menu', array( $this, 'admin_menu' ), 99 );

        // Plugin Actions
        add_filter( 'plugin_action_links_' . $this->plugin->name . '/' . $this->plugin->name . '.php', array( $this, 'add_action_link' ), 10, 2 );

        // Reviews
        add_action( 'wp_ajax_' . str_replace( '-', '_', $this->plugin->name ) . '_dismiss_review', array( $this, 'dismiss_review' ) );
        add_action( 'admin_notices', array( $this, 'display_review_request' ) );

        // Export and Support
        add_action( 'plugins_loaded', array( $this, 'export' ) );
        add_action( 'plugins_loaded', array( $this, 'maybe_redirect' ) );

    }    

    /**
     * Adds the WP Zinc CSS class to the <body> tag when we're in the WordPress Admin interface
     * and viewing a Plugin Screen
     *
     * This allows us to then override some WordPress layout styling on e.g. #wpcontent, without
     * affecting other screens, Plugins etc.
     *
     * @since   1.0.0
     *
     * @param   string   $classes    CSS Classes
     * @return  string               CSS Classes
     */
    public function admin_body_class( $classes ) {

        // Define a list of strings that determine whether we're viewing a Plugin Screen
        $screens = array(
            $this->plugin->name,
        );

        // Allow the Plugin to add more strings to this
        $screens = apply_filters( 'wpzinc_admin_body_class', $screens, $classes );

        // Determine whether we're on a Plugin Screen
        $is_plugin_screen = $this->is_plugin_screen( $screens );

        // Bail if we're not a Plugin screen
        if ( ! $is_plugin_screen ) {
            return $classes;
        }

        // Add the wpzinc class and plugin name
        $classes  .= ' wpzinc ' . $this->plugin->name;

        // Return
        return trim( $classes );

    }

    /**
     * Determines whether we're viewing this Plugin's screen in the WordPress Administration
     * interface
     *
     * @since   1.0.0
     *
     * @param   array   $screens    Screens
     * @return  bool                Is Plugin Screen
     */
    private function is_plugin_screen( $screens ) {

        // Bail if the current screen can't be obtained
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }

        // Bail if no screen names were specified to search for
        if ( empty( $screens ) || count( $screens ) == 0 ) {
            return false;
        }

        // Get screen
        $screen = get_current_screen();

   
        // Itera
        foreach ( $screens as $screen_name ) {
            if ( strpos( $screen->id, $screen_name ) === false ) {
                continue;
            }

            // We're on a Plugin Screen
            return true;
        }

        // If here, we're not on a Plugin Screen
        return false;

    } 
    
    /**
     * Register JS scripts, which Plugins may optionally load via wp_enqueue_script()
     * Enqueues CSS
     *
     * @since   1.0.0
     */
    public function admin_scripts_css() {    

        // JS
        wp_register_script( 'wpzinc-admin-conditional', $this->dashboard_url . 'js/jquery.form-conditionals.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin-clipboard', $this->dashboard_url . 'js/clipboard.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin-inline-search', $this->dashboard_url . 'js/inline-search.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin-media-library', $this->dashboard_url . 'js/media-library.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin-modal', $this->dashboard_url . 'js/modal.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin-select2', $this->dashboard_url . 'js/select2.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin-tabs', $this->dashboard_url . 'js/tabs.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin-tags', $this->dashboard_url . 'js/tags.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'wpzinc-admin', $this->dashboard_url . 'js/min/admin-min.js', array( 'jquery' ), $this->plugin->version, true );
           
        // CSS
        wp_register_style( 'wpzinc-admin-select2', $this->dashboard_url . 'css/select2.css' ); 
        wp_enqueue_style( 'wpzinc-admin', $this->dashboard_url . 'css/admin.css' ); 

    }   

    /**
     * Add Import / Export + Support Menu Links to the WordPress Administration interface
     *
     * @since 1.0.0
     *
     * @param   string  $parent_slug   Parent Slug 
     */
    public function admin_menu( $parent_slug = '' ) {

        // If a parent slug is defined, attach the submenu items to that
        // Otherwise use the plugin's name
        $slug = ( ! empty( $parent_slug ) ? $parent_slug : $this->plugin->name );

        add_submenu_page( $slug, __( 'Import & Export', $this->plugin->name ), __( 'Import & Export', $this->plugin->name ), 'manage_options', $this->plugin->name . '-import-export', array( $this, 'import_export_screen' ) ); 
        add_submenu_page( $slug, __( 'Support', $this->plugin->name ), __( 'Support', $this->plugin->name ), 'manage_options', $this->plugin->name . '-support', array( $this, 'support_screen' ) );
    
    }

    /**
     * Adds Plugin Action Links to the Plugin when activated in the Plugins Screen
     *
     * @since   1.0.0
     *
     * @param   array   $links  Action Links
     * @param   string  $file   Plugin File
     * @return  array           Action Links
     */
    public function add_action_link( $links, $file ) {

        // Bail if the licensing class exists
        if ( class_exists( 'LicensingUpdateManager' ) ) {
            return $links;
        }

        // Add Links
        $links[] = '<a href="' . $this->get_upgrade_url( 'plugins' ) . '" target="_blank">' . __( 'Upgrade', $this->plugin->name ) . '</a>';

        // Return
        return $links;

    }

    /**
     * Displays a dismissible WordPress Administration notice requesting a review, if the main
     * plugin's key action has been completed.
     *
     * @since   1.0.0
     */
    public function display_review_request() {

        // If we're not an Admin user, bail
        if ( ! function_exists( 'current_user_can' ) ) {
            return;
        }
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // If the review request was dismissed by the user, bail.
        if ( $this->dismissed_review() ) {
            return;
        }

        // If no review request has been set by the plugin, bail.
        if ( ! $this->requested_review() ) {
            return;
        }

        // If here, display the request for a review
        include_once( $this->dashboard_folder . '/views/review-notice.php' );

    }

    /**
     * Flag to indicate whether a review has been requested.
     *
     * @since   1.0.0
     *
     * @return  bool    Review Requested
     */
    public function requested_review() {

        $time = get_option( $this->plugin->review_name . '-review-request' );
        if ( empty( $time ) ) {
            return false;
        }

        // Check the current date and time matches or is later than the above value
        $now = time();
        if ( $now >= ( $time + ( 3 * DAY_IN_SECONDS ) ) ) {
            return true;
        }

        // We're not yet ready to show this review
        return false;

    }

    /**
     * Requests a review notification, which is displayed on subsequent page loads.
     *
     * @since   1.0.0
     */
    public function request_review() {

        // If a review has already been requested, bail
        $time = get_option( $this->plugin->review_name . '-review-request' );
        if ( ! empty( $time ) ) {
            return;
        }

        // Request a review, setting the value to the date and time now.
        update_option( $this->plugin->review_name . '-review-request', time() );

    }

    /**
     * Flag to indicate whether a review request has been dismissed by the user.
     *
     * @since   1.0.0
     *
     * @return  bool    Review Dismissed
     */
    public function dismissed_review() {

        return get_option( $this->plugin->review_name . '-review-dismissed' );

    }

    /**
     * Dismisses the review notification, so it isn't displayed again.
     *
     * @since   1.0.0
     */
    public function dismiss_review() {

        update_option( $this->plugin->review_name . '-review-dismissed', 1 );

        // Send success response if called via AJAX
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            wp_send_json_success( 1 );
        }

    }

    /**
     * Returns the Upgrade URL for this Plugin.
     *
     * Adds Google Analytics UTM tracking, and optional coupon flag
     *
     * @since   1.0.0
     *
     * @param   string  $utm_content    UTM Content Value
     * @return  string                  Upgrade URL
     */
    public function get_upgrade_url( $utm_content = '' ) {

        // Build URL
        $url = $this->plugin->upgrade_url . '?utm_source=wordpress&utm_medium=link&utm_content=' . $utm_content . '&utm_campaign=general';

        // Return
        return $url;

    }

    /**
     * Import / Export Screen
     *
     * @since 1.0.0
     */
    public function import_export_screen() {

        // Import
        if ( isset( $_POST['submit'] ) ) {
            // Check nonce
            if ( ! isset( $_POST[ $this->plugin->name . '_nonce'] ) ) {
                // Missing nonce    
                $this->errorMessage = __( 'nonce field is missing. Settings NOT saved.', $this->plugin->name );
            } elseif ( ! wp_verify_nonce( $_POST[ $this->plugin->name . '_nonce' ], $this->plugin->name ) ) {
                // Invalid nonce
                $this->errorMessage = __( 'Invalid nonce specified. Settings NOT saved.', $this->plugin->name );
            } else {       
                // Import
                if ( ! is_array( $_FILES ) ) {
                    $this->errorMessage = __( 'No JSON file uploaded.', $this->plugin->name );
                } elseif ( $_FILES['import']['error'] != 0 ) {
                    $this->errorMessage = __( 'Error when uploading JSON file.', $this->plugin->name );
                } else {
                    $handle = fopen( $_FILES['import']['tmp_name'], 'r' );
                    $json = fread( $handle, $_FILES['import']['size'] );
                    fclose( $handle );
                    $json_arr = json_decode( $json, true );
                    
                    // Run import routine
                    $result = $this->import( $json_arr );
                    if ( ! $result ) {
                        $this->errorMessage = __( 'Supplied file is not a valid JSON settings file, or has become corrupt.', $this->plugin->name );
                    } else {
                        $this->message = __( 'Settings imported.', $this->plugin->name );
                    }   
                }
            }
        }
        
        // Output view
        include_once( $this->dashboard_folder . '/views/import-export.php' );

    }
    
    /**
     * Support Screen
     *
     * @since 1.0.0
     */
    public function support_screen() {   
        // We never reach here, as we redirect earlier in the process
    }
    
    /**
     * Imports the given JSON
     *
     * @since 1.0.0
     *
     * @param   array   $json_arr   JSON Array (settings|data keys)
     * @return  bool                Result
     */
    public function import( $json_arr ) {
        
        // Import Settings
        // Newer JSON exports store settings in the 'settings' array key
        // Older JSON exports store settings and nothing else
        $settings = ( ! array_key_exists( 'settings', $json_arr ) ? $json_arr : $json_arr['settings'] );
        if ( is_array( $settings ) ) {  
            if ( isset($this->plugin->settingsName ) ) {
                delete_option( $this->plugin->settingsName );
                update_option( $this->plugin->settingsName, $settings );    
            } else {
                delete_option( $this->plugin->name );
                update_option( $this->plugin->name, $settings );
            }
        }
        
        // Import Data
        if ( array_key_exists( 'data', $json_arr ) ) {
            // Data exists - fire action which main plugin hooks into to import data
            do_action( $this->plugin->name . '-import', $json_arr['data'] );
        }
        
        // Done
        return true;
        
    }
    
    /**
     * If we have requested the export JSON, force a file download
     *
     * @since 1.0.0
     */ 
    public function export() {

        // Check we are on the right page
        if ( ! isset( $_GET['page'] ) ) {
            return;
        }
        if ( $_GET['page'] != $this->plugin->name . '-import-export' ) {
            return;
        }
        if ( ! isset( $_GET['export'] ) ) {
            return;
        }
        if ( $_GET['export'] != 1 ) {
            return;
        }
        
        // Get settings
        $settings = get_option( $this->plugin->name );
        
        // If settings are false, we masy be using settingsName
        if ( ! $settings && isset( $this->plugin->settingsName ) ) {
            $settings = get_option( $this->plugin->settingsName );
        }
        
        // Get any other data from the main plugin
        // Main plugin can hook into this filter and return an array of data
        $data = array();
        $data = apply_filters( $this->plugin->name . '-export', $data );
        
        // Build final array
        $json_arr = array(
            'settings'  => $settings,
            'data'      => $data,
        );
        
        // Build JSON
        $json = json_encode( $json_arr );
        
        header( "Content-type: application/x-msdownload" );
        header( "Content-Disposition: attachment; filename=export.json" );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );
        echo $json;
        exit();

    }

    /**
     * If the Support or Upgrade menu item was clicked, redirect
     *
     * @since 3.0
     */
    public function maybe_redirect() {

        // Check we requested the support page
        if ( ! isset( $_GET['page'] ) ) {
            return;
        }
        
        // Redirect to Support
        if ( $_GET['page'] == $this->plugin->name . '-support' ) {
            wp_redirect( $this->plugin->support_url );
            die();
        }

        // Redirect to Upgrade
        if ( $_GET['page'] == $this->plugin->name . '-upgrade' ) {
            wp_redirect( $this->get_upgrade_url( 'menu' ) );
            die();
        }

    }

}