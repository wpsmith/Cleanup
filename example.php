<?php

namespace WPS\WP\MyPlugin;

require_once 'src/CleanupAdmin.php';

// Cleaning up the admin object-orientedly.
$admin_cleanup = \WPS\WP\CleanupAdmin::get_instance();
$admin_cleanup->remove_all();

require_once 'src/CleanupPublic.php';

// Cleaning up the public object-orientedly.
$public_cleanup = \WPS\WP\CleanupPublic::get_instance();
$public_cleanup->remove_all();

require_once 'src/WordPressCleanup.php';

// Cleaning up WordPress output object-orientedly.
$public_cleanup = \WPS\WP\WordPressCleanup::get_instance();
$public_cleanup->remove_all();

require_once 'src/WordPressPluginsCleanup.php';

// Cleaning up WordPress Plugins stuff object-orientedly.
$public_cleanup = \WPS\WP\WordPressPluginsCleanup::get_instance();
$public_cleanup->remove_all();

require_once 'src/WordPressPluginsCleanup.php';

// Cleaning up all the things object-orientedly.
$public_cleanup = \WPS\WP\WordPressPluginsCleanup::get_instance();
$public_cleanup->remove_all();