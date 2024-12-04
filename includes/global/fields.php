<?php
/**
 * Fields class
 * 
 * @package Comment_Rating_Field_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class Comment_Rating_Field_Pro_Fields {

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
    * @since    3.2.0
    */
    public $table = 'crfp_fields';
    
    /**
    * Primary SQL Table Primary Key
    *
    * @since    3.2.0
    */
    public $key = 'fieldID';

    /**
     * Activation routines for this Model
     *
     * @since    3.2.0
     *
     * @global   $wpdb   WordPress DB Object
     */
    public function activate() {

        global $wpdb;

        // Create database tables
        $wpdb->query("  CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . $this->table . " (
                            `fieldID` int(10) NOT NULL AUTO_INCREMENT,
                            `groupID` int(10) NOT NULL DEFAULT '0',
                            `hierarchy` int(10) NOT NULL DEFAULT '0',
                            `label` varchar(200) NOT NULL,
                            `required` tinyint(1) NOT NULL DEFAULT '0',
                            `required_text` varchar(200) NOT NULL,
                            `cancel_text` varchar(200) NOT NULL,
                            PRIMARY KEY (`fieldID`),
                            KEY `required` (`required`)
                        ) ENGINE=MyISAM 
                        DEFAULT CHARSET=" . $wpdb->charset . "
                        AUTO_INCREMENT=1" );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $wpdb->last_error ) ) {
            $wpdb->print_error();
        }

    }

    /**
     * Gets a record by its ID
     *
     * @since   3.2.0
     *
     * @param   int     $id ID
     * @return  mixed       Record | false
     */
    public function get_by_id( $id ) {

        global $wpdb;
       
        // Get record
        $query = $wpdb->prepare("   SELECT *
                                    FROM " . $wpdb->prefix . $this->table . "
                                    WHERE " . $this->key . " = %d
                                    LIMIT 1",
                                    $id ); 
        $results = $wpdb->get_results( $query, ARRAY_A );
        
        // Check a record was found     
        if ( ! $results ) {
            return false;
        }             
        if ( count( $results ) == 0 ) {
            return false;
        }

        // Return record
        return $results[0];

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
    public function get_by( $field, $value ) {
        
        global $wpdb;
       
        // Get record
        $query = $wpdb->prepare("   SELECT *
                                    FROM " . $wpdb->prefix . $this->table . "
                                    WHERE " . $field . " = '%s'",
                                    $value ); 
        $results = $wpdb->get_results( $query, ARRAY_A );

        // Check a record was found     
        if ( ! $results ) {
            return false;
        }             
        if ( count( $results ) == 0 ) {
            return false;
        }

        // Return
        return $results;

    }


    /**
     * Returns an array of records
     *
     * @since   3.2.0
     * 
     * @param   string  $orderBy            Order By Column (default: label, optional)
     * @param   string  $order              Order Direction (default: ASC, optional)
     * @param   int     $paged              Pagination (default: 1, optional)
     * @param   int     $results PerPage    Results per page (default: 10, optional)
     * @param   string  $search             Search Keywords (optional)
     * @return  array                       Records
     */
    public function get_all( $order_by = 'label', $order = 'ASC', $paged = 1, $results_per_page = 10, $search = '' ) {
        
        global $wpdb;
        
        $get_all = ( ( $paged == -1 ) ? true : false );

        // Search? 
        if ( ! empty( $search ) ) {
            $query = $wpdb->prepare( "  SELECT *
                                        FROM " . $wpdb->prefix . $this->table . "
                                        WHERE keyword LIKE '%%%s%%'
                                        ORDER BY %s %s",
                                        $search,
                                        $order_by,
                                        $order );
        } else {
            $query = $wpdb->prepare( "  SELECT *
                                        FROM " . $wpdb->prefix . $this->table . "
                                        ORDER BY %s %s",
                                        $order_by,
                                        $order );
        }

        // Add Limit
        if ( ! $get_all ) {
            $query = $query . $wpdb->prepare( " LIMIT %d, %d",
                                                ( ( $paged - 1 ) * $results_per_page ),
                                                $results_per_page );
        }

        // Get results
        $results = $wpdb->get_results( $query );

        // Check a record was found     
        if ( ! $results ) {
            return false;
        }             
        if ( count( $results ) == 0 ) {
            return false;
        }

        return $results;

    }

    /**
     * Get the number of matching records
     *
     * @since   1.0.0
     *
     * @param   string $search  Search Keywords (optional)
     * @return  bool            Exists
     */
    public function total( $search = '' ) {

        global $wpdb;
        
        // Prepare query
        if ( ! empty( $search ) ) {
            $query = $wpdb->prepare( "  SELECT COUNT(" . $this->key . ")
                                        FROM " . $wpdb->prefix . $this->table . "
                                        WHERE keyword LIKE '%%%s%%'",
                                        $search ); 
        } else {
            $query = "  SELECT COUNT( " . $this->key . " )
                        FROM " . $wpdb->prefix . $this->table; 
    
        }
        
        // Return count
        return (int) $wpdb->get_var( $query );

    }

    /**
     * Adds or edits a record, based on the given data array.
     *
     * @since   3.2.0
     * 
     * @param   array   $data   Array of data to save
     * @param   int     $id     ID (if set, edits the existing record)
     * @return  mixed           ID or WP_Error
     */
    public function save( $data, $id = '' ) {

        global $wpdb;

        // Check for required data fields
        if ( empty( $data['label'] ) ) {
            return new WP_Error( 'validation_error', __( 'Please complete the label field.', $this->plugin->name ) );
        }

        if ( ! empty( $id ) ) {
            // Editing an existing record
            // Build query
            $query = array();
            foreach ( $data as $key => $value ) {
                switch ($key) {
                    case $this->key:
                        // Ignore
                        break;
                    default:
                        // String
                        $query[] = $key." = '" . htmlentities( $value, ENT_QUOTES, 'UTF-8' ) . "'";
                        break;
                }
            }
            
            // Prepare query to an SQL string
            $query = $wpdb->prepare("   UPDATE " . $wpdb->prefix . $this->table . "
                                        SET " . implode( ',', $query ) . "
                                        WHERE " . $this->key . " = %s",
                                        $id);
                                        
            // Run query
            $result = $wpdb->query( $query );

            // Check query was successful
            if ( $result === FALSE ) {
                return new WP_Error( 'db_query_error', __( 'Rating field could not be edited in the database. DB said: ' . $wpdb->last_error ), $wpdb->last_error ); 
            }

            // Success!
            return $id; 
        } else {
            // Adding a new record  
            $query = $wpdb->prepare("   INSERT INTO ".$wpdb->prefix . $this->table . " (groupID, hierarchy, label, required, required_text, cancel_text)
                                        VALUES (%s, %s, %s, %s, %s, %s)",
                                        $data['groupID'],
                                        $data['hierarchy'],
                                        $data['label'],
                                        $data['required'],
                                        $data['required_text'],
                                        $data['cancel_text'] );
                                    
            // Run query
            $result = $wpdb->query( $query );
          
            // Check query was successful
            if ( $result === FALSE ) {
                return new WP_Error( 'db_query_error', __( 'Rating field could not be added to the database. DB said: '.$wpdb->last_error ), $wpdb->last_error ); 
            }
            
            // Get and return ID
            return $wpdb->insert_id;
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
    public function delete( $data ) {

        global $wpdb;
        
        // Run query
        if ( is_array( $data ) ) {
            $query = "  DELETE FROM " . $wpdb->prefix . $this->table . "
                        WHERE " . $this->key . " IN (" . implode( ',', $data ) . ")";
        } else {
            $query = $wpdb->prepare("   DELETE FROM " . $wpdb->prefix . $this->table . "
                                        WHERE " . $this->key . " = %s
                                        LIMIT 1",
                                        $data );
        }
        $result = $wpdb->query( $query );
                          
        // Check query was successful
        if ( $result === FALSE ) {
            return new WP_Error( 'db_query_error', __( 'Record(s) could not be deleted from the database. DB said: '.$wpdb->last_error ), $wpdb->last_error );
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
    public function change( $field, $old_value, $new_value ) {
        
        global $wpdb;
        
        // Prepare query to an SQL string
        $query = $wpdb->prepare( "  UPDATE " . $wpdb->prefix . $this->table . "
                                    SET " . $field . " = %s
                                    WHERE " . $field . " = %s",
                                    $new_value,
                                    $old_value );
        $result = $wpdb->query( $query );

        // Check query was successful
        if ( $result === FALSE ) {
            return new WP_Error( 'db_query_error', __( 'Record\'s field could not be changed in the database. DB said: '.$wpdb->last_error ), $wpdb->last_error ); 
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
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
            self::$instance = new self;
        }

        return self::$instance;

    }
    
}
?>