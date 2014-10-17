=== Pantheon-sessions ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://www.getpantheon.com/
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 4.0.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use native PHP sessions and stay horizontally scalable. Better living through superior technology.

== Description ==

WordPress core does not use sessions, but sometimes they are required by your use case.

This plugin implements PHP's native session functionality backed by the WordPress database. This allows plugins, themes, and custom code to safely use PHP $_SESSIONs in a distributed environment where PHP's default tempfile sessions won't work.

Note that primary development is on GitHub if you would like to contribute:

https://github.com/pantheon-systems/wp-native-php-sessions

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

That's it!

== Frequently Asked Questions ==

= Why not use another session plugin? =

This implements the built-in PHP session handling functions, rather than introducing anything custom. That way you can use built-in language functions like the `$_SESSION` superglobal and `session_start()` in your code. Everything else will "just work".

= Why store them in the database? =

PHP's fallback default functionality is to allow sessions to be stored in a temporary file. This is what most code that invokes sessions uses by default, and in simple use-cases it works, which is why so many plugins do it.

However, if you intend to scale your application, local tempfiles are a dangerous choice. They are not shared between different instances of the application, producing erratic behavior that can be impossible to debug. By storing them in the database the state of the sessions is shared across all application instances.

== Troubleshooting ==

If you see an error like "Fatal error: session_start(): Failed to initialize storage module: user (path: ) in .../code/wp-content/plugins/plugin-that-uses-sessions/example.php on line 2" do the following:

1. Deactivate the plugin that is attempting to start the session. Temporarily renaming the plugin folder will do the trick.
2. Activate this wp-native-php-sessions plugin
3. Activate the plugin that uses sessions

== Changelog ==

= 0.1 =
* Initial release
