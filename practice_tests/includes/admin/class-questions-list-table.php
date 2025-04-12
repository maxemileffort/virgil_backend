<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List Table class for Practice Test Questions.
 */
class Practice_Test_Questions_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => 'question', // Singular name of the listed records
            'plural'   => 'questions', // Plural name of the listed records
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
            'cb'          => '<input type="checkbox" />', // Checkbox for bulk actions
            'id'          => __( 'ID', 'practice-tests' ),
            'testtype'    => __( 'Type', 'practice-tests' ),
            'question'    => __( 'Question', 'practice-tests' ),
            'subjectarea' => __( 'Subject', 'practice-tests' ),
            'skill'       => __( 'Skill', 'practice-tests' ),
            // Add more columns if needed (e.g., passage, correct answer)
            // Be mindful of screen real estate
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
            'id'          => array( 'id', false ), // True means it's already sorted
            'testtype'    => array( 'testtype', false ),
            'subjectarea' => array( 'subjectarea', false ),
            'skill'       => array( 'skill', false ),
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
            case 'testtype':
            case 'subjectarea':
            case 'skill':
                return esc_html( $item->$column_name );
            case 'question':
                 // Add row actions (Edit, Delete)
                $actions = array(
                    // TODO: Implement Edit/Delete functionality and link generation
                    'edit'   => sprintf( '<a href="?page=%s&action=%s&question_id=%s">Edit</a>', $_REQUEST['page'], 'edit_question', $item->id ),
                    'delete' => sprintf( '<a href="?page=%s&action=%s&question_id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to delete this question?\')">Delete</a>', $_REQUEST['page'], 'delete_question', $item->id, wp_create_nonce('pt_delete_question_' . $item->id) ),
                );
                 return sprintf( '%1$s %2$s', esc_html( wp_trim_words( $item->question, 20, '...' ) ), $this->row_actions( $actions ) );
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
            $this->_args['singular'], // "question"
            $item->id // The value of the checkbox should be the record's ID
        );
    }

    /**
     * Define bulk actions.
     *
     * @return array
     */
    protected function get_bulk_actions() {
        $actions = array(
            'bulk_delete' => __( 'Delete', 'practice-tests' ),
            // Add other bulk actions here if needed
        );
        return $actions;
    }

    /**
     * Prepare items for the table.
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'practice_test_questions';

        $per_page     = $this->get_items_per_page( 'questions_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" ); // Simple count for now

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
        $orderby = isset( $_GET['orderby'] ) && array_key_exists( $_GET['orderby'], $this->get_sortable_columns() ) ? sanitize_sql_orderby( $_GET['orderby'] ) : 'id';
        $order   = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $_GET['order'] ) : 'DESC';

        // Handle search
        $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $where_clause = '';
        if (!empty($search_term)) {
            // Search in question text, subject, skill etc.
             $where_clause = $wpdb->prepare(
                " WHERE (question LIKE %s OR subjectarea LIKE %s OR skill LIKE %s)",
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%'
            );
             // Recalculate total items if searching
             $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name" . $where_clause);
             $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page,
            ]);
        }


        $offset = ( $current_page - 1 ) * $per_page;
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        $this->items = $wpdb->get_results( $sql );
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

            $delete_ids = isset( $_POST['question'] ) ? array_map( 'intval', $_POST['question'] ) : array();

            if ( ! empty( $delete_ids ) ) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'practice_test_questions';
                $ids_placeholder = implode( ',', array_fill( 0, count( $delete_ids ), '%d' ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id IN ($ids_placeholder)", $delete_ids ) );
                // TODO: Add admin notice for success/failure
                echo '<div class="notice notice-success is-dismissible"><p>Selected questions deleted.</p></div>';
            }
        }

         // Handle single row delete action
         if ('delete_question' === $this->current_action()) {
            $question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;
            $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

            if ($question_id > 0 && wp_verify_nonce($nonce, 'pt_delete_question_' . $question_id)) {
                 if (!current_user_can('manage_options')) {
                    wp_die('Permission denied!');
                }
                global $wpdb;
                $table_name = $wpdb->prefix . 'practice_test_questions';
                $wpdb->delete($table_name, ['id' => $question_id], ['%d']);
                 // TODO: Add admin notice for success/failure
                 echo '<div class="notice notice-success is-dismissible"><p>Question deleted.</p></div>';
            } else {
                 echo '<div class="notice notice-error is-dismissible"><p>Failed to delete question (invalid ID or nonce).</p></div>';
            }
        }
    }

} // End class
