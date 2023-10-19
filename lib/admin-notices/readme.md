# WPTRT Admin Notices

This is a custom class allowing WordPress theme authors to add admin notices to the WordPress dashboard.
Its primary purpose is for providing a standardized method of creating admin notices in a consistent manner using the default WordPress styles.

Notices created using this method are automatically dismissible.

## Usage

```php
$my_theme_notices = new \WPTRT\AdminNotices\Notices();

// Add a notice.
$my_theme_notices->add( (string) $id, (string) $title, (string) $content, (array) $options );

// Boot things up.
$my_theme_notices->boot();
```

After you instantiate the `Notices` object using `$my_theme_notices = new \WPTRT\AdminNotices\Notices();` you can add new notices using the `add()` method.

The arguments of this method are:

| Parameter | Type | | Description
|---|---|---|---|
| `$id`| `string` | Required | A unique ID for this notice. **The ID can contain lowercase latin letters and underscores**. It is used to construct the option (or user-meta) key that will be strored in the database. |
| `$title` | `string` | Required | The title for your notice. If you don't want to use a title you can use set it to `false`. |
| `$message` | `string` | Required | The content for the notice you want to create. Please note that the only acceptable tags here are `<p>`, `<a>`, `<em>`, `<strong>`.|
| `$options` | `array` | Optional | Extra arguments for this notice. Can be used to alter the notice's default behavior. |

The `$options` argument is an array that can have the following optional items:

| Key | Type | Value | Default
|---|---|---|---|
| `scope` | `string` | Can be `global` or `user`. Determines if the dismissed status will be saved as an option or user-meta. | `global` |
| `type` | `string` |  Can be one of `info`, `success`, `warning`, `error`. | `info`
| `alt_style` | `bool` | Set to true if you want to use alternative styles. These have a background-color depending on the `type` argument - in contrast to the normal styles that use a white background. | `false` |
| `capability` | `string` | The user capability required to see the notice. For a list of all available capabilities please refer to the [Roles and Capabilities](https://wordpress.org/support/article/roles-and-capabilities/) article. | `edit_theme_options`
| `screens` | `array` | An array of screens where the notice will be displayed. For a reference of all available screen-IDs, refer to [this article](https://codex.wordpress.org/Plugin_API/Admin_Screen_Reference). | `[]` |
| `option_prefix` | `string` | The prefix that will be used to build the option (or user-meta) name. Can contain lowercase latin letters and underscores. The actual option is built by combining the `option_prefix` argument with the defined ID from the 1st argument of the `add()` method. | `wptrt_notice_dismissed` |

## Examples
You can add the following code within your theme's existing code.

First we need to instantiate the `Notices` object:
```php
use WPTRT\AdminNotices\Notices;

$my_theme_notices = new Notices();
```
To add a simple, default notice:
```php
$my_theme_notices->add(
    'my_theme_notice',                           // Unique ID.
    esc_html__( 'Notice Title', 'textdomain' ),  // The title for this notice.
    esc_html__( 'Notice content', 'textdomain' ) // The content for this notice.
);
```
The above example will create a new notice that will only show on all dashboard pages. When the notice gets dismissed, a new option will be saved in the database with the key `wptrt_notice_dismissed_my_theme_notice`. The key gets created by appending the `$id` to the default prefix for the option (`wptrt_notice_dismissed`), separated by an underscore.

To add a more customized notice:

```php
$my_theme_notices->add(
    'my_notice',                                  // Unique ID.
    esc_html__( 'Notice Title', 'textdomain' ),   // The title for this notice.
    esc_html__( 'Notice content', 'textdomain' ), // The content for this notice.
    [
        'scope'         => 'user',       // Dismiss is per-user instead of global.
        'screens'       => [ 'themes' ], // Only show notice in the "themes" screen.
        'type'          => 'warning',    // Make this a warning (orange color).
        'alt_style'     => true,         // Use alt styles.
        'option_prefix' => 'my_theme',   // Change the user-meta prefix.
    ]
);
```

The above example will create a new notice that will only show in the "Themes" screen in the dashboard. When the notice gets dismissed, a new user-meta will be saved and the key for the stored user-meta will be `my_theme_my_notice`. The key gets created by appending the `$id` to our defined `option_prefix`, separated by an underscore.

The `Notices` class can be used to add multiple notices.
Once you have finished adding the notices, you will have to run the `boot` method so that the notices can be added to the dashboard:
```php
$my_theme_notices->boot();
```

To sum up all the above, a complete example of how to add an admin notice would look like this:

```php
$my_theme_notices = new \WPTRT\AdminNotices\Notices();
$my_theme_notices->add( 'my_theme_notice', __( 'Title', 'textdomain' ), __( 'Content', 'textdomain' ) );
$my_theme_notices->boot();
```

## Autoloading

You'll need to use an autoloader with this. Ideally, this would be [Composer](https://getcomposer.org).  However, we have a [basic autoloader](https://github.com/WPTRT/autoload) available to include with themes if needed.

### Composer

From the command line:

```sh
composer require wptrt/admin-notices
```

### WPTRT Autoloader

If using the WPTRT autoloader, use the following code:

```php
include get_theme_file_path( 'path/to/autoload/src/Loader.php' );

$loader = new \WPTRT\Autoload\Loader();
$loader->add( 'WPTRT\\AdminNotices\\Notice', get_theme_file_path( 'path/to/admin-notices/src' ) );
$loader->register();
```
