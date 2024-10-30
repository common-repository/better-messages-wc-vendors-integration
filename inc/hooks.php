<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_WC_Vendors_Integration_Hooks' ) ):

    class BP_Better_Messages_WC_Vendors_Integration_Hooks
    {

        public static function instance()
        {

            static $instance = null;

            if (null === $instance) {
                $instance = new BP_Better_Messages_WC_Vendors_Integration_Hooks();
            }

            return $instance;
        }


        public function __construct()
        {
            /**
             * WC Vendors plugin
             */
             add_action('wcvendors_settings_after_shop_description', array( $this, 'wc_vendors_after_shop_description' ), 100 );
             add_action('wcvendors_pro_store_settings_saved', array( $this, 'wc_vendors_save_settings_frontend' ), 10 );
             add_action('wcv_store_settings_saved', array( $this, 'wc_vendors_save_settings_frontend' ), 10 );

             add_action('woocommerce_after_add_to_cart_button', array( $this, 'wc_vendors_product_page_contact_button' ), 10 );

            // Hook into the navigation
            add_filter( 'wcv_pro_dashboard_urls', array( $this, 'add_messages_page_nav' ), 9 );

            // Create the custom page
            add_filter( 'wcv_pro_dashboard_custom_page',  array( $this, 'messages_page' ), 10, 4 );

            add_filter( 'bp_better_messages_page', array( $this, 'messages_page_for_vendors'), 10, 2 );

            add_action('wp_enqueue_scripts', array( $this, 'inbox_counter_javascript' ) );

            if( ! class_exists('BuddyPress') ) {
                add_filter('bp_core_get_userlink', array($this, 'bp_core_get_userlink'), 10, 2);
            }
        }

        public function inbox_counter_javascript(){
            if( ! is_user_logged_in() ) return false;
            $enabled = BP_Better_Messages_WC_Vendors_Integration()->functions->is_messages_enabled( get_current_user_id() );
            if( $enabled === 'enabled' ) {
                ob_start(); ?>
                    jQuery(document).on('bp-better-messages-update-unread', function( event ) {
                    var unread = parseInt(event.detail.unread);
                        var element = jQuery('.wcvendors-pro-dashboard-wrapper .wcv-navigation #dashboard-menu-item-messages a .bp-better-messages-unread');
                        if( element.length === 0 ){
                            jQuery('<span class="bp-better-messages-unread bpbmuc bpbmuc-hide-when-null" data-count="' + unread + '">' + unread + '</span>').appendTo(jQuery('.wcvendors-pro-dashboard-wrapper .wcv-navigation #dashboard-menu-item-messages a'));
                        }
                    });
                <?php
                $js = ob_get_clean();

                if( trim( $js ) !== '' ){
                    if( class_exists('Better_Messages') ) {
                        wp_add_inline_script('better-messages', BP_Better_Messages()->functions->minify_js($js), 'before');
                    } else {
                        wp_add_inline_script( 'bp_messages_js', BP_Better_Messages()->functions->minify_js( $js ), 'before' );
                    }
                }

                ob_start(); ?>
                .wcvendors-pro-dashboard-wrapper .wcv-navigation #dashboard-menu-item-messages a .bp-better-messages-unread{
                    margin-left: 10px;
                }
                <?php
                $css = ob_get_clean();

                if( trim( $css ) !== '' ){

                    if( class_exists('Better_Messages') ) {
                        wp_add_inline_style('better-messages', BP_Better_Messages()->functions->minify_css($css));
                    } else {
                        wp_add_inline_style('bp-messages', BP_Better_Messages()->functions->minify_css($css));
                    }
                }
            }
        }

        public function bp_core_get_userlink( $link, $user_id ){
            if( ! class_exists('WCV_Vendors') ) return $link;
            if( ! WCV_Vendors::is_vendor( $user_id ) ) return $link;

            return WCV_Vendors::get_vendor_shop_page( $user_id );
        }

        public function messages_page_for_vendors( $url, $user_id ){
            if( ! is_user_logged_in() ) return $url;
            if( ! class_exists('WCV_Vendors') ) return $url;
            if( ! WCV_Vendors::is_vendor( $user_id ) ) return $url;

            $enabled = BP_Better_Messages_WC_Vendors_Integration()->functions->is_messages_enabled( $user_id );

            if( $enabled === 'enabled' ) {
                $pro_dashboard_pages   = (array) get_option( 'wcvendors_dashboard_page_id', array() );
                if( ! empty( $pro_dashboard_pages ) ) {
                    $dashboard_page_id = $pro_dashboard_pages[0];
                    $permalink         = get_permalink($dashboard_page_id);
                    $url = trailingslashit($permalink) . 'messages/';
                }
            }

            return $url;
        }

        function add_messages_page_nav( $pages ){
            $vendor_id = get_current_user_id();
            $enabled = BP_Better_Messages_WC_Vendors_Integration()->functions->is_messages_enabled( $vendor_id );
            if( $enabled !== 'enabled' ) return $pages;

            $pages[ 'messages' ] = array(
                'slug'    => 'messages',
                'id'      => 'messages',
                'label'   => __( 'Messages', 'better-messages-wc-vendors-integration' ),
                'actions' => array(
                    'edit'      => __( 'New', 'wcvendors-pro' ),
                ),
            );

            return $pages;
        }

        function messages_page( $object, $object_id, $template, $custom ){
            if ( 'messages' === $object ){
                echo BP_Better_Messages()->functions->get_page();
            }
        }


        public function wc_vendors_product_page_contact_button(){
            global $authordata;

            $vendor_id = $authordata->ID;
            $bpbm_wc_vendors = get_user_meta( $vendor_id, 'bpbm_wc_vendors', true );
            if( empty($bpbm_wc_vendors) ) {
                $bpbm_wc_vendors = apply_filters('bp_better_messages_wc_vendors_default', 'disabled');
            }

            if( $bpbm_wc_vendors === 'enabled' ){
                $product_title = get_the_title();
                $user_id = $vendor_id; #Here you need to get user_id from somewhere depending on the environment
                $user = get_userdata($user_id);
                $nice_name = $user->user_nicename;
                $text = sprintf(__('Have question regarding your product %s', 'better-messages-wc-vendors-integration'), $product_title);

                $subject = urlencode($text);
                $message = urlencode($text);
                $label   = __('Ask a Question', 'better-messages-wc-vendors-integration');

                if( class_exists('Better_Messages' ) ){
                    $link = Better_Messages()->functions->add_hash_arg('new-conversation', [
                        'to'      => $user->ID,
                        'subject' => $subject,
                        'message' => $message
                    ], Better_Messages()->functions->get_link( get_current_user_id() ) );
                } else {
                    $link = BP_Better_Messages()->functions->get_link(get_current_user_id()) . '?new-message&to=' . $nice_name . '&subject=' . $subject . '&message=' . $message;
                }

                echo '<a href="' . esc_url($link) .  '" class="bpbm-pm-button wc-vendors-pm">' . esc_attr($label) . '</a>';
            }

        }

        public function wc_vendors_save_settings_frontend( $user_id ){
            if( isset( $_POST['bpbm_wc_vendors'] )){
                $value = $_POST['bpbm_wc_vendors'];

                if( $value !== 'enabled' && $value !== 'disabled' ){
                    $value = apply_filters('bp_better_messages_wc_vendors_default', 'disabled');
                }

                update_user_meta( $user_id, 'bpbm_wc_vendors', $value );
            }

            return true;
        }

        public function wc_vendors_after_shop_description(){
            if( is_admin() ) {
            } else {
                $vendor_id = get_current_user_id();
                $bpbm_wc_vendors = get_user_meta( $vendor_id, 'bpbm_wc_vendors', true );
                if( empty($bpbm_wc_vendors) ) {
                    $bpbm_wc_vendors = apply_filters('bp_better_messages_wc_vendors_default', 'disabled');
                }
                ?>
                <div class="pv_shop_bpbm_messages">
                    <p>
                        <b><?php _e('Messages', 'better-messages-wc-vendors-integration'); ?></b>
                        <br>
                        <?php _e('Allow buyers to ask questions regarding your product using private messaging system', 'better-messages-wc-vendors-integration'); ?>
                    </p>
                    <p>
                        <label for="bpbm_wc_vendors_enabled"><input id="bpbm_wc_vendors_enabled" type="radio" name="bpbm_wc_vendors" value="enabled" <?php checked($bpbm_wc_vendors, 'enabled'); ?>> Enabled</label>
                        <br>
                        <label for="bpbm_wc_vendors_disabled"><input id="bpbm_wc_vendors_disabled" type="radio" name="bpbm_wc_vendors" value="disabled" <?php checked($bpbm_wc_vendors, 'disabled'); ?>> Disabled</label>
                    </p>
                </div>
            <?php }
        }

    }


    function BP_Better_Messages_WC_Vendors_Integration_Hooks(){
        return BP_Better_Messages_WC_Vendors_Integration_Hooks::instance();
    }

endif;
