<?php

namespace Pantheon_Sessions;

class List_Table extends \WP_List_Table {

	/**
	 * Prepare the items for the list table
	 */
	public function prepare_items() {
		global $wpdb;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->items = $wpdb->get_results( "SELECT * FROM $wpdb->pantheon_sessions" );
		$total_items = count( $this->items );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $total_items,
		) );

	}

	/**
	 * Message for no items found
	 */
	public function no_items() {
		_e( 'No sessions found.', 'pantheon-sessions' );
	}

	/**
	 * Get the columns in the list table
	 */
	public function get_columns() {
		return array(
			'session_id'            => __( 'Session ID', 'pantheon-sessions' ),
			'user_id'               => __( 'User ID', 'pantheon-sessions' ),
			'hostname'              => __( 'Hostname', 'pantheon-sessions' ),
			'timestamp'             => __( 'Timestamp', 'pantheon-sessions' ),
			'session_data'          => __( 'Session Data', 'pantheon-sessions' ),
			);
	}

	/**
	 * Render a column value
	 */
	public function column_default( $item, $column_name ) {
		if ( $column_name == 'session_data' ) {
			return '<code>' . esc_html( $item->session ) . '</code>';
		} else {
			return esc_html( $item->$column_name );
		}
	}

}
