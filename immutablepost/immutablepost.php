<?php
/**
 * Plugin Name: Immutable Post
 * Version: 1.0.0
 * Plugin URI: http://www.increaseo.com
 * Description: Publish an Immutable Post on the Ethereum Blockchain
 * Author: Increaseo
 * Author URI: http://increaseo.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: immutablepost
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author I
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-immutablepost.php';
require_once 'includes/class-immutablepost-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-immutablepost-admin-api.php';
require_once 'includes/lib/class-immutablepost-post-type.php';
require_once 'includes/lib/class-immutablepost-taxonomy.php';

/**
 * Returns the main instance of ImmutablePost to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object ImmutablePost
 */
function immutablepost() {
	$instance = ImmutablePost::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = ImmutablePost_Settings::instance( $instance );
	}

	return $instance;
}



immutablepost();
