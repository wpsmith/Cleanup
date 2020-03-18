# Plugin

[![Code Climate](https://codeclimate.com/github/wpsmith/Cleanup/badges/gpa.svg)](https://codeclimate.com/github/wpsmith/Cleanup)

A class to use in your WordPress plugin to cleanup various portions of WordPress.

## Description

WordPress outputs a lot of stuff that can be removed safely. It is often recommended to remove these from WordPress for the security of the sites. This package can remove the following:
* Admin Bar Items
* Links
* Scripts
* Widgets, Dashboard Widgets, and Frontend Widgets
* Admin Menu Items
* REST

## Installation

This isn't a WordPress plugin on its own, so the usual instructions don't apply. Instead you can install manually or using `composer`.

### Manually install class
Copy [`src`](src) folder into your plugin for basic usage. Be sure to require the various files accordingly.

or:

### Install class via Composer
1. Tell Composer to install this class as a dependency: `composer require wpsmith/plugin`
2. Recommended: Install the Mozart package: `composer require coenjacobs/mozart --dev` and [configure it](https://github.com/coenjacobs/mozart#configuration).
3. The class is now renamed to use your own prefix to prevent collisions with other plugins bundling this class.

## Implementation & Usage

### Base Cleanup (Abstract Class)

Every Cleanup class (those below), can include the following items in their cleanup:
* LINKS
* WIDGETS
* POST FORMATS
* SCRIPTS†
* ADMIN BAR ITEMS†

† Doesn't clean anything by default

### Admin Cleanup

You can clean up the admin entirely in one of three ways:

~~~php
// Cleaning up the admin at instantiation.
\WPS\WP\CleanupAdmin::get_instance( 'all' );

// Cleaning up the admin at instantiation item by item.
\WPS\WP\CleanupAdmin::get_instance( [
    'menu'         => 'all',
    'dashboard'    => 'all',
    'links'        => 'all',
    'widgets'      => 'all',
    'post_formats' => 'all',
] );

// Cleaning up the admin object-orientedly.
$admin_cleanup = \WPS\WP\CleanupAdmin::get_instance();
$admin_cleanup->remove_all();
~~~

### Public Cleanup

~~~php
// Cleaning up the public at instantiation.
\WPS\WP\CleanupPublic::get_instance( 'all' );

// Cleaning up the public at instantiation item by item.
\WPS\WP\CleanupPublic::get_instance( [
    'rest'             => 'all',
    'frontend_widgets' => 'all',
    'links'            => 'all',
    'widgets'          => 'all',
    'post_formats'     => 'all',
] );

// Cleaning up the public object-orientedly.
$public_cleanup = \WPS\WP\CleanupPublic::get_instance();
$public_cleanup->remove_all();
~~~

### WP Cleanup

WordPress Cleanup does the following:
* Resets the excerpt metabox priority
* Removes WP Version from all scripts and styles
* Redirects attachment pages to parent pages and set to 404
* Redirects author/date pages to 404.

~~~php
// Cleaning up WordPress at instantiation.
\WPS\WP\WordPressCleanup::get_instance( 'all' );

// Cleaning up the public at instantiation item by item.
\WPS\WP\WordPressCleanup::get_instance( [
   'links'        => 'all',
   'widgets'      => 'all',
   'post_formats' => 'all',
] );

// Cleaning up the public object-orientedly.
$public_cleanup = \WPS\WP\WordPressCleanup::get_instance();
$public_cleanup->remove_all();
~~~

### WP Plugins Cleanup

WordPress Plugins Cleanup does the following:
* Inherits and does all the WordPress Cleanup items above.
* Safely supports the following plugins:
    * Yoast/WordPress SEO - metabox priority, Removes Yoast Comments
    * Restricted Content Pro metabox priority
    * Envira Gallery license check on non-plugins admin page
    * Soliloquy license check on non-plugins admin page

~~~php
// Cleaning up WordPress at instantiation.
\WPS\WP\WordPressPluginsCleanup::get_instance( 'all' );

// Cleaning up the public at instantiation item by item.
\WPS\WP\WordPressPluginsCleanup::get_instance( [
   'links'        => 'all',
   'widgets'      => 'all',
   'post_formats' => 'all',
] );

// Cleaning up the public object-orientedly.
$public_cleanup = \WPS\WP\WordPressPluginsCleanup::get_instance();
$public_cleanup->remove_all();
~~~

### All Cleanup

This does all of the above.

~~~php
// Cleaning up WordPress at instantiation.
\WPS\WP\WordPressPluginsCleanup::get_instance( 'all' );

// Cleaning up the public at instantiation item by item.
\WPS\WP\WordPressPluginsCleanup::get_instance( [
   'links'        => 'all',
   'widgets'      => 'all',
   'post_formats' => 'all',
] );

// Cleaning up the public object-orientedly.
$public_cleanup = \WPS\WP\WordPressPluginsCleanup::get_instance();
$public_cleanup->remove_all();
~~~

## Change Log

See the [change log](CHANGELOG.md).

## License

[GPL 2.0 or later](LICENSE).

## Contributions

Contributions are welcome - fork, fix and send pull requests against the `master` branch please.

## Credits

Built by [Travis Smith](https://twitter.com/wp_smith)  
Copyright 2013-2020 [Travis Smith](https://wpsmith.net)