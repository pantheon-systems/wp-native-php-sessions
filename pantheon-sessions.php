<?php
/**
 * Plugin Name: Native PHP Sessions for WordPress
 * Version: 1.3.7-dev
 * Description: Offload PHP's native sessions to your database for multi-server compatibility.
 * Author: Pantheon
 * Author URI: https://www.pantheon.io/
 * Plugin URI: https://wordpress.org/plugins/wp-native-php-sessions/
 * Text Domain: wp-native-php-sessions
 *
 * @package WPNPS
 **/

use Pantheon_Sessions\Session;

define( 'PANTHEON_SESSIONS_VERSION', '1.3.7-dev' );

/**
 * Main controller class for the plugin.
 */
class Pantheon_Sessions {

	/**
	 * Copy of the singleton instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * The admin instance.
	 *
	 * @var \Pantheon_Sessions\Admin
	 */
	private $admin;

	/**
	 * Gets a copy of the singleton instance.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new Pantheon_Sessions();
			self::$instance->load();
		}
		return self::$instance;
	}

	/**
	 * Load the plugin
	 */
	private function load() {

		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		$this->define_constants();
		$this->require_files();

		if ( PANTHEON_SESSIONS_ENABLED ) {

			$this->setup_database();
			$this->initialize_session_override();
			$this->set_ini_values();
			add_action( 'set_logged_in_cookie', [ __CLASS__, 'action_set_logged_in_cookie' ], 10, 4 );
			add_action( 'clear_auth_cookie', [ __CLASS__, 'action_clear_auth_cookie' ] );
		}
	}

	/**
	 * Define our constants
	 */
	private function define_constants() {

		if ( ! defined( 'PANTHEON_SESSIONS_ENABLED' ) ) {
			define( 'PANTHEON_SESSIONS_ENABLED', 1 );
		}
	}

	/**
	 * Load required files
	 */
	private function require_files() {

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once __DIR__ . '/inc/class-cli-command.php';
		}

