<?php
/**
 * Plugin Name: WooCommerce Simplify Commerce Gateway
 * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-simplify-commerce/
 * Description: The Simplify Commerce gateway lets you to take credit card payments directly on your store. Requires PHP 5.3+
 * Author: Automattic
 * Author URI: http://woothemes.com/
 * Version: 1.0.2
 *
 * Copyright (c) 2016 Automattic
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required minimums
 */
define( 'WC_SIMPLIFY_COMMERCE_MIN_PHP_VER', '5.3.0' );
define( 'WC_SIMPLIFY_COMMERCE_MIN_WC_VER', '2.6.0' );
define( 'WC_SIMPLIFY_COMMERCE_FILE', __FILE__ );

class WC_Gateway_Simplify_Commerce_Loader {

	/**
	 * @var Singleton The reference the *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup() {}

	/** @var whether or not we need to load code for / support subscriptions */
	private $subscription_support_enabled = false;

	/**
	 * Notices (array)
	 * @var array
	 */
	public $notices = array();

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init the plugin after plugins_loaded so environment variables are set.
	 */
	public function init() {
		// Don't hook anything else in the plugin if we're in an incompatible environment
		if ( self::get_environment_warning() ) {
			return;
		}

		// Init the gateway itself
		$this->init_gateways();

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication)
	 */
	public function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message
		);
	}

	/**
	 * The primary sanity check, automatically disable the plugin on activation if it doesn't
	 * meet minimum requirements.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 */
	public static function activation_check() {
		$environment_warning = self::get_environment_warning( true );
		if ( $environment_warning ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( $environment_warning );
		}
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation.
	 */
	public function check_environment() {
		$environment_warning = self::get_environment_warning();
		if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$this->add_admin_notice( 'bad_environment', 'error', $environment_warning );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}

	/**
	 * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
	 * found or false if the environment has no problems.
	 */
	static function get_environment_warning( $during_activation = false ) {

		if ( version_compare( phpversion(), WC_SIMPLIFY_COMMERCE_MIN_PHP_VER, '<' ) ) {
			if ( $during_activation ) {
				$message = __( 'The plugin could not be activated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-simplify-commerce', 'woocommerce-gateway-simplify-commerce' );
			} else {
				$message = __( 'The WooCommerce Simplify Commerce plugin has been deactivated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-simplify-commerce' );
			}
			return sprintf( $message, WC_SIMPLIFY_COMMERCE_MIN_PHP_VER, phpversion() );
		}

		if ( version_compare( WC_VERSION, WC_SIMPLIFY_COMMERCE_MIN_WC_VER, '<' ) ) {
			if ( $during_activation ) {
				$message = __( 'The plugin could not be activated. The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-simplify-commerce', 'woocommerce-gateway-simplify-commerce' );
			} else {
				$message = __( 'The WooCommerce Simplify Commerce plugin has been deactivated. The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-simplify-commerce' );
			}
			return sprintf( $message, WC_SIMPLIFY_COMMERCE_MIN_WC_VER, WC_VERSION );
		}

		if ( ! function_exists( 'curl_init' ) ) {
			if ( $during_activation ) {
				return __( 'The plugin could not be activated. cURL is not installed.', 'woocommerce-gateway-simplify-commerce' );
			}
			return __( 'The WooCommerce Simplify Commerce plugin has been deactivated. cURL is not installed.', 'woocommerce-gateway-simplify-commerce' );
		}

		return false;
	}

	/**
	 * Adds plugin action links
	 *
	 * @since 1.0.0
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=simplify_commerce' ) . '">' . __( 'Settings', 'woocommerce-gateway-simplify-commerce' ) . '</a>',
			'<a href="https://docs.woothemes.com/document/simplify-commerce/">' . __( 'Docs', 'woocommerce-gateway-simplify-commerce' ) . '</a>',
			'<a href="http://support.woothemes.com/">' . __( 'Support', 'woocommerce-gateway-simplify-commerce' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Display any notices we've collected thus far (e.g. for connection, disconnection)
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo "<div class='" . esc_attr( sanitize_html_class( $notice['class'] ) ) . "'><p>";
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
			echo "</p></div>";
		}
	}

	/**
	 * Initialize the gateway. Called very early - in the context of the plugins_loaded action
	 *
	 * @since 1.0.0
	 */
	public function init_gateways() {
		if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
			$this->subscription_support_enabled = true;
		}

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		require_once( plugin_basename( 'includes/class-wc-gateway-simplify-commerce.php' ) );

		load_plugin_textdomain( 'woocommerce-gateway-simplify-commerce', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );

		if ( $this->subscription_support_enabled ) {
			require_once( plugin_basename( 'includes/class-wc-addons-gateway-simplify-commerce.php' ) );
		}
	}

	/**
	 * Add the gateways to WooCommerce
	 *
	 * @since 1.0.0
	 */
	public function add_gateways( $methods ) {
		if ( $this->subscription_support_enabled ) {
			$methods[] = 'WC_Addons_Gateway_Simplify_Commerce';
		} else {
			$methods[] = 'WC_Gateway_Simplify_Commerce';
		}
		return $methods;
	}

	/**
	 * What rolls down stairs
	 * alone or in pairs,
	 * and over your neighbor's dog?
	 * What's great for a snack,
	 * And fits on your back?
	 * It's log, log, log
	 *
	 * @since 1.0.0
	 */
	public function log( $context, $message ) {
		if ( empty( $this->log ) ) {
			$this->log = new WC_Logger();
		}

		$this->log->add( 'woocommerce-gateway-simplify-commerce', $context . " - " . $message );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $context . " - " . $message );
		}
	}
}

$GLOBALS['wc_gateway_simplify_commerce_loader'] = WC_Gateway_Simplify_Commerce_Loader::get_instance();
register_activation_hook( __FILE__, array( 'WC_Gateway_Simplify_Commerce_Loader', 'activation_check' ) );
