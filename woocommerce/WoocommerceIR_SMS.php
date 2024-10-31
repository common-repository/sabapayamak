<?php

namespace SabaPayamak;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SABAPAYAMAK_WOO_VERSION' ) ) {
	define( 'SABAPAYAMAK_WOO_VERSION', '4.3.0' );
}

if ( ! defined( 'SABAPAYAMAK_WOO_URL' ) ) {
	define( 'SABAPAYAMAK_WOO_URL', plugins_url( '', __FILE__ ) );
}

if ( ! defined( 'SABAPAYAMAK_WOO_INCLUDE_DIR' ) ) {
	define( 'SABAPAYAMAK_WOO_INCLUDE_DIR', dirname( __FILE__ ) . '/includes' );
}

require_once 'includes/class-gateways.php';
require_once 'includes/class-settings-api.php';
require_once 'includes/class-settings.php';
require_once 'includes/class-helper.php';
require_once 'includes/class-bulk.php';
require_once 'includes/class-metabox.php';
require_once 'includes/class-subscription.php';
require_once 'includes/class-product-tab.php';
require_once 'includes/class-product-events.php';
require_once 'includes/class-orders.php';
require_once 'includes/class-archive.php';
require_once 'includes/class-contacts.php';

require_once 'includes/class-deprecateds.php';