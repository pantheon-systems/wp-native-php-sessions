<?php
/**
 * Autoloader Class.
 *
 * A basic PSR-4 autoloader for theme developers.
 *
 * @author    WPTRT <themes@wordpress.org>
 * @copyright 2019 WPTRT
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/WPTRT/autoload
 */

namespace WPTRT\Autoload;

class Loader {

	/**
	 * Array of loaders.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array
	 */
	protected $loaders = [];

	/**
	 * Adds a new prefix and path to load.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string        $prefix   Namespace prefix.
	 * @param  array|string  $paths    Absolute path(s) where to look for classes.
	 * @return void
	 */
	public function add( $prefix, $paths ) {

		foreach ( (array) $paths as $path ) {
			$this->loaders[ $prefix ][] = $path;
		}
	}

	/**
	 * Removes a loader by prefix or prefix + path.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $prefix   Namespace prefix.
	 * @param  string  $path     Absolute path.
	 * @return void
	 */
	public function remove( $prefix, $path = '' ) {

		// Remove specific loader if both the prefix and path are provided.
		if ( $path ) {
			if ( $this->has( $prefix, $path ) ) {
				$key = array_search( $path, $this->loaders[ $prefix ], true );
				unset( $this->loaders[ $prefix ][ $key ] );
			}

			return;
		}

		// Remove all loaders for a prefix if no path is provided.
		if ( $this->has( $prefix ) ) {
			unset( $this->loaders[ $prefix ] );
		}
	}

	/**
	 * Checks if a loader is already added.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $prefix   Namespace prefix.
	 * @param  string  $path     Absolute path.
	 * @return bool
	 */
	public function has( $prefix, $path = '' ) {

		if ( $path ) {
			return isset( $this->loaders[ $prefix ] ) && in_array( $path, $this->loaders[ $prefix ], true );
		}

		return isset( $this->loaders[ $prefix ] );
	}

	/**
	 * Registers all loaders.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function register() {

		if ( $this->loaders ) {
			spl_autoload_register( function( $class ) {
				$this->load( $class );
			}, true, true );
		}
	}

	/**
	 * Loads a class if it's within the given namespace.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string  $class  Fully-qualified class name.
	 * @return void
	 */
	protected function load( $class ) {

		foreach ( $this->loaders as $prefix => $paths ) {

			// Continue if the class is not in our namespace.
			if ( 0 !== strpos( $class, $prefix ) ) {
				continue;
			}

			// Build a class filename to append to the path.
			$suffix = ltrim( str_replace( $prefix, '', $class ), '\\' );
			$suffix = DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $suffix ) . '.php';

			// Loop through the paths to see if we can find the file
			// for the class.
			foreach ( $paths as $path ) {

				// Load the class file if it exists and return.
				if ( file_exists( $file = realpath( $path ) . $suffix ) ) {
					include $file;
					return;
				}
			}
		}
	}
}
