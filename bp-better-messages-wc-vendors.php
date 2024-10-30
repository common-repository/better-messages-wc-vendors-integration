<?php
/*
    @wordpress-plugin
    Plugin Name: Better Messages - Integration for WC Vendors Marketplace
    Plugin URI: https://www.wordplus.org
    Description: Better Messages - Integration for WC Vendors Marketplace
    Version: 1.0.10
    Author: WordPlus
    Author URI: https://www.wordplus.org
    License: GPL2
    Text Domain: better-messages-wc-vendors
    Domain Path: /languages
*/

if ( ! class_exists( 'BP_Better_Messages_WC_Vendors_Integration' ) && ! function_exists( 'bbmwv_fs' ) ) {
    class BP_Better_Messages_WC_Vendors_Integration
    {
        public $realtime;
        public $version = '1.0.10';
        public $path;
        public $url;

        /** @var BP_Better_Messages_WC_Vendors_Integration_Hooks $hooks */
        public  $hooks ;

        /** @var BP_Better_Messages_WC_Vendors_Integration_Functions $functions */
        public $functions;

        public static function instance()
        {
            // Store the instance locally to avoid private static replication
            static  $instance = null ;
            // Only run these methods if they haven't been run previously

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_WC_Vendors_Integration();
                $instance->setup_vars();
                $instance->setup_actions_classes();
            }

            // Always return the instance
            return $instance;
            // The last metroid is in captivity. The galaxy is at peace.
        }

        public function admin_notice(){
            $new_license      = bbmwv_fs()->get_upgrade_url();
            $activate_license = bbmwv_fs()->get_activation_url();
            echo '<div class="notice notice-error">';
            echo '<p><b>BP Better Messages WC Vendors Integration</b> missing license. To get new license press <a href="'.$new_license.'">here</a>. To activate existing license press <a href="'.$activate_license.'">here</a>.</p>';
            echo '</div>';
        }

        public function setup_vars()
        {
            $this->path = plugin_dir_path( __FILE__ );
            $this->url  = plugin_dir_url( __FILE__ );
        }

        /**
         * Require necessary files
         */
        public function require_files()
        {
            require_once 'inc/hooks.php';
            require_once 'inc/functions.php';

        }

        public function setup_actions_classes()
        {
            $this->require_files();
            $this->hooks     = BP_Better_Messages_WC_Vendors_Integration_Hooks();
            $this->functions = BP_Better_Messages_WC_Vendors_Integration_Functions();
        }

    }

    function BP_Better_Messages_WC_Vendors_Integration()
    {
        return BP_Better_Messages_WC_Vendors_Integration::instance();
    }

    function bbmwv_fs() {
        global $bbmwv_fs;

        if ( ! isset( $bbmwv_fs ) ) {
            // Activate multisite network integration.
            if ( ! defined( 'WP_FS__PRODUCT_8691_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_8691_MULTISITE', true );
            }

            // Include Freemius SDK.
            if ( file_exists( dirname( dirname( __FILE__ ) ) . '/bp-better-messages/inc/freemius/start.php' ) ) {
                // Try to load SDK from parent plugin folder.
                require_once dirname( dirname( __FILE__ ) ) . '/bp-better-messages/inc/freemius/start.php';
            } else if ( file_exists( dirname( dirname( __FILE__ ) ) . '/bp-better-messages-premium/inc/freemius/start.php' ) ) {
                // Try to load SDK from premium parent plugin folder.
                require_once dirname( dirname( __FILE__ ) ) . '/bp-better-messages-premium/inc/freemius/start.php';
            } else {
                require_once dirname(__FILE__) . '/inc/freemius/start.php';
            }

            $bbmwv_fs = fs_dynamic_init( array(
                'id'                  => '8691',
                'slug'                => 'better-messages-wc-vendors-integration',
                'type'                => 'plugin',
                'public_key'          => 'pk_81572487ef5c67b080edb79788813',
                'is_premium'          => false,
                'has_paid_plans'      => false,
                'is_org_compliant'    => false,
                'parent'              => array(
                    'id'         => '1557',
                    'slug'       => 'bp-better-messages',
                    'public_key' => 'pk_8af54172153e9907893f32a4706e2',
                    'name'       => 'BP Better Messages',
                ),
                'menu'                => array(
                    'first-path'     => 'plugins.php',
                    'support'        => false,
                ),
            ) );
        }

        return $bbmwv_fs;
    }


    function bbmwv_fs_is_parent_active_and_loaded() {
        // Check if the parent's init SDK method exists.
        return function_exists( 'bpbm_fs' );
    }

    function bbmwv_fs_is_parent_active() {
        $active_plugins = get_option( 'active_plugins', array() );

        if ( is_multisite() ) {
            $network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
            $active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
        }

        foreach ( $active_plugins as $basename ) {
            if ( 0 === strpos( $basename, 'bp-better-messages/' ) ||
                0 === strpos( $basename, 'bp-better-messages-premium/' )
            ) {
                return true;
            }
        }

        return false;
    }

    function bbmwv_fs_init() {
        if ( bbmwv_fs_is_parent_active_and_loaded() ) {
            // Init Freemius.
            bbmwv_fs();

            // Signal that the add-on's SDK was initiated.
            do_action( 'bbmwv_fs_loaded' );

            // Parent is active, add your init code here.

        } else {
            // Parent is inactive, add your error handling here.
        }
    }

    add_action( 'plugins_loaded', 'BP_Better_Messages_WC_Vendors_Integration_Init', 30 );

    function BP_Better_Messages_WC_Vendors_Integration_Init()
    {
        if (bbmwv_fs_is_parent_active_and_loaded()) {
            // If parent already included, init add-on.
            bbmwv_fs_init();
            BP_Better_Messages_WC_Vendors_Integration();
        } else {

            if (bbmwv_fs_is_parent_active()) {
                // Init add-on only after the parent is loaded.
                add_action('bbm_fs_loaded', 'bbmwv_fs_init');
                BP_Better_Messages_WC_Vendors_Integration();
            } else {
                // Even though the parent is not activated, execute add-on for activation / uninstall hooks.
                bbmwv_fs_init();
            }
        }
    }
}
