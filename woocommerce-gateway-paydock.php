<?php
/*
Plugin Name: Woocommerce Gateway PayDock
Plugin URI:
Description: WooCommerce Gateway PayDock
Author: Edward Bruin
Version: 1.0.2
Author URI:
*/
if ( !defined('WP_DEBUG_LOG') ) define('WP_DEBUG_LOG', true);
// error_log('hello number 2');

// Exit if executed directl
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WC_Dependencies' ) )
    require_once 'class-wc-dependencies.php';

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
    function is_woocommerce_active() {
        return WC_Dependencies::woocommerce_active_check();
    }
}

if ( is_woocommerce_active() ) {

    //current plugin version
    define( 'WOOPAYDOCK_VER', '1.0.2' );

    // The text domain for strings localization
    define( 'WOOPAYDOCKTEXTDOMAIN', 'woocommerce-gateway-paydock' );

    if ( !class_exists( 'WOOPAYDOCK' ) ) {

        class WOOPAYDOCK {

            var $plugin_dir;
            var $plugin_url;

            public function __clone() {
                _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.1' );
            }

            public function __wakeup() {
                _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.1' );
            }

            /**
             * Main constructor
             **/
            function __construct() {

                //setup proper directories
                if ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/woocommerce-gateway-paydock.php' ) ) {
                    $this->plugin_dir = WPMU_PLUGIN_DIR . '/woocommerce-gateway-paydock/';
                    $this->plugin_url = WPMU_PLUGIN_URL . '/woocommerce-gateway-paydock/';
                } else if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/woocommerce-gateway-paydock/woocommerce-gateway-paydock.php' ) ) {
                    $this->plugin_dir = WP_PLUGIN_DIR . '/woocommerce-gateway-paydock/';
                    $this->plugin_url = WP_PLUGIN_URL . '/woocommerce-gateway-paydock/';
                } else if ( defined('WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/woocommerce-gateway-paydock.php' ) ) {
                    $this->plugin_dir = WP_PLUGIN_DIR;
                    $this->plugin_url = WP_PLUGIN_URL;
                }

                register_activation_hook( $this->plugin_dir . 'woocommerce-gateway-paydock.php', array( &$this, 'activation' ) );

                load_plugin_textdomain( WOOPAYDOCKTEXTDOMAIN, false, dirname( 'woocommerce-gateway-paydock/woocommerce-gateway-paydock.php' ) . '/languages/' );

                add_filter( 'woocommerce_payment_gateways',  array( $this, 'add_gateways' ) );
            }

            /**
             * Run Activated funtions
             */
            function activation() {
                add_option( 'woopaydock_ver', WOOPAYDOCK_VER );
            }

            /**
             * Add gateways to WC
             *
             * @param  array $methods
             * @return array of methods
             */
            public function add_gateways( $methods ) {
                include_once( $this->plugin_dir . 'class-paydock.php' );

                $methods[] = 'WCPayDockGateway';

                return $methods;
            }

            //end class
        }

    }

    /**
     * function to initiate plugin
     */
    function init_woopaydock() {

        //checking for version required
        if ( ! version_compare( paydock_get_wc_version(), '2.6.0', '>=' ) ) {
            add_action( 'admin_notices', 'woopaydock_rec_ver_notice', 5 );
            function woopaydock_rec_ver_notice() {
                if ( current_user_can( 'install_plugins' ) )
                    echo '<div class="error fade"><p>Sorry, but for this version of <b>Woocommerce Gateway PayDock</b> is required version of the <b>WooCommerce</b> not lower than <b>2.6.0</b>. <br />Please update <b>WooCommerce</b> to latest version.</span></p></div>';
            }

        } else {
            $GLOBALS['woopaydock'] = new WOOPAYDOCK();
        }
    }

    function paydock_get_wc_version() {
        if ( defined( 'WC_VERSION' ) && WC_VERSION )
            return WC_VERSION;
        if ( defined( 'WOOCOMMERCE_VERSION' ) && WOOCOMMERCE_VERSION )
            return WOOCOMMERCE_VERSION;
        return null;
    }

    add_action( 'plugins_loaded', 'init_woopaydock' );
}