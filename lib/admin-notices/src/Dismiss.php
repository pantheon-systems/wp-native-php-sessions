<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Handles dismissing admin notices.
 *
 * @package   WPTRT/admin-notices
 * @author    WPTRT <themes@wordpress.org>
 * @copyright 2019 WPTRT
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/WPTRT/admin-notices
 */

namespace WPTRT\AdminNotices;

/**
 * The Dismiss class, responsible for dismissing and checking the status of admin notices.
 *
 * @since 1.0.0
 */
class Dismiss {

	/**
	 * The notice-ID.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $id;

	/**
	 * The prefix we'll be using for the option/user-meta.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $prefix;

	/**
	 * The notice's scope. Can be "user" or "global".
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $scope;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0
	 * @param string $id     A unique ID for this notice. Can contain lowercase characters and underscores.
	 * @param string $prefix The prefix that will be used for the option/user-meta.
	 * @param string $scope  Controls where the dismissal will be saved: user or global.
	 */
	public function __construct( $id, $prefix, $scope = 'global' ) {

		// Set the object properties.
		$this->id     = sanitize_key( $id );
		$this->prefix = sanitize_key( $prefix );
		$this->scope  = ( in_array( $scope, [ 'global', 'user' ], true ) ) ? $scope : 'global';

		// Handle AJAX requests to dismiss the notice.
		add_action( 'wp_ajax_wptrt_dismiss_notice', [ $this, 'ajax_maybe_dismiss_notice' ] );

		// Print the script after common.js.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_script' ) );
	}

	/**
	 * Print the script for dismissing the notice.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function add_script() {

		$id             = esc_attr( $this->id );
		$nonce          = wp_create_nonce( 'wptrt_dismiss_notice_' . $this->id );
		$admin_ajax_url = esc_url( admin_url( 'admin-ajax.php' ) );

		$script = <<<EOD
jQuery( function() {
    var dismissBtn  = document.querySelector( '#wptrt-notice-$id .notice-dismiss' );

    // Add an event listener to the dismiss button.
    dismissBtn.addEventListener( 'click', function( event ) {
    	var httpRequest = new XMLHttpRequest(),
    		postData    = '';

    	// Build the data to send in our request.
    	// Data has to be formatted as a string here.
    	postData += 'id=$id';
    	postData += '&action=wptrt_dismiss_notice';
    	postData += '&nonce=$nonce';

    	httpRequest.open( 'POST', '$admin_ajax_url' );
    	httpRequest.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' )
    	httpRequest.send( postData );
    });
});
EOD;

		wp_add_inline_script( 'common', $script, 'after' );
	}

	/**
	 * Check if the notice has been dismissed or not.
	 *
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	public function is_dismissed() {

		// Check if the notice has been dismissed when using user-meta.
		if ( 'user' === $this->scope ) {
			return ( get_user_meta( get_current_user_id(), "{$this->prefix}_{$this->id}", true ) );
		}

		return ( get_option( "{$this->prefix}_{$this->id}" ) );
	}

	/**
	 * Run check to see if we need to dismiss the notice.
	 * If all tests are successful then call the dismiss_notice() method.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function ajax_maybe_dismiss_notice() {

		// Sanity check: Early exit if we're not on a wptrt_dismiss_notice action.
		if ( ! isset( $_POST['action'] ) || 'wptrt_dismiss_notice' !== $_POST['action'] ) {
			return;
		}

		// Sanity check: Early exit if the ID of the notice is not the one from this object.
		if ( ! isset( $_POST['id'] ) || $this->id !== $_POST['id'] ) {
			return;
		}

		// Security check: Make sure nonce is OK.
		check_ajax_referer( 'wptrt_dismiss_notice_' . $this->id, 'nonce', true );

		// If we got this far, we need to dismiss the notice.
		$this->dismiss_notice();
	}

	/**
	 * Actually dismisses the notice.
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function dismiss_notice() {
		if ( 'user' === $this->scope ) {
			update_user_meta( get_current_user_id(), "{$this->prefix}_{$this->id}", true );
			return;
		}
		update_option( "{$this->prefix}_{$this->id}", true, false );
	}
}
