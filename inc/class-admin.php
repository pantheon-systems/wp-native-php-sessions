<?php
/**
 * Controller for backend functionality.
 *
 * @package WPNPS
 */

namespace Pantheon_Sessions;

/**
 * Controller for backend functionality.
 */
class Admin {

	/**
	 * Copy of the singleton instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Name of capability required to perform actions.
	 *
	 * @var string
	 */
	private static $capability = 'manage_options';

	/**
	 * Gets a copy of the singleton instance.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Admin();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	/**
	 * Load admin actions
	 */
	private function setup_actions() {

		add_action( 'admin_menu', [ $this, 'action_admin_menu' ] );
		add_action( 'wp_ajax_pantheon_clear_session', [ $this, 'handle_clear_session' ] );
	}

	/**
	 * Register the admin menu
	 */
	public function action_admin_menu() {

		add_management_page( __( 'Pantheon Sessions', 'wp-native-php-sessions' ), __( 'Sessions', 'wp-native-php-sessions' ), self::$capability, 'pantheon-sessions', [ $this, 'handle_page' ] );
	}

	/**
	 * Render the admin page
	 */
	public function handle_page() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once __DIR__ . '/class-list-table.php';

		echo '<div class="wrap">';

		echo '<div>';
		$query_args = [
			'action'  => 'pantheon_clear_session',
			'nonce'   => wp_create_nonce( 'pantheon_clear_session' ),
			'session' => 'all',
		];
		if ( $wpdb->get_var( "SELECT COUNT(session_id) FROM $wpdb->pantheon_sessions" ) ) {
			echo '<a class="button pantheon-clear-all-sessions" style="float:right; margin-top: 9px;" href="' . esc_url( add_query_arg( $query_args, admin_url( 'admin-ajax.php' ) ) ) . '">' . esc_html__( 'Clear All', 'wp-native-php-sessions' ) . '</a>';
		}
		echo '<h2>' . esc_html__( 'Pantheon Sessions', 'wp-native-php-sessions' ) . '</h2>';
		if ( isset( $_GET['message'] ) && in_array( $_GET['message'], [ 'delete-all-session', 'delete-session' ], true ) ) {
			if ( 'delete-all-session' === $_GET['message'] ) {
				$message = __( 'Cleared all sessions.', 'wp-native-php-sessions' );
			} elseif ( 'delete-session' === $_GET['message'] ) {
				$message = __( 'Session cleared.', 'wp-native-php-sessions' );
			}
			echo '<div id="message" class="updated"><p>' . esc_html( $message ) . '</p></div>';
		}
		echo '</div>';

		$wp_list_table = new List_Table();
		$wp_list_table->prepare_items();
		$wp_list_table->display();

		echo '</div>';

		add_action( 'admin_footer', [ $this, 'action_admin_footer' ] );
	}

	/**
	 * Handle a request to clear all sessions
	 */
	public function handle_clear_session() {
		global $wpdb;

		if ( ! current_user_can( self::$capability ) || ! wp_verify_nonce( $_GET['nonce'], 'pantheon_clear_session' ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'wp-native-php-sessions' ) );
		}

		if ( ! empty( $_GET['session'] ) && 'all' === $_GET['session'] ) {
			$wpdb->query( "DELETE FROM $wpdb->pantheon_sessions" );
			$message = 'delete-all-session';
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->pantheon_sessions WHERE session_id=%s", sanitize_text_field( $_GET['session'] ) ) );
			$message = 'delete-session';
		}
		wp_safe_redirect( add_query_arg( 'message', $message, wp_get_referer() ) );
		exit;
	}

	/**
	 * Stuff that needs to go in the footer
	 */
	public function action_admin_footer() {
		?>
	<script>
	(function($){
		$(document).ready(function(){
			$('.pantheon-clear-all-sessions').on('click', function( e ){
				if ( ! confirm( '<?php esc_html_e( 'Are you sure you want to clear all active sessions?', 'wp-native-php-sessions' ); ?>') ) {
					e.preventDefault();
				}
			});
		});
	}(jQuery))
	</script>
		<?php
	}

}