		if ( is_admin() ) {
			require_once __DIR__ . '/inc/class-admin.php';
			$this->admin = Pantheon_Sessions\Admin::get_instance();
		}
	}

	/**
	 * Set the PHP ini settings for the session implementation to work properly
	 *
	 * Largely adopted from Drupal 7's implementation
	 */
	private function set_ini_values() {

		if ( headers_sent() ) {
			return;
		}

		// If the user specifies the cookie domain, also use it for session name.
		if ( defined( 'COOKIE_DOMAIN' ) && constant( 'COOKIE_DOMAIN' ) ) {
			$cookie_domain = constant( 'COOKIE_DOMAIN' );
			$session_name  = $cookie_domain;
		} else {
			$session_name  = parse_url( home_url(), PHP_URL_HOST );
			$cookie_domain = ltrim( $session_name, '.' );
			// Strip leading periods, www., and port numbers from cookie domain.
			if ( strpos( $cookie_domain, 'www.' ) === 0 ) {
				$cookie_domain = substr( $cookie_domain, 4 );
			}
			$cookie_domain = explode( ':', $cookie_domain );
			$cookie_domain = '.' . $cookie_domain[0];
		}

		// Per RFC 2109, cookie domains must contain at least one dot other than the
		// first. For hosts such as 'localhost' or IP Addresses we don't set a cookie domain.
		if ( count( explode( '.', $cookie_domain ) ) > 2 && ! is_numeric( str_replace( '.', '', $cookie_domain ) ) ) {
			ini_set( 'session.cookie_domain', $cookie_domain );
		}
		// To prevent session cookies from being hijacked, a user can configure the
		// SSL version of their website to only transfer session cookies via SSL by
		// using PHP's session.cookie_secure setting. The browser will then use two
		// separate session cookies for the HTTPS and HTTP versions of the site. So we
		// must use different session identifiers for HTTPS and HTTP to prevent a
		// cookie collision.
		if ( is_ssl() ) {
			ini_set( 'session.cookie_secure', true );
		}
		$prefix = ini_get( 'session.cookie_secure' ) ? 'SSESS' : 'SESS';

		session_name( $prefix . substr( hash( 'sha256', $session_name ), 0, 32 ) );

		// Use session cookies, not transparent sessions that puts the session id in
		// the query string.
		$use_cookies = '1';
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$use_cookies = '0';
		}
		ini_set( 'session.use_cookies', $use_cookies );
		ini_set( 'session.use_only_cookies', '1' );
		ini_set( 'session.use_trans_sid', '0' );
		// Don't send HTTP headers using PHP's session handler.
		// An empty string is used here to disable the cache limiter.
		ini_set( 'session.cache_limiter', '' );
		// Use httponly session cookies. Limits use by JavaScripts.
		ini_set( 'session.cookie_httponly', '1' );
		// Get cookie lifetime from filters so you can put your custom lifetime.
		ini_set( 'session.cookie_lifetime', (int) apply_filters( 'pantheon_session_expiration', 0 ) );
	}

	/**
	 * Override the default sessions implementation with our own
	 *
	 * Largely adopted from Drupal 7's implementation
	 */
	private function initialize_session_override() {
		require_once __DIR__ . '/inc/class-session.php';
		require_once __DIR__ . '/inc/class-session-handler.php';
		$session_handler = new Pantheon_Sessions\Session_Handler();
		if ( PHP_SESSION_ACTIVE !== session_status() ) {
			// Check if headers have already been sent.
			if ( headers_sent( $file, $line ) ) {
				// Output a friendly error message if headers are already sent.
				trigger_error(
					sprintf(
						/* translators: %1s: File path, %2d: Line number */
						__( "Oops! The wp-native-php-sessions plugin couldn't start the session because output has already been sent. This might be caused by PHP throwing errors. Please check the code in %1s on line %2d.", 'wp-native-php-sessions' ),
						$file,
						$line
					),
					E_USER_WARNING
				);
			} else {
				session_set_save_handler( $session_handler, false );
			}
		}
		// Close the session before $wpdb destructs itself.
		add_action( 'shutdown', 'session_write_close', 999, 0 );
	}

	/**
	 * Set up the database
	 */
	private function setup_database() {
		global $wpdb, $table_prefix;

		$table_name              = "{$table_prefix}pantheon_sessions";
		$wpdb->pantheon_sessions = $table_name;
		$wpdb->tables[]          = 'pantheon_sessions';

		if ( get_option( 'pantheon_session_version' ) ) {
			return;
		}

		$create_statement = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'An auto-incrementing id to serve as an index.',
			`user_id` bigint(20) unsigned NOT NULL COMMENT 'The user_id corresponding to a session, or 0 for anonymous user.',
			`session_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'A session ID. The value is generated by plugin''s session handlers.',
			`secure_session_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'Secure session ID. The value is generated by plugin''s session handlers.',
			`ip_address` varchar(128) NOT NULL DEFAULT '' COMMENT 'The IP address that last used this session ID.',
			`datetime` datetime DEFAULT NULL COMMENT 'The datetime value when this session last requested a page. Old records are purged by PHP automatically.',
			`data` mediumblob COMMENT 'The serialized contents of \$_SESSION, an array of name/value pairs that persists across page requests by this session ID. Plugin loads \$_SESSION from here at the start of each request and saves it at the end.',
			KEY `session_id` (`session_id`),
			KEY `secure_session_id` (`secure_session_id`)
		)";
		// phpcs:ignore
		$wpdb->query( $create_statement );
		update_option( 'pantheon_session_version', PANTHEON_SESSIONS_VERSION );
	}

	/**
	 * Sets the user id value to the session when the user logs in.
	 *
	 * @param string  $logged_in_cookie Cooke name.
	 * @param integer $expire           When the cookie is set to expire.
	 * @param integer $expiration       When the cookie is set to expire.
	 * @param integer $user_id          Id for the logged-in user.
	 */
	public static function action_set_logged_in_cookie( $logged_in_cookie, $expire, $expiration, $user_id ) {
		$session = Session::get_by_sid( session_id() );
		if ( $session ) {
			$session->set_user_id( $user_id );
		}
	}

	/**
	 * Clears the user id value from the session when the user logs out.
	 */
	public static function action_clear_auth_cookie() {
		$session = Session::get_by_sid( session_id() );
		if ( $session ) {
			$session->set_user_id( 0 );
		}
	}

	/**
	 * Force the plugin to be the first loaded
	 */
	public static function force_first_load() {
		$path    = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
		$plugins = get_option( 'active_plugins' );
		if ( $plugins ) {
			$key = array_search( $path, $plugins, true );
			if ( $key ) {
				array_splice( $plugins, $key, 1 );
				array_unshift( $plugins, $path );
				update_option( 'active_plugins', $plugins );
			}
		}
	}

	/**
	 * Checks whether primary keys were set and notifies users if not.
	 */
	public static function check_native_primary_keys() {
		global $wpdb;
		$table_name = $wpdb->base_prefix . 'pantheon_sessions';
		$old_table  = $wpdb->base_prefix . 'old_pantheon_sessions';
		$query      = "SHOW KEYS FROM {$table_name} WHERE key_name = 'PRIMARY';";

		$key_existence = $wpdb->get_results( $query );

		if ( empty( $key_existence ) ) {
			// If the key doesn't exist, recommend remediation.
			?>
			<div class="notice notice-error is-dismissible">
				<p>
				<?php
				print __( 'Your PHP Native Sessions table is missing a primary key. Please run "wp {site_name}.dev pantheon session add-index" and verify that the process completes successfully and that this message goes away, then run "wp {site_name}.live pantheon session add-index" to resolve this issue on your live environment.',
				'wp-native-php-sessions' );
				?>
						</p>
			</div>
			<?php
		}

		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s',
		$wpdb->esc_like( $old_table ) );

		// Check for table existence and delete if present.
		if ( $wpdb->get_var( $query ) == $old_table ) {
			// If an old table exists but has not been removed, suggest doing so.
			?>
			<div class="notice notice-error">
				<p>
				<?php
				print __( 'An old version of the PHP Native Sessions table is detected. When testing is complete, run wp {site_name}.{env} pantheon session primary-key-finalize to clean up old data, or run wp {site_name}.{env} pantheon session primary-key-revert if there were issues.',
				'wp-native-php-sessions' );
				?>
						</p>
			</div>
			<?php
		}
	}

	/**
	 * Set id as primary key in the Native PHP Sessions plugin table.
	 */
	public function add_index() {
		global $wpdb;
		$unprefixed_table = 'pantheon_sessions';
		$table            = $wpdb->base_prefix . $unprefixed_table;
		$temp_clone_table = $wpdb->base_prefix . 'sessions_temp_clone';

		// If the command has been run multiple times and there is already a
		// temp_clone table, drop it.
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $temp_clone_table ) );

		if ( $wpdb->get_var( $query ) == $temp_clone_table ) {
			$query = "DROP TABLE {$temp_clone_table};";
			$wpdb->query( $query );
		}

		if ( ! PANTHEON_SESSIONS_ENABLED ) {
			$this->safe_output( __( 'Pantheon Sessions is currently disabled.', 'wp-native-php-sessions' ), 'error' );
		}

		// Verify that the ID column/primary key does not already exist.
		$query         = "SHOW KEYS FROM {$table} WHERE key_name = 'PRIMARY';";
		$key_existence = $wpdb->get_results( $query );

		// Avoid errors by not attempting to add a column that already exists.
		if ( ! empty( $key_existence ) ) {
			$this->safe_output( __( 'ID column already exists and does not need to be added to the table.', 'wp-native-php-sessions' ), 'error' );
		}

		// Alert the user that the action is going to go through.
		$this->safe_output( __( 'Primary Key does not exist, resolution starting.', 'wp-native-php-sessions' ), 'log' );

		$count_query = "SELECT COUNT(*) FROM {$table};";
		$count_total = $wpdb->get_results( $count_query );
		$count_total = $count_total[0]->{'COUNT(*)'};

		if ( $count_total >= 20000 ) {
			// translators: %s is the total number of rows that exist in the pantheon_sessions table.
			$this->safe_output( __( 'A total of %s rows exist. To avoid service interruptions, this operation will be run in batches. Any sessions created between now and when operation completes may need to be recreated.', 'wp-native-php-sessions' ), 'log', [ $count_total ] );
		}
		// Create temporary table to copy data into in batches.
		$query = "CREATE TABLE {$temp_clone_table} LIKE {$table};";
		$wpdb->query( $query );
		$query = "ALTER TABLE {$temp_clone_table} ADD COLUMN id BIGINT AUTO_INCREMENT PRIMARY KEY FIRST";
		$wpdb->query( $query );

		$batch_size = 20000;
		$loops      = ceil( $count_total / $batch_size );

		for ( $i = 0; $i < $loops; $i++ ) {
			$offset = $i * $batch_size;

			$query           = sprintf( "INSERT INTO {$temp_clone_table} 
(user_id, session_id, secure_session_id, ip_address, datetime, data) 
SELECT user_id,session_id,secure_session_id,ip_address,datetime,data 
FROM %s ORDER BY user_id LIMIT %d OFFSET %d", $table, $batch_size, $offset );
			$results         = $wpdb->query( $query );
			$current_results = $results + ( $batch_size * $i );

			// translators: %1 and %2 are how many rows have been processed out of how many total.
			$this->safe_output( __( 'Updated %1$s / %2$s rows. ', 'wp-native-php-sessions' ), 'log', [ $current_results, $count_total ] );
		}

		// Hot swap the old table and the new table, deleting a previous old
		// table if necessary.
		$old_table = $wpdb->base_prefix . 'old_' . $unprefixed_table;
		$query     = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $old_table ) );

		if ( $wpdb->get_var( $query ) == $old_table ) {
			$query = "DROP TABLE {$old_table};";
			$wpdb->query( $query );
		}
		$query = "ALTER TABLE {$table} RENAME {$old_table};";
		$wpdb->query( $query );
		$query = "ALTER TABLE {$temp_clone_table} RENAME {$table};";
		$wpdb->query( $query );

		$this->safe_output( __( 'Operation complete, please verify that your site is working as expected. When ready, run terminus wp {site_name}.{env} pantheon session primary-key-finalize to clean up old data, or run terminus wp {site_name}.{env} pantheon session primary-key-revert if there were issues.', 'wp-native-php-sessions' ), 'log' );
	}

	/**
	 * Finalizes the creation of a primary key by deleting the old data.
	 */
	public function primary_key_finalize() {
		global $wpdb;
		$table = $wpdb->base_prefix . 'old_pantheon_sessions';

		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) );

		// Check for table existence and delete if present.
		if ( ! $wpdb->get_var( $query ) == $table ) {
			$this->safe_output( __( 'Old table does not exist to be removed.', 'wp-native-php-sessions' ), 'error' );
		} else {
			$query = "DROP TABLE {$table};";
			$wpdb->query( $query );

			$this->safe_output( __( 'Old table has been successfully removed, process complete.', 'wp-native-php-sessions' ), 'log' );

			$query = "describe {$table};";
            print "findme table is: {$table}";
			$describe = $wpdb->get_results( $query );
			var_dump( $describe );
		}
	}

	/**
	 * Reverts addition of primary key.
	 */
	public function primary_key_revert() {
		global $wpdb;
		$old_clone_table  = $wpdb->base_prefix . 'old_pantheon_sessions';
		$temp_clone_table = $wpdb->base_prefix . 'temp_pantheon_sessions';
		$table            = $wpdb->base_prefix . 'pantheon_sessions';

		// If there is no old table to roll back to, error.
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $old_clone_table ) );

		if ( ! $wpdb->get_var( $query ) == $old_clone_table ) {
			$this->safe_output( __( 'There is no old table to roll back to.', 'wp-native-php-sessions' ), 'error' );
		}

		// Swap old table and new one.
		$query = "ALTER TABLE {$table} RENAME {$temp_clone_table};";
		$wpdb->query( $query );
		$query = "ALTER TABLE {$old_clone_table} RENAME {$table};";
		$wpdb->query( $query );
		$this->safe_output( __( 'Rolled back to previous state successfully, dropping corrupt table.', 'wp-native-php-sessions' ), 'log' );

		// Remove table which did not function.
		$query = "DROP TABLE {$temp_clone_table}";
		$wpdb->query( $query );
		$this->safe_output( __( 'Process complete.', 'wp-native-php-sessions' ), 'log' );
	}

	/**
	 * Provide output to users, whether it's being run in WP_CLI or not.
	 *
	 * @param string $message Message to be printed.
	 * @param string $type If message is being printed through WP_CLI, what type of message.
	 * @param array $variables If sprintf is needed, an array of values.
	 *
	 * @return void
	 */
	protected function safe_output( $message, $type, array $variables = [] ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::$type( vsprintf( $message, $variables ) );
			return;
		}

		print "\n" . vsprintf( $message, $variables );

		// Calling WP_CLI::error triggers an exit, but we still need to exist even if we don't have WP_CLI available.
		if ( $type === 'error' ) {
			exit( 1 );
		}
	}
}

/**
 * Release the kraken!
 *
 * @return object
 */
function Pantheon_Sessions() {
	return Pantheon_Sessions::get_instance();
}

add_action( 'activated_plugin', 'Pantheon_Sessions::force_first_load' );
add_action( 'admin_notices', 'Pantheon_Sessions::check_native_primary_keys' );

Pantheon_Sessions();
