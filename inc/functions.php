<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_WC_Vendors_Integration_Functions' ) ):

    class BP_Better_Messages_WC_Vendors_Integration_Functions
    {

        public static function instance()
        {

            static $instance = null;

            if (null === $instance) {
                $instance = new BP_Better_Messages_WC_Vendors_Integration_Functions();
            }

            return $instance;
        }


        public function __construct()
        {
        }

        public function is_messages_enabled( $vendor_id ){

            $bpbm_wc_vendors = get_user_meta( $vendor_id, 'bpbm_wc_vendors', true );

            if( empty($bpbm_wc_vendors) ) {
                $bpbm_wc_vendors = apply_filters('bp_better_messages_wc_vendors_default', 'disabled');
            }

            return $bpbm_wc_vendors;
        }

    }


    function BP_Better_Messages_WC_Vendors_Integration_Functions(){
        return BP_Better_Messages_WC_Vendors_Integration_Functions::instance();
    }

endif;