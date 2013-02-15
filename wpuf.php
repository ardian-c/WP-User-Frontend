<?php

/*
Plugin Name: WP User Frontend
Plugin URI: http://tareq.wedevs.com/2011/01/new-plugin-wordpress-user-frontend/
Description: Post, Edit, Delete posts and edit profile without coming to backend
Author: Tareq Hasan
Version: 1.1
Author URI: http://tareq.weDevs.com
*/

if ( !class_exists( 'WeDevs_Settings_API' ) ) {
    require_once dirname( __FILE__ ) . '/lib/class.settings-api.php';
}

require_once 'wpuf-functions.php';
require_once 'admin/settings-options.php';

if ( is_admin() ) {
    require_once 'admin/settings.php';
    require_once 'admin/forms.php';
}

require_once 'lib/form-add.php';
require_once 'lib/upload.php';

 require_once 'wpuf-dashboard.php';
 require_once 'wpuf-editprofile.php';
 require_once 'wpuf-edit-user.php';

class WP_User_Frontend {

    function __construct() {
//        register_activation_hook( __FILE__, array($this, 'install') );
        register_deactivation_hook( __FILE__, array($this, 'uninstall') );

        add_action( 'admin_init', array($this, 'block_admin_access') );

        add_action( 'init', array($this, 'load_textdomain') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
    }

    /**
     * Create tables on plugin activation
     *
     * @global object $wpdb
     */
    function install() {
        global $wpdb;

        flush_rewrite_rules( false );

        $sql_custom = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpuf_customfields (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `field` varchar(30) NOT NULL,
         `type` varchar(20) NOT NULL,
         `values` text NOT NULL,
         `label` varchar(200) NOT NULL,
         `desc` varchar(200) NOT NULL,
         `required` varchar(5) NOT NULL,
         `region` varchar(20) NOT NULL DEFAULT 'top',
         `order` int(1) NOT NULL,
         PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $sql_subscription = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpuf_subscription (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `count` int(5) DEFAULT '0',
        `duration` int(5) NOT NULL DEFAULT '0',
        `cost` float NOT NULL DEFAULT '0',
        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

        $sql_transaction = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpuf_transaction (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `user_id` bigint(20) DEFAULT NULL,
        `status` varchar(255) NOT NULL DEFAULT 'pending_payment',
        `cost` varchar(255) DEFAULT '',
        `post_id` bigint(20) DEFAULT NULL,
        `pack_id` bigint(20) DEFAULT NULL,
        `payer_first_name` longtext,
        `payer_last_name` longtext,
        `payer_email` longtext,
        `payment_type` longtext,
        `payer_address` longtext,
        `transaction_id` longtext,
        `created` datetime NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

        $wpdb->query( $sql_custom );
        $wpdb->query( $sql_subscription );
        $wpdb->query( $sql_transaction );
    }

    function uninstall() {

    }

    /**
     * Enqueues Styles and Scripts when the shortcodes are used only
     *
     * @uses has_shortcode()
     * @since 0.2
     */
    function enqueue_scripts() {
        $path = plugins_url( '', __FILE__ );

        //for multisite upload limit filter
        if ( is_multisite() ) {
            require_once ABSPATH . '/wp-admin/includes/ms.php';
        }

        require_once ABSPATH . '/wp-admin/includes/template.php';

        // wp_enqueue_style( 'wpuf', $path . '/css/wpuf.css' );
        wp_enqueue_style( 'wpuf-css', $path . '/css/frontend-forms.css' );

        wp_enqueue_script( 'jquery-validate', $path . '/js/jquery.validate.min.js', array('jquery') );
        wp_enqueue_script( 'wpuf-form', $path . '/js/frontend-form.js', array('jquery', 'plupload-handlers') );

        wp_localize_script( 'wpuf-form', 'wpuf_frontend', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'error_message' => __( 'Please fix the errors to proceed', 'wpuf' )
        ) );
    }

    /**
     * Block user access to admin panel for specific roles
     *
     * @global string $pagenow
     */
    function block_admin_access() {
        global $pagenow;

        $access_level = wpuf_get_option( 'admin_access' );
        $valid_pages = array('admin-ajax.php', 'async-upload.php', 'media-upload.php');

        if ( !current_user_can( $access_level ) && !in_array( $pagenow, $valid_pages ) ) {
            wp_die( __( 'Access Denied. Your site administrator has blocked your access to the WordPress back-office.', 'wpuf' ) );
        }
    }

    /**
     * Load the translation file for current language.
     *
     * @since version 0.7
     * @author Tareq Hasan
     */
    function load_textdomain() {
        $locale = apply_filters( 'wpuf_locale', get_locale() );
        $mofile = dirname( __FILE__ ) . "/languages/wpuf-$locale.mo";

        if ( file_exists( $mofile ) ) {
            load_textdomain( 'wpuf', $mofile );
        }
    }

    /**
     * The main logging function
     *
     * @uses error_log
     * @param string $type type of the error. e.g: debug, error, info
     * @param string $msg
     */
    public static function log( $type = '', $msg = '' ) {
        if ( WP_DEBUG == true ) {
            $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
            error_log( $msg, 3, dirname( __FILE__ ) . '/log.txt' );
        }
    }

}

$wpuf = new WP_User_Frontend();
