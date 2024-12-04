<?php

/**
 * Groups class
 * 
 * @package Comment_Rating_Field_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class Review_Comment_Integration_Pro_Groups
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
     * Primary SQL Table
     *
     * @since   3.2.0
     *
     * @var     string
     */
    public $table = 'crfp_groups';

    /**
     * Primary SQL Table Primary Key
     *
     * @since   3.2.0
     *
     * @var     string
     */
    public $key = 'groupID';

    /**
     * Serialized fields
     *
     * @since   3.2.0
     *
     * @var     array
     */
    public $serializedFields = array(
        'placementOptions',
        'css',
        'ratingInput',
        'ratingOutputExcerpt',
        'ratingOutputContent',
        'ratingOutputRSS',
        'ratingOutputComments',
        'ratingOutputRSSComments',
    );

    /**
     * Available Schemas
     *
     * @since   3.2.0
     *
     * @var     array
     */
    public $schemas = array(
        ''              => '(No Schema)',
        'CreativeWork'  => 'Creative Work',
        'Offer'         => 'Offer',
        'Organization'  => 'Organization',
        'Place'         => 'Place',
        'Product'       => 'Product',
        'Recipe'        => 'Recipe',
    );

    /**
     * Groups Cache
     *
     * @since   3.2.0
     *
     * @var     array
     */
    public $groups = array();

    /**
     * Helper method to check if the Groups table exists
     *
     * @since   3.5.1
     *
     * @return  bool    Database Table Exists
     */
    public function table_exists()
    {

        global $wpdb;

        $exists = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . $this->table . "'");

        return ($exists == $wpdb->prefix . $this->table);
    }

    /**
     * Activation routines for this Model
     *
     * @since   3.2.0
     *
     * @global  $wpdb   WordPress DB Object
     */
    public function activate()
    {

        global $wpdb;

        // Create database tables
        $wpdb->query("  CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . $this->table . " (
                            `groupID` int(10) NOT NULL AUTO_INCREMENT,
                            `name` varchar(200) NOT NULL,
                            `placementOptions` text NOT NULL,
                            `schema_type` varchar(200) NOT NULL,
                            `css` text NOT NULL,
                            `ratingInput` text NOT NULL,
                            `ratingOutputExcerpt` text NOT NULL,
                            `ratingOutputContent` text NOT NULL,
                            `ratingOutputRSS` text NOT NULL,
                            `ratingOutputComments` text NOT NULL,
                            `ratingOutputRSSComments` text NOT NULL,
                            PRIMARY KEY (`groupID`)
                        ) ENGINE=MyISAM
                        DEFAULT CHARSET=" . $wpdb->charset . "
                        AUTO_INCREMENT=1");

        if (defined('WP_DEBUG') && WP_DEBUG && !empty($wpdb->last_error)) {
            $wpdb->print_error();
        }
    }

    /**
     * Gets a record by its ID
     *
     * @since   3.2.0
     *
     * @param   int     $id     ID
     * @return  mixed           Record | false
     */
    public function get_by_id($id)
    {

        global $wpdb;

        // Get record
        $query = $wpdb->prepare(
            "   SELECT *
                                    FROM " . $wpdb->prefix . $this->table . "
                                    WHERE " . $this->key . " = %d
                                    LIMIT 1",
            $id
        );
        $results = $wpdb->get_results($query, ARRAY_A);

        // Check a record was found     
        if (!$results) {
            return false;
        }
        if (count($results) == 0) {
            return false;
        }

        // Map to single
        $result = $results[0];

        // Expand serialized data
        foreach ($this->serializedFields as $field) {
            $result[$field] = unserialize($result[$field]);
        }

        // Merge defaults
        $result = $this->merge_defaults($result);

        // Get fields
        $result['fields'] = Comment_Rating_Field_Pro_Fields::get_instance()->get_by('groupID', $id);

        // Return record
        return $result;
    }

    /**
     * Gets all results by the key/value pair
     *
     * @since   3.2.0
     *
     * @param   string  $field  Field Name
     * @param   string  $value  Field Value
     * @return  array           Records
     */
    public function get_by($field, $value)
    {

        global $wpdb;

        // Get record
        $query = $wpdb->prepare(
            "   SELECT *
                                    FROM " . $wpdb->prefix . $this->table . "
                                    WHERE " . $field . " = '%s'",
            $value
        );
        $results = $wpdb->get_results($query, ARRAY_A);

        // Check a record was found     
        if (!$results) {
            return false;
        }
        if (count($results) == 0) {
            return false;
        }

        // Map to single
        $result = $results[0];

        // Expand serialized data
        foreach ($this->serializedFields as $field) {
            $result[$field] = unserialize($result[$field]);
        }

        // Merge defaults
        $result = $this->merge_defaults($result);

        // Get fields
        $result['fields'] = Comment_Rating_Field_Pro_Fields::get_instance()->get_by('groupID', $id);

        // Return
        return $result;
    }


    /**
     * Returns an array of records
     *
     * @since   3.2.0
     * 
     * @param   string  $order_by           Order By Column (default: label, optional)
     * @param   string  $order              Order Direction (default: ASC, optional)
     * @param   int     $paged              Pagination (default: 1, optional)
     * @param   int     $results_per_page   Results per page (default: 10, optional)
     * @param   string  $search             Search Keywords (optional)
     * @return  array                       Records
     */
    public function get_all($order_by = 'label', $order = 'ASC', $paged = 1, $results_per_page = 10, $search = '')
    {

        global $wpdb;

        $get_all = (($paged == -1) ? true : false);

        // Search? 
        if (!empty($search)) {
            $query = $wpdb->prepare(
                "  SELECT *
                                        FROM " . $wpdb->prefix . $this->table . "
                                        WHERE name LIKE '%%%s%%'
                                        ORDER BY %s %s",
                $search,
                $order_by,
                $order
            );
        } else {
            $query = $wpdb->prepare(
                "  SELECT *
                                        FROM " . $wpdb->prefix . $this->table . "
                                        ORDER BY %s %s",
                $order_by,
                $order
            );
        }

        // Add Limit
        if (!$get_all) {
            $query = $query . $wpdb->prepare(
                " LIMIT %d, %d",
                (($paged - 1) * $results_per_page),
                $results_per_page
            );
        }

        // Get results
        $results = $wpdb->get_results($query, ARRAY_A);

        // Check a record was found     
        if (!$results) {
            return false;
        }
        if (count($results) == 0) {
            return false;
        }

        // Iterate through results
        foreach ($results as $key => $result) {

            // Expand serialized data
            foreach ($this->serializedFields as $field) {
                $results[$key][$field] = unserialize($result[$field]);
            }

            // Merge defaults
            $results[$key] = $this->merge_defaults($results[$key]);

            // Get fields
            $results[$key]['fields'] = Comment_Rating_Field_Pro_Fields::get_instance()->get_by('groupID', $result['groupID']);
        }

        return $results;
    }

    /**
     * Get the number of matching records
     *
     * @since 3.2.0
     *
     * @param   string $search  Search Keywords (optional)
     * @return  bool            Exists
     */
    public function total($search = '')
    {

        global $wpdb;

        // Prepare query
        if (!empty($search)) {
            $query = $wpdb->prepare(
                "  SELECT COUNT(" . $this->key . ")
                                        FROM " . $wpdb->prefix . $this->table . "
                                        WHERE name LIKE '%%%s%%'",
                $search
            );
        } else {
            $query = "  SELECT COUNT( " . $this->key . " )
                        FROM " . $wpdb->prefix . $this->table;
        }

        // Return count
        return (int) $wpdb->get_var($query);
    }

    /**
     * Maps $_POST data to match the DB table structure
     *
     * Called when a group is saved but failed, so $_POST data needs to be used
     * for the form fields
     *
     * @since   3.2.0
     *
     * @param   array   $data   POST Data
     * @return  array           Group Structure
     */
    public function map_post_data($data)
    {

        // Map fields
        if (isset($data['fields'])) {
            $fields = array();
            $count = 1;
            foreach ($data['fields']['label'] as $index => $label) {
                // Skip empty entry
                if (
                    empty($data['fields']['label'][$index]) &&
                    empty($data['fields']['required_text'][$index]) &&
                    empty($data['fields']['cancel_text'][$index])
                ) {
                    continue;
                }

                // Add field
                $fields[] = array(
                    'fieldID'       => (isset($data['fields']['fieldID'][$index]) ? $data['fields']['fieldID'][$index] : ''),
                    'label'         => $label,
                    'hierarchy'     => $count,
                    'required'      => $data['fields']['required'][$index],
                    'required_text' => $data['fields']['required_text'][$index],
                    'cancel_text'   => $data['fields']['cancel_text'][$index],
                );

                // Increment hierarchy count
                $count++;
            }
            $data['fields'] = $fields;
        }

        return $data;
    }

    /**
     * Returns an array of output group types (excerpt, RSS, content) for setting screens
     *
     * @since   3.2.7
     *
     * @return  array   Output Group Types
     */
    public function get_output_group_types()
    {

        $output_groups = array(
            'ratingOutputContent'   => array(
                'title' => __('Hiển thị đánh giá tổng: Trong nội dung bài viết', 'review-comment-integration-pro-plugin'),
                'type'  => __('Content', 'review-comment-integration-pro-plugin'),
            ),
        );

        // Allow devs to filter.
        $output_groups = apply_filters('Review_Comment_Integration_Pro_Groups_get_output_group_types', $output_groups);

        // Return.
        return $output_groups;
    }

    /**
     * Defines a default group structure when creating a new group
     * 
     * Also used to map all available keys and values to an existing group, so
     * the rest of the plugin can depend on keys and values always being present.
     *
     * @since   3.2.0
     *
     * @return  array   Group
     */
    public function get_defaults()
    {

        $defaults = array(
            'name' => '',
            'placementOptions' => '',
            'schema_type' => '',
            'css' => array(
                'starBackgroundColor' => '#cccccc',
                'starColor'         => '#f5c710',
                'starInputColor'    => '#b43600',
                'starSize'          => 16,
            ),
            'ratingInput' => array(
                'maxRating'         => 5,
                'precision'         => 0,
                'position'          => 'middle',
                'limitRating'       => 0,
                'disableReplies'    => 0,
                'enableHalfRatings' => 0,
                'limitByRole'       => array(),
            ),
            'ratingOutputExcerpt' => array(
                'enabled'               => 0,
                'position'              => '',
                'style'                 => '',
                'average'               => 0,
                'averageLabel'          => '',
                'averageLabelPosition'  => '',
                'totalRatings'          => '',
                'totalRatingsBefore'    => __('from', 'review-comment-integration-pro-plugin'),
                'totalRatingsAfter'     => __('ratings', 'review-comment-integration-pro-plugin'),
                'showBreakdown'         => 0,
                'showRatingNumber'      => 0,
                'filterComments'        => 0,
                'linkToComments'        => 0,
                'cssID'                 => '',
                'cssClass'              => '',
            ),
            'ratingOutputContent' => array(
                'enabled'               => 1,
                'position'              => '',
                'style'                 => '',
                'average'               => 1,
                'averageLabel'          => __('Rating: ', 'review-comment-integration-pro-plugin'),
                'averageLabelPosition'  => '',
                'totalRatings'          => '',
                'totalRatingsBefore'    => __('from', 'review-comment-integration-pro-plugin'),
                'totalRatingsAfter'     => __('ratings', 'review-comment-integration-pro-plugin'),
                'showBreakdown'         => 0,
                'showRatingNumber'      => 0,
                'filterComments'        => 0,
                'linkToComments'        => 0,
                'cssID'                 => '',
                'cssClass'              => '',
            ),
            'ratingOutputRSS' => array(
                'enabled'           => 0,
                'position'          => '',
                'totalRatings'      => '',
                'totalRatingsBefore' => __('from', 'review-comment-integration-pro-plugin'),
                'totalRatingsAfter' => __('ratings', 'review-comment-integration-pro-plugin'),
            ),
            'ratingOutputComments' => array(
                'enabled'               => 1,
                'position'              => '',
                'style'                 => '',
                'average'               => 1,
                'averageLabel'          => __('Rating: ', 'review-comment-integration-pro-plugin'),
                'averageLabelPosition'  => '',
                'showBreakdown'         => 0,
                'showRatingNumber'      => 0,
                'showSortingOptions'    => 0,
            ),
            'ratingOutputRSSComments' => array(
                'enabled'               => 0,
                'position'              => '',
                'average'               => 0,
                'averageLabel'          => '',
                'averageLabelPosition'  => '',
                'showBreakdown'         => 0,
            ),
            'fields' => array(),
        );

        // Allow devs to filter defaults.
        $defaults = apply_filters('Review_Comment_Integration_Pro_Groups_get_defaults', $defaults);

        // Return.
        return $defaults;
    }

    /**
     * Merges defaults with the given result
     *
     * @since   3.2.7
     *
     * @param   array   $result     Group
     * @return  array               Group
     */
    public function merge_defaults($group)
    {

        // Get defaults, and merge the results over them, so we always have the same array structure
        $defaults = $this->get_defaults();
        $keys = array('css', 'ratingInput', 'ratingOutputExcerpt', 'ratingOutputContent', 'ratingOutputRSS', 'ratingOutputComments', 'ratingOutputRSSComments');
        foreach ($keys as $key) {
            if (!is_array($group[$key])) {
                $group[$key] = $defaults[$key];
            }

            // Remove any settings in our group that should not be there.
            // This fixes various issues with options removed between plugin version upgrades.
            foreach ($group[$key] as $sub_key => $value) {
                if (!isset($defaults[$key][$sub_key])) {
                    unset($group[$key][$sub_key]);
                }
            }

            // Adds any settings to our group that are not there
            // This fixes various issues with options added between plugin version upgrades.
            foreach ($defaults[$key] as $sub_key => $value) {
                if (!array_key_exists($sub_key, $group[$key])) {
                    $group[$key][$sub_key] = $defaults[$key][$sub_key];
                }
            }
        }

        // Return.
        return $group;
    }

    /**
     * Gets the field group for the given Post ID
     *
     * @since   3.2.0
     *
     * @param   int     $post_id    Post ID
     * @return  bool                Can Have Rating
     */
    public function get_group_by_post_id($post_id)
    {

        // If no groups are stored in this class' $groups array, fetch them now
        if (empty($this->groups)) {
            $this->groups = $this->get_all('name', 'ASC', -1, 999);
        }

        // If there are still no groups, bail
        if (empty($this->groups)) {
            return false;
        }

        // Iterate through groups until a Post Type or Taxonomy match is found
        $post_type = get_post_type($post_id);
        foreach ($this->groups as $group) {
            // If no placement options exist, continue
            if (!isset($group['placementOptions']) || empty($group['placementOptions']) || count($group['placementOptions']) == 0) {
                continue;
            }

            if (isset($group['placementOptions']['type']) && is_array($group['placementOptions']['type'])) {
                foreach ($group['placementOptions']['type'] as $type => $enabled) {
                    if ($type == $post_type) {
                        // Post Type Match - return group
                        return $group;
                    }
                }
            }

            // Taxonomies
            if (isset($group['placementOptions']['tax']) && is_array($group['placementOptions']['tax'])) {
                foreach ($group['placementOptions']['tax'] as $tax => $term_ids) {
                    // Get Post Terms + build array of term IDs
                    $post_term_ids = array();
                    $terms = wp_get_post_terms($post_id, $tax);
                    foreach ($terms as $key => $term) {
                        $post_term_ids[] = $term->term_id;
                    }

                    // Iterate through group's term IDs to see if one of them match the Post term ID
                    foreach ($term_ids as $term_id => $intVal) {
                        if (in_array($term_id, $post_term_ids)) {
                            return $group;
                        }
                    }
                }
            }
        }
    }

    /**
     * Adds or edits a record, based on the given data array.
     *
     * @since   3.2.0
     * 
     * @param   array  $data    Array of data to save
     * @param   int    $id      ID (if set, edits the existing record)
     * @return  mixed           object  ID or WP_Error
     */
    public function save($data, $id = '')
    {

        global $wpdb;

        // Check for required data fields
        if (empty($data['name'])) {
            return new WP_Error('validation_error', __('Please complete the name field.', 'review-comment-integration-pro-plugin'));
        }
        if (empty($data['placementOptions'])) {
            return new WP_Error('validation_error', __('Please choose at least one placement option.', 'review-comment-integration-pro-plugin'));
        }

        if (!empty($id)) {
            // Editing an existing record
            // Build query
            $query = array();
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'name':
                    case 'schema_type':
                        // String
                        $query[] = $key . " = '" . htmlentities($value, ENT_QUOTES, 'UTF-8') . "'";
                        break;

                    case 'placementOptions':
                    case 'css':
                    case 'ratingInput':
                    case 'ratingOutputExcerpt':
                    case 'ratingOutputContent':
                    case 'ratingOutputRSS':
                    case 'ratingOutputComments':
                    case 'ratingOutputRSSComments':
                        // Serialize
                        $query[] = $key . " = '" . (trim($data[$key] != '') ? serialize($data[$key]) : '') . "'";
                        break;

                    default:
                        // Ignore anything else
                        break;
                }
            }

            // Prepare query to an SQL string
            $query = $wpdb->prepare(
                "   UPDATE " . $wpdb->prefix . $this->table . "
                                        SET " . implode(',', $query) . "
                                        WHERE " . $this->key . " = %s",
                $id
            );

            // Run query
            $result = $wpdb->query($query);

            // Check query was successful
            if ($result === FALSE) {
                return new WP_Error('db_query_error', __('Rating field could not be edited in the database. DB said: ' . $wpdb->last_error), $wpdb->last_error);
            }

            // Request that the user review the plugin. Notification displayed later,
            // can be called multiple times and won't re-display the notification if dismissed.
            $this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());
            $this->base->dashboard->request_review();

            // Success!
            return $id;
        } else {
            // Adding a new record  
            $query = $wpdb->prepare(
                "   INSERT INTO " . $wpdb->prefix . $this->table . " (name, placementOptions, schema_type, css, ratingInput, ratingOutputExcerpt, ratingOutputContent, ratingOutputRSS, ratingOutputComments, ratingOutputRSSComments)
                                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                $data['name'],
                serialize($data['placementOptions']),
                $data['schema_type'],
                serialize($data['css']),
                serialize($data['ratingInput']),
                serialize($data['ratingOutputExcerpt']),
                serialize($data['ratingOutputContent']),
                serialize($data['ratingOutputRSS']),
                serialize($data['ratingOutputComments']),
                serialize($data['ratingOutputRSSComments'])
            );

            // Run query
            $result = $wpdb->query($query);

            // Check query was successful
            if ($result === FALSE) {
                return new WP_Error('db_query_error', __('Rating field could not be added to the database. DB said: ' . $wpdb->last_error), $wpdb->last_error);
            }

            // Get Group ID now
            // If we do this after the review request, we get an ID for the options table record
            $group_id = $wpdb->insert_id;

            // Request that the user review the plugin. Notification displayed later,
            // can be called multiple times and won't re-display the notification if dismissed.
            $this->base = (class_exists('Comment_Rating_Field_Pro') ? Comment_Rating_Field_Pro::get_instance() : ReviewCommentIntegrationPlugin::get_instance());
            $this->base->dashboard->request_review();

            // Return Group ID
            return $group_id;
        }
    }

    /**
     * Deletes the record for the given primary key ID
     *
     * @since   3.2.0
     * 
     * @param   mixed   $data   Single ID or array of IDs
     * @return  bool            Success
     */
    public function delete($data)
    {

        global $wpdb;

        // Run query
        if (is_array($data)) {
            $query = "  DELETE FROM " . $wpdb->prefix . $this->table . "
                        WHERE " . $this->key . " IN (" . implode(',', $data) . ")";
        } else {
            $query = $wpdb->prepare(
                "   DELETE FROM " . $wpdb->prefix . $this->table . "
                                        WHERE " . $this->key . " = %s
                                        LIMIT 1",
                $data
            );
        }
        $result = $wpdb->query($query);

        // Check query was successful
        if ($result === FALSE) {
            return new WP_Error('db_query_error', __('Record(s) could not be deleted from the database. DB said: ' . $wpdb->last_error), $wpdb->last_error);
        }

        return true;
    }

    /**
     * Changes the given field's old value to a new value
     *
     * @since   3.2.0
     *
     * @param   string  $field      Field
     * @param   string  $old_value  Old Value
     * @param   string  $new_value  New Value
     * @return  bool                Success
     */
    public function change($field, $old_value, $new_value)
    {

        global $wpdb;

        // Prepare query to an SQL string
        $query = $wpdb->prepare(
            "  UPDATE " . $wpdb->prefix . $this->table . "
                                    SET " . $field . " = %s
                                    WHERE " . $field . " = %s",
            $new_value,
            $old_value
        );
        $result = $wpdb->query($query);

        // Check query was successful
        if ($result === FALSE) {
            return new WP_Error('db_query_error', __('Record\'s field could not be changed in the database. DB said: ' . $wpdb->last_error), $wpdb->last_error);
        }

        return true;
    }

    /**
     * Checks if the logged in User can add a review for the given Group ID
     *
     * @since   3.5.0
     *
     * @param   array     $group    Group
     * @return  bool                Can Comment
     */
    public function user_can_comment($group)
    {

        // Can comment if there is no limit by role
        if (!isset($group['ratingInput']['limitByRole'])) {
            return true;
        }
        if (empty($group['ratingInput']['limitByRole'])) {
            return true;
        }

        // Cannot comment if not logged in
        $user = wp_get_current_user();
        if (!$user) {
            return false;
        }

        // Cannot comment if the user does not have a role
        if (!isset($user->roles[0])) {
            return false;
        }

        // If user role is in settings, can comment
        return in_array($user->roles[0], $group['ratingInput']['limitByRole']);
    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since   3.2.6
     *
     * @return  object  Class.
     */
    public static function get_instance()
    {

        if (!isset(self::$instance) && !(self::$instance instanceof self)) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}
