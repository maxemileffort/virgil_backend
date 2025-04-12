<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List Table class for Practice Test Users (Subscribers).
 */
class Practice_Test_Users_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => 'user', // Singular name of the listed records
            'plural'   => 'users', // Plural name of the listed records
            'ajax'     => false, // Does this table support ajax?
        ) );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    public function get_columns() {
        $columns = array(
            'cb'                => '<input type="checkbox" />',
            'id'                => __( 'ID', 'practice-tests' ),
            'user_name'         => __( 'Name', 'practice-tests' ),
            'user_email'        => __( 'Email', 'practice-tests' ),
            'user_role'         => __( 'Role', 'practice-tests' ),
            'plan'              => __( 'Plan', 'practice-tests' ),
            'user_status'       => __( 'Status', 'practice-tests' ),
            'registration_date' => __( 'Registered', 'practice-tests' ),
            'tests_taken'       => __( 'Tests Taken', 'practice-tests' ),
            // Add more stats if needed, but keep it concise
        );
        return $columns;
    }

    /**
     * Get sortable columns.
     *
     * @return array
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'id'                => array( 'id', false ),
            'user_name'         => array( 'user_name', false ),
            'user_email'        => array( 'user_email', false ),
            'user_role'         => array( 'user_role', false ),
            'plan'              => array( 'plan', false ),
            'user_status'       => array( 'user_status', false ),
            'registration_date' => array( 'registration_date', false ),
            'tests_taken'       => array( 'tests_taken', false ),
        );
        return $sortable_columns;
    }

    /**
     * Default column rendering.
     *
     * @param object $item
     * @param string $column_name
     * @return mixed
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
            case 'user_name':
            case 'user_email':
            case 'user_role':
            case 'plan':
            case 'user_status':
            case 'tests_taken':
                return esc_html( $item->$column_name );
            case 'registration_date':
                return date_format( date_create( $item->registration_date ), 'Y-m-d H:i' );
            default:
                return print_r( $item, true ); // Show the whole array for troubleshooting
        }
    }

     /**
     * Checkbox column.
     *
     * @param object $item
     * @return string
     */
    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'], // "user"
            $item->id // The value of the checkbox should be the record's ID
        );
    }

     /**
     * Add row actions.
     *
     * @param object $item
     * @return string
     */
    protected function column_user_name($item) {
        $actions = array(
            // TODO: Implement Edit/Delete functionality and link generation
            'edit'   => sprintf('<a href="?page=%s&action=%s&user_id=%s">Edit</a>', $_REQUEST['page'], 'edit_user', $item->id),
            'delete' => sprintf('<a href="?page=%s&action=%s&user_id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to delete this user and all their data?\')">Delete</a>', $_REQUEST['page'], 'delete_user', $item->id, wp_create_nonce('pt_delete_user_' . $item->id)),
        );
        return sprintf('%1$s %2$s', esc_html($item->user_name), $this->row_actions($actions));
    }


    /**
     * Define bulk actions.
     *
     * @return array
     */
    protected function get_bulk_actions() {
        $actions = array(
            'bulk_delete' => __( 'Delete', 'practice-tests' ),
            // Add other bulk actions like 'change_status_active', 'change_status_paused' etc.
        );
        return $actions;
    }

    /**
     * Prepare items for the table.
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'practice_test_subs';

        $per_page     = $this->get_items_per_page( 'users_per_page', 20 );
        $current_page = $this->get_pagenum();

        // Handle sorting
        $orderby = isset( $_GET['orderby'] ) && array_key_exists( $_GET['orderby'], $this->get_sortable_columns() ) ? sanitize_sql_orderby( $_GET['orderby'] ) : 'id';
        $order   = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $_GET['order'] ) : 'DESC';

        // Handle search
        $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $where_clause = '';
        $sql_params = [];
        if (!empty($search_term)) {
             $where_clause = " WHERE (user_name LIKE %s OR user_email LIKE %s)";
             $like_term = '%' . $wpdb->esc_like($search_term) . '%';
             $sql_params[] = $like_term;
             $sql_params[] = $like_term;
        }

        // Calculate total items (considering search)
        $total_items_sql = "SELECT COUNT(id) FROM $table_name" . $where_clause;
        $total_items = $wpdb->get_var($wpdb->prepare($total_items_sql, $sql_params));


        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ) );

        $columns  = $this->get_columns();
        $hidden   = array(); // Hidden columns
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Process bulk actions (needs handler function)
        $this->process_bulk_action();

        // Fetch the data
        $offset = ( $current_page - 1 ) * $per_page;
        $query = "SELECT * FROM $table_name" . $where_clause . " ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $full_params = array_merge($sql_params, [$per_page, $offset]);

        $this->items = $wpdb->get_results( $wpdb->prepare( $query, $full_params ) );
    }

     /**
     * Handle bulk actions.
     */
    public function process_bulk_action() {
        // Detect when a bulk action is being triggered
        if ( 'bulk_delete' === $this->current_action() ) {
             // Security check
            if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
                 wp_die( 'Nonce verification failed!' );
            }
             if (!current_user_can('manage_options')) { // Adjust capability check if needed
                wp_die('Permission denied!');
            }

            $delete_ids = isset( $_POST['user'] ) ? array_map( 'intval', $_POST['user'] ) : array();

            if ( ! empty( $delete_ids ) ) {
                global $wpdb;
                $table_subs = $wpdb->prefix . 'practice_test_subs';
                // TODO: Also delete related data (attempts, responses) for these users
                $ids_placeholder = implode( ',', array_fill( 0, count( $delete_ids ), '%d' ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM $table_subs WHERE id IN ($ids_placeholder)", $delete_ids ) );
                // Add admin notice for success/failure
                echo '<div class="notice notice-success is-dismissible"><p>Selected users deleted.</p></div>';
            }
        }

         // Handle single row delete action
         if ('delete_user' === $this->current_action()) {
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

            if ($user_id > 0 && wp_verify_nonce($nonce, 'pt_delete_user_' . $user_id)) {
                 if (!current_user_can('manage_options')) {
                    wp_die('Permission denied!');
                }
                global $wpdb;
                $table_subs = $wpdb->prefix . 'practice_test_subs';
                 // TODO: Also delete related data (attempts, responses) for this user
                $wpdb->delete($table_subs, ['id' => $user_id], ['%d']);
                 // Add admin notice for success/failure
                 echo '<div class="notice notice-success is-dismissible"><p>User deleted.</p></div>';
            } else {
                 echo '<div class="notice notice-error is-dismissible"><p>Failed to delete user (invalid ID or nonce).</p></div>';
            }
        }
    }

} // End class
