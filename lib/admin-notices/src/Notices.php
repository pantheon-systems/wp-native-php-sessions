<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Admin-Notices class.
 *
 * Handles creating Notices and printing them.
 *
 * @package   WPTRT/admin-notices
 * @author    WPTRT <themes@wordpress.org>
 * @copyright 2019 WPTRT
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/WPTRT/admin-notices
 */

namespace WPTRT\AdminNotices;

/**
 * The Admin_Notice class, responsible for creating admin notices.
 *
 * Each notice is a new instance of the object.
 *
 * @since 1.0.0
 */
class Notices {

	/**
	 * An array of notices.
	 *
	 * @access private
	 * @since 1.0
	 * @var array
	 */
	private $notices = [];

	/**
	 * Adds actions for the notices.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function boot() {

		// Add the notice.
		add_action( 'admin_notices', [ $this, 'the_notices' ] );
	}

	/**
	 * Add a notice.
	 *
	 * @access public
	 * @since 1.0
	 * @param string $id      A unique ID for this notice. Can contain lowercase characters and underscores.
	 * @param string $title   The title for our notice.
	 * @param string $message The message for our notice.
	 * @param array  $options An array of additional options to change the defaults for this notice.
	 *                        See Notice::__constructor() for details.
	 * @return void
	 */
	public function add( $id, $title, $message, $options = [] ) {
		$this->notices[ $id ] = new Notice( $id, $title, $message, $options );
	}

	/**
	 * Remove a notice.
	 *
	 * @access public
	 * @since 1.0
	 * @param string $id The unique ID of the notice we want to remove.
	 * @return void
	 */
	public function remove( $id ) {
		unset( $this->notices[ $id ] );
	}

	/**
	 * Get a single notice.
	 *
	 * @access public
	 * @since 1.0
	 * @param string $id The unique ID of the notice we want to retrieve.
	 * @return Notice|null
	 */
	public function get( $id ) {
		if ( isset( $this->notices[ $id ] ) ) {
			return $this->notices[ $id ];
		}
		return null;
	}

	/**
	 * Get all notices.
	 *
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	public function get_all() {
		return $this->notices;
	}

	/**
	 * Prints the notice.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function the_notices() {
		$notices = $this->get_all();

		foreach ( $notices as $notice ) {
			$notice->the_notice();
		}
	}
}
