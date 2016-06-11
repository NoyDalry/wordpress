<?php
/*
Plugin Name: Wedding support
Plugin URI:
Description: This plugin is made for wedding site
Version: 1.0.0
Author: Gaun Yun
Author URI:

* LICENSE
    Copyright 2016 Gaun Yun  (email : yyccii412@gmail.com)
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'Wedding_Support' ) ) {
    define( 'CRON_INTERVAL', 10 );

    register_activation_hook(__FILE__, array('Wedding_Support', 'install'));
    register_deactivation_hook(__FILE__, array('Wedding_Support', 'deactivate'));
    register_uninstall_hook(__FILE__, array('Wedding_Support', 'uninstall'));

    class Wedding_Support
    {
        public $version = '1.0.0';
        public static $plugin_dir;
        public static $plugin_url;

        function __construct() {
            self::$plugin_dir = plugin_dir_path(__FILE__);
            self::$plugin_url = plugins_url('', __FILE__);

            add_action( 'init', array( &$this, 'create_supplier_post_type' ) );
            add_action( 'admin_init', array( &$this, 'supplier_post_meta' ) );

            add_action( 'save_post',  array( &$this, 'save_supplier_fields' ), 100, 2 );
            add_filter( 'template_include', array( &$this, 'supplier_single_template' ), 1 );

            add_action( 'wp_enqueue_scripts', array( &$this, 'load_front_scripts' ) );
            add_action( 'admin_enqueue_scripts', array( &$this, 'load_backend_scripts' ) );

            add_action( 'wp_ajax_register_supplier', array( &$this, 'register_supplier' ) );
            add_action( 'wp_ajax_nopriv_register_supplier', array( &$this, 'register_supplier' ) );
            add_action( 'wp_ajax_upload_file', array( &$this, 'upload_file' ) );
            add_action( 'wp_ajax_nopriv_upload_file', array( &$this, 'upload_file' ) );
            add_action( 'wp_ajax_check_supplier_valid', array( &$this, 'check_supplier_valid' ) );
            add_action( 'wp_ajax_nopriv_check_supplier_valid', array( &$this, 'check_supplier_valid' ) );
            add_action( 'wp_ajax_pay_supplier', array( &$this, 'pay_supplier' ) );
            add_action( 'wp_ajax_nopriv_pay_supplier', array( &$this, 'pay_supplier' ) );
            add_action( 'wp_ajax_view_free_supplier', array( &$this, 'view_free_supplier' ) );
            add_action( 'wp_ajax_nopriv_view_free_supplier', array( &$this, 'view_free_supplier' ) );

            add_shortcode( 'wedding_search', array( &$this, 'wedding_search_form' ) );
            add_shortcode( 'supplier_register', array( &$this, 'supplier_register_page' ) );

            //Cron job
            add_filter( 'cron_schedules', array( &$this, 'create_new_cron_schedule' ) );
            add_action( 'ws_cron_event', array( &$this, 'check_supplier_payment' ) );
            add_action( 'wp', array( &$this, 'schedule_install' ) );

            /*woocommerce*/
            add_action( 'woocommerce_checkout_order_processed', array( &$this, 'register_supplier_data' ) );
        }

        public function register_supplier_data( $order_id ) {
            global $woocommerce, $wpdb;

            $remove_items = WC()->session->removed_cart_contents;
            foreach( $remove_items as $remove_key => $remove_item ) {
                $remove_id = $remove_item['auto_id'];
                wp_delete_post( $remove_id );
                $wpdb->query( "DELETE FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$remove_id'" );
            }

            $carts = WC()->session->cart;

            foreach( $carts as $cart_key => $cart_val ) {
                update_post_meta( $cart_val['auto_id'], "ws_quantity", $cart_val['quantity'] );
                update_post_meta( $cart_val['auto_id'], "ws_order_id", $order_id );
            }
        }

        public static function install() {
            global $wpdb;

            // create supplier list table
            $creation_query =
                "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}supplier_list_table (
                `id` int(20) NOT NULL AUTO_INCREMENT,
                `post_id` int(31),
                `business_name` varchar(255),
                `company_name` varchar(255),
                `website_address` varchar(255),
                `email_address` varchar(255),
                `phone_number`  varchar(255),
                `address` varchar(255),
                `street` varchar(255),
                `suburb` varchar(255),
                `state`   varchar(255),
                `region`   varchar(255),
                `abn_number` int(31),
                `distance`  int(31),
                `category` varchar(255),
                `owner_name` varchar(255),
                `owner_email` varchar(255),
                `owner_phone`  varchar(255),
                `main_img`  varchar(255),
                `gallery_1` varchar(255),
                `gallery_2` varchar(255),
                `gallery_3` varchar(255),
                `gallery_4`  varchar(255),
                `pay` varchar(255),
                PRIMARY KEY (`id`)
                );";

            $wpdb->query($creation_query);
        }

        public function schedule_install() {
            // Create cron job
            if ( ! wp_next_scheduled( 'ws_cron_event' ) ) {
                wp_schedule_event( time(), 'in_per_one_hour', 'ws_cron_event' );
            }
        }

        public function create_new_cron_schedule( $schedules ){
            $schedules['in_per_one_hour'] = array(
                'interval' => CRON_INTERVAL,
                'display' => 'Once in One minutes'
            );

            return $schedules;
        }

        static function uninstall() {
            //Clear cron job
            wp_clear_scheduled_hook( 'ws_cron_event' );
        }

        static function deactivate() {
            //Clear cron job
            wp_clear_scheduled_hook( 'ws_cron_event' );
        }

        public function load_front_scripts() {
            //For progress bar
            $jquery_version = '1.11.4';
            wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array( 'jquery' ), $jquery_version );
            wp_enqueue_script( 'jquery-ui-script', '//code.jquery.com/ui/' . $jquery_version . '/jquery-ui.js', array( 'jquery' ), $jquery_version );
            wp_enqueue_script( 'jquery-ui-core' );

            wp_register_style( 'supplier-search', plugins_url('css/search-supplier.css', __FILE__) );
            wp_enqueue_style( 'supplier-search' );

            wp_register_style( 'supplier-register', plugins_url('css/register-supplier.css', __FILE__) );
            wp_enqueue_style( 'supplier-register' );

            wp_register_script( 'supplier_search', plugins_url( 'js/search-supplier.js' , __FILE__ ) );
            wp_enqueue_script( 'supplier_search' );
            wp_localize_script('supplier_search', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

            wp_register_script( 'supplier_register', plugins_url( 'js/register-supplier.js' , __FILE__ ) );
            wp_enqueue_script( 'supplier_register', array( 'jquery, jquery-ui-core' ) );
            wp_localize_script('supplier_register', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        }

        public function load_backend_scripts() {
            wp_register_style( 'supplier-backend', plugins_url('css/backend-supplier.css', __FILE__) );
            wp_enqueue_style( 'supplier-backend' );
        }

        public function create_supplier_post_type() {
            register_post_type('wedding_supplier',
                array(
                    'labels' => array(
                        'name' => 'Wedding Supplier',
                        'singular_name' => 'Wedding Supplier',
                        'add_new' => 'Add New',
                        'add_new_item' => 'Add New Wedding Supplier',
                        'edit' => 'Edit',
                        'edit_item' => 'Edit Wedding Supplier',
                        'new_item' => 'New Wedding Supplier',
                        'view' => 'View',
                        'view_item' => 'View Wedding Supplier',
                        'search_items' => 'Search Wedding Supplier',
                        'not_found' => 'No Wedding Supplier found',
                        'not_found_in_trash' => 'No Wedding Supplier found in Trash',
                        'parent' => 'Parent Wedding Supplier',
                    ),
                    'taxonomies' => array(),
                    'public' => true,
                    'menu_position' => 20,
                    'supports' => array('title', 'editor',  'thumbnail'),
                    'has_archive' => true
                )
            );
        }

        public function supplier_post_meta() {
            add_meta_box( 'supplier_details_meta_box', 'Wedding Supplier Details', array( &$this, 'supplier_details_post_meta' ), 'wedding_supplier', 'normal', 'high' );
        }

        public function supplier_details_post_meta( $wedding_supplier ) {
            global $wpdb;
            $supplier_details = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$wedding_supplier->ID'" );
            $supplier_details = empty( $supplier_details ) ? array() : $supplier_details;

            $supplier_payment = get_post_meta( $wedding_supplier->ID, 'pay_statement', true );
            if( empty( $supplier_payment ) ) {
                $supplier_payment = "not yet decide";
            }

            $start_time = get_post_meta( $wedding_supplier->ID, 'ws_start_time', true );
            $end_time = get_post_meta( $wedding_supplier->ID, 'ws_end_time', true );

            $supplier_category = array();
            $supplier_category[0] = '';
            $supplier_category[1] = '';
            $supplier_category[2] = '';
            $supplier_categories = $supplier_details[0]->category;
            $supplier_categories = explode( "--", $supplier_categories );
            foreach( $supplier_categories as $category_key => $category_val ) {
                if( $category_val != "" ) {
                    $supplier_category[$category_key] = $category_val;
                }
            }

            ?>
            <table class="ws-supplier-metabox">
                <tr><td class="ws-metabox-title">Supplier Information</td></tr>
                <tr>
                    <th>Category 1</th>
                    <td><input type='text' size='80' name='supplier_category1' value='<?php echo $supplier_category[0]; ?>'/></td>
                </tr>
                <tr>
                    <th>Category 2</th>
                    <td><input type='text' size='80' name='supplier_category2' value='<?php echo $supplier_category[1]; ?>'/></td>
                </tr>
                <tr>
                    <th>Category 3</th>
                    <td><input type='text' size='80' name='supplier_category3' value='<?php echo $supplier_category[2]; ?>'/></td>
                </tr>
                <tr>
                    <th>Business Name</th>
                    <td><input type='text' size='80' name='supplier_business_name' value='<?php echo $supplier_details[0]->business_name; ?>'/></td>
                </tr>
                <tr>
                    <th>Company Name</th>
                    <td><input type='text' size='80' name='supplier_company_name' value='<?php echo $supplier_details[0]->company_name; ?>'/></td>
                </tr>
                <tr>
                    <th>Website Address</th>
                    <td><input type='text' size='80' name='supplier_website_address' value='<?php echo $supplier_details[0]->website_address; ?>'/></td>
                </tr>
                <tr>
                    <th>Email Address</th>
                    <td><input type='text' size='80' name='supplier_email_address' value='<?php echo $supplier_details[0]->email_address; ?>'/></td>
                </tr>
                <tr>
                    <th>Phone Number</th>
                    <td><input type='text' size='80' name='supplier_phone_number' value='<?php echo $supplier_details[0]->phone_number; ?>'/></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td><input type='text' size='80' name='supplier_address' value='<?php echo $supplier_details[0]->address; ?>'/></td>
                </tr>
                <tr>
                    <th>Street</th>
                    <td><input type='text' size='80' name='supplier_street' value='<?php echo $supplier_details[0]->street; ?>'/></td>
                </tr>
                <tr>
                    <th>Suburb</th>
                    <td><input type='text' size='80' name='supplier_suburb' value='<?php echo $supplier_details[0]->suburb; ?>'/></td>
                </tr>
                <tr>
                    <th>State</th>
                    <td><input type='text' size='80' name='supplier_state' value='<?php echo $supplier_details[0]->state; ?>'/></td>
                </tr>
                <tr>
                    <th>Region</th>
                    <td><input type='text' size='80' name='supplier_region' value='<?php echo $supplier_details[0]->region; ?>'/></td>
                </tr>
                <tr>
                    <th>ABN Number</th>
                    <td><input type='text' size='80' name='supplier_abn_number' value='<?php echo $supplier_details[0]->abn_number; ?>'/></td>
                </tr>
                <tr>
                    <th>Distance Prepared to Travel</th>
                    <td><input type='text' size='80' name='supplier_distance' value='<?php echo $supplier_details[0]->distance; ?>'/></td>
                </tr>
                <tr><td class="ws-metabox-title">Owner Information</td></tr>
                <tr>
                    <th>Contact Person</th>
                    <td><input type='text' size='80' name='supplier_owner_name' value='<?php echo $supplier_details[0]->owner_name; ?>'/></td>
                </tr>
                <tr>
                    <th>Email Address</th>
                    <td><input type='text' size='80' name='supplier_owner_email' value='<?php echo $supplier_details[0]->owner_email; ?>'/></td>
                </tr>
                <tr>
                    <th>Phone Number</th>
                    <td><input type='text' size='80' name='supplier_owner_phone' value='<?php echo $supplier_details[0]->owner_phone; ?>'/></td>
                </tr>
                <tr><td class="ws-metabox-title">Images</td></tr>
                <tr>
                    <th>Main Image</th>
                    <td><input type='text' size='80' name='supplier_main_img' value='<?php echo $supplier_details[0]->main_img; ?>'/></td>
                </tr>
                <tr>
                    <th>Gallery 1</th>
                    <td><input type='text' size='80' name='supplier_gallery0' value='<?php echo $supplier_details[0]->gallery_1; ?>'/></td>
                </tr>
                <tr>
                    <th>Gallery 2</th>
                    <td><input type='text' size='80' name='supplier_gallery1' value='<?php echo $supplier_details[0]->gallery_2; ?>'/></td>
                </tr>
                <tr>
                    <th>Gallery 3</th>
                    <td><input type='text' size='80' name='supplier_gallery2' value='<?php echo $supplier_details[0]->gallery_3; ?>'/></td>
                </tr>
                <tr>
                    <th>Gallery 4</th>
                    <td><input type='text' size='80' name='supplier_gallery3' value='<?php echo $supplier_details[0]->gallery_4; ?>'/></td>
                </tr>
                <tr><td class="ws-metabox-title">Pay Status</td></tr>
                <tr>
                    <th>Pay</th>
                    <td><input type='text' size='80' name='supplier_pay' value='<?php echo $supplier_details[0]->pay; ?>'/></td>
                </tr>
                <tr>
                    <th>Payment</th>
                    <td><input type='text' size='80' name='supplier_pay_statement' value='<?php echo $supplier_payment; ?>'/></td>
                </tr>
                <tr>
                    <th>Start Time</th>
                    <td><input type='text' size='80' name='supplier_start_time' value='<?php echo $start_time; ?>'/></td>
                </tr>
                <tr>
                    <th>End Time</th>
                    <td><input type='text' size='80' name='supplier_end_time' value='<?php echo $end_time; ?>'/></td>
                </tr>
                <tr><td><input type="hidden" name="from_post" value="from_admin_post"/></td></tr>
            </table>
        <?php
        }

        public function save_supplier_fields( $post_id = false, $post = false ) {
            if( $post->post_type != 'wedding_supplier' || empty( $post_id ) ) {
                return;
            }
            if( empty( $_POST['from_post'] ) || $_POST['from_post'] != "from_admin_post" ) {
                return;
            }

            global $wpdb;

            $post_name = get_the_title( $post_id );
            $gallery[0]       = isset( $_POST['supplier_gallery0'] )        ? $_POST['supplier_gallery0']         : '';
            $gallery[1]       = isset( $_POST['supplier_gallery1'] )        ? $_POST['supplier_gallery1']         : '';
            $gallery[2]       = isset( $_POST['supplier_gallery2'] )        ? $_POST['supplier_gallery2']         : '';
            $gallery[3]       = isset( $_POST['supplier_gallery3'] )        ? $_POST['supplier_gallery3']         : '';

            $start_time       = isset( $_POST['supplier_start_time'] )      ? $_POST['supplier_start_time']       : '';
            $end_time         = isset( $_POST['supplier_end_time'] )        ? $_POST['supplier_end_time']         : '';
            update_post_meta( $post_id, 'ws_start_time', $start_time );
            update_post_meta( $post_id, 'ws_end_time', $end_time );

            $supplier_category = array();
            $supplier_category[1]       = isset( $_POST['supplier_category1'] )        ? $_POST['supplier_category1']        : '';
            $supplier_category[2]       = isset( $_POST['supplier_category2'] )        ? $_POST['supplier_category2']        : '';
            $supplier_category[3]       = isset( $_POST['supplier_category3'] )        ? $_POST['supplier_category3']        : '';

            $supplier_categories = $supplier_category[1] . "--" . $supplier_category[2] . "--" . $supplier_category[3];

            $supplier_payment = isset( $_POST['supplier_pay_statement'] ) ? $_POST['supplier_pay_statement'] : 'not yet decide';
            update_post_meta( $post_id, 'pay_statement', $supplier_payment );

            $supplier_detail = array(
                'post_id'          => $post_id,
                'post_name'        => $post_name,
                'business_name'    => isset( $_POST['supplier_business_name'] )   ? $_POST['supplier_business_name']   : '',
                'company_name'     => isset( $_POST['supplier_company_name'] )    ? $_POST['supplier_company_name']    : '',
                'email_address'    => isset( $_POST['supplier_email_address'] )   ? $_POST['supplier_email_address']   : '',
                'abn_number'       => isset( $_POST['supplier_abn_number'] )      ? $_POST['supplier_abn_number']      : 0,
                'category'         => $supplier_categories,
                'website_address'  => isset( $_POST['supplier_website_address'] ) ? $_POST['supplier_website_address'] : '',
                'phone_number'     => isset( $_POST['supplier_phone_number'] )    ? $_POST['supplier_phone_number']    : '',
                'address'          => isset( $_POST['supplier_address'] )         ? $_POST['supplier_address']         : '',
                'street'           => isset( $_POST['supplier_street'] )          ? $_POST['supplier_street']          : '',
                'suburb'           => isset( $_POST['supplier_suburb'] )          ? $_POST['supplier_suburb']          : '',
                'state'            => isset( $_POST['supplier_state'] )           ? $_POST['supplier_state']           : '',
                'region'           => isset( $_POST['supplier_region'] )          ? $_POST['supplier_region']          : '',
                'distance'         => isset( $_POST['supplier_distance'] )        ? $_POST['supplier_distance']        : 150,
                'owner_name'       => isset( $_POST['supplier_owner_name'] )      ? $_POST['supplier_owner_name']      : '',
                'owner_email'      => isset( $_POST['supplier_owner_email'] )     ? $_POST['supplier_owner_email']     : '',
                'owner_phone'      => isset( $_POST['supplier_owner_phone'] )     ? $_POST['supplier_owner_phone']     : '',
                'main_img'         => isset( $_POST['supplier_main_img'] )        ? $_POST['supplier_main_img']        : '',
                'gallery_0'        => $gallery[0],
                'gallery_1'        => $gallery[1],
                'gallery_2'        => $gallery[2],
                'gallery_3'        => $gallery[3],
                'gallery'          => $gallery,
                'pay'              => isset( $_POST['supplier_pay'] )             ? $_POST['supplier_pay']             : 'free'
            );

            $is_exit = $wpdb->get_var( "SELECT * FROM {$wpdb->prefix}supplier_list_table WHERE post_id = $post_id" );

            if( empty( $is_exit ) ) { // make new supplier on the backend
                self::new_supplier( $supplier_detail );
            } else { // modify supplier
                self::modify_supplier( $supplier_detail );
            }
        }

        public function new_supplier( $supplier_detail, $desc = '' ) {
            global $wpdb;
            $supplier_table = $wpdb->prefix . "supplier_list_table";
            $wpnewcarousel_table = $wpdb->prefix . "wpnewcarousel";
            $wpnewcarouseldata_table = $wpdb->prefix . "wpnewcarouseldata";

            $time = date('Y-m-d h:i:sa');
            $post_id = $supplier_detail['post_id'];
            $post_name = $supplier_detail['post_name'];
            if( $post_id == 0 ) {
                $title = $supplier_detail['category'] . $supplier_detail['company_name'];
                $post_name = $supplier_detail['category'] . time();

                $add_post = array(
                    'post_title'    => $title,
                    'post_content'  => $desc,
                    'post_status'   => 'publish',
                    'post_name'     => $post_name,
                    'post_type'     => 'wedding_supplier'
                );
                $post_id = wp_insert_post( $add_post );

                $post_url = get_permalink( $post_id );
                $admin_to = array( 'minesingapore@gmail.com', 'admin@thebridessuite.com' );
                //$admin_to = array( 'minesingapore@gmail.com', 'minesingapore@gmail.com' );
                $user_to = $supplier_detail['owner_email'];

                $email_header = "From: thebridessuit <noreply@thebridessuit.com>\r\n";
                $email_header .= "Reply-To: noreply@thebridessuit.com\r\n" ;
                $email_header .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                $email_header .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

                if( $supplier_detail['pay'] == "free" ) {
                    $admin_content = sprintf( "<p>Free Supplier had registered from %s.</p> Please check in <a>%s</a>.", $supplier_detail['owner_email'], $post_url );
                    $user_content = sprintf( "<h3>Thank you.</h3> <p>Your free version Supplier had registered.</p> Please check in <a>%s</a>.", $post_url );
                    wp_mail( $user_to, 'New Supplier', $user_content, $email_header );
                } else {
                    $admin_content = sprintf( "<p>%s Supplier had registered from %s.</p> <p>Waiting for payment.</p> Please check in <a>%s</a>.", $supplier_detail['pay'], $supplier_detail['owner_email'], $post_url );
                }

                wp_mail( $admin_to, 'New Supplier', $admin_content, $email_header );
            }

            $wpdb->insert( $supplier_table, array(
                    'post_id'         => $post_id,
                    'business_name'   => $supplier_detail['business_name'],
                    'company_name'    => $supplier_detail['company_name'],
                    'website_address' => $supplier_detail['website_address'],
                    'email_address'   => $supplier_detail['email_address'],
                    'phone_number'    => $supplier_detail['phone_number'],
                    'address'         => $supplier_detail['address'],
                    'street'          => $supplier_detail['street'],
                    'suburb'          => $supplier_detail['suburb'],
                    'state'           => $supplier_detail['state'],
                    'region'          => $supplier_detail['region'],
                    'abn_number'      => $supplier_detail['abn_number'],
                    'distance'        => $supplier_detail['distance'],
                    'category'        => $supplier_detail['category'],
                    'owner_name'      => $supplier_detail['owner_name'],
                    'owner_email'     => $supplier_detail['owner_email'],
                    'owner_phone'     => $supplier_detail['owner_phone'],
                    'main_img'        => $supplier_detail['main_img'],
                    'gallery_1'       => $supplier_detail['gallery_0'],
                    'gallery_2'       => $supplier_detail['gallery_1'],
                    'gallery_3'       => $supplier_detail['gallery_2'],
                    'gallery_4'       => $supplier_detail['gallery_3'],
                    'pay'             => $supplier_detail['pay'],
                )
            );

            $wpdb->insert( $wpnewcarousel_table, array(
                    'CarouselName'   => $post_name,
                    'CarouselWidth'  => '848',
                    'CarouselHeight' => '388',
                    'CarouselEffect' => 'sliceDown',
                    'AnimationSpeed' => '1000',
                    'StartSlide'     => '0',
                    'PauseTime'      => '3000',
                    'ShowNav'        => 'false',
                    'HoverPause'     => 'true',
                    'SubmitDate'     => $time,
                    'IsActive'       => '1',
                )
            );

            $wpnewcarousel_id =  $wpdb->insert_id;
            update_post_meta( $post_id, 'wpnewcarousel_id', $wpnewcarousel_id );

            if( $supplier_detail['pay'] == "free" ) {
                update_post_meta( $post_id, 'pay_statement', "complete" );
                $time = time();
                $start_time = date('Y-m-d');

                $end_time = $time + 61 * 24 * 3600;
                $end_time = date('Y-m-d', $end_time);

                update_post_meta( $post_id, 'ws_start_time', $start_time );
                update_post_meta( $post_id, 'ws_end_time', $end_time );
            } else {
                update_post_meta( $post_id, 'pay_statement', "yet not decide" );
            }

            foreach( $supplier_detail['gallery'] as $key => $url ) {
                if( ! empty( $url ) ) {
                    $query = "INSERT INTO $wpnewcarouseldata_table ";
                    $query .= '( CarouselId, BackgroundImageURL, BackgroundImageLink, BackgroundImageAltText, TitleText, weight ) ';
                    $query .= "VALUES ( '{$wpnewcarousel_id}', '{$url}', '', 'gallery', '', '0' )";
                    $wpdb->query($query);
                }
            }

            return $post_id;
        }

        public function modify_supplier( $supplier_detail ) {
            global $wpdb;
            $supplier_table = $wpdb->prefix . "supplier_list_table";
            $wpnewcarouseldata_table = $wpdb->prefix . "wpnewcarouseldata";

            $wpdb->update( "$supplier_table",
                array(
                    'business_name' => $supplier_detail['business_name'],
                    'company_name' => $supplier_detail['company_name'],
                    'website_address' => $supplier_detail['website_address'],
                    'email_address' => $supplier_detail['email_address'],
                    'phone_number' => $supplier_detail['phone_number'],
                    'address' => $supplier_detail['address'],
                    'street' => $supplier_detail['street'],
                    'suburb' => $supplier_detail['suburb'],
                    'state' => $supplier_detail['state'],
                    'region' => $supplier_detail['region'],
                    'abn_number' => $supplier_detail['abn_number'],
                    'distance' => $supplier_detail['distance'],
                    'category' => $supplier_detail['category'],
                    'owner_name' => $supplier_detail['owner_name'],
                    'owner_email' => $supplier_detail['owner_email'],
                    'owner_phone' => $supplier_detail['owner_phone'],
                    'main_img' => $supplier_detail['main_img'],
                    'gallery_1' => $supplier_detail['gallery_0'],
                    'gallery_2' => $supplier_detail['gallery_1'],
                    'gallery_3' => $supplier_detail['gallery_2'],
                    'gallery_4' => $supplier_detail['gallery_3'],
                    'pay' => $supplier_detail['pay']
                ),
                array(
                    'post_id' => $supplier_detail['post_id']
                )
            );

            $wpnewcarousel_id = get_post_meta( $supplier_detail['post_id'], 'wpnewcarousel_id', true );
            $wpdb->delete( "$wpnewcarouseldata_table", array( 'CarouselId' => $wpnewcarousel_id ) );

            foreach( $supplier_detail['gallery'] as $key => $url ) {
                if( ! empty( $url ) ) {
                    $query = "INSERT INTO $wpnewcarouseldata_table ";
                    $query .= '( CarouselId, BackgroundImageURL, BackgroundImageLink, BackgroundImageAltText, TitleText, weight ) ';
                    $query .= "VALUES ( '{$wpnewcarousel_id}', '{$url}', '', 'gallery', '', '0' )";
                    $wpdb->query($query);
                }
            }

            $post_url = get_permalink($supplier_detail['post_id']);
            $to = 'minesingapore@gmail.com';
            $content = sprintf( "Supplier had changed. Please check in <a>%s</a>", $post_url);
            $email_header = "Content-Type: text/html\r\n" . 'X-Mailer: PHP/' . phpversion()."\r\n";
            wp_mail( $to, 'Modify of Supplier', $content, $email_header );
        }

        public function register_supplier() {
            $desc  = isset( $_POST['supplier_desc'] )  ? $_POST['supplier_desc']  : '';
            $gallery[0]       = isset( $_POST['supplier_gallery0'] )        ? $_POST['supplier_gallery0']        : '';
            $gallery[1]       = isset( $_POST['supplier_gallery1'] )        ? $_POST['supplier_gallery1']        : '';
            $gallery[2]       = isset( $_POST['supplier_gallery2'] )        ? $_POST['supplier_gallery2']        : '';
            $gallery[3]       = isset( $_POST['supplier_gallery3'] )        ? $_POST['supplier_gallery3']        : '';

            $supplier_detail = array(
                'post_id'          => 0,
                'post_name'        => '',
                'business_name'    => isset( $_POST['supplier_business_name'] )   ? $_POST['supplier_business_name']   : '',
                'company_name'     => isset( $_POST['supplier_company_name'] )    ? $_POST['supplier_company_name']    : '',
                'email_address'    => isset( $_POST['supplier_email_address'] )   ? $_POST['supplier_email_address']   : '',
                'abn_number'       => isset( $_POST['supplier_abn_number'] )      ? $_POST['supplier_abn_number']      : 0,
                'category'         => isset( $_POST['supplier_category'] )        ? $_POST['supplier_category']        : '',
                'website_address'  => isset( $_POST['supplier_website_address'] ) ? $_POST['supplier_website_address'] : '',
                'phone_number'     => isset( $_POST['supplier_phone_number'] )    ? $_POST['supplier_phone_number']    : '',
                'address'          => isset( $_POST['supplier_address'] )         ? $_POST['supplier_address']         : '',
                'street'           => isset( $_POST['supplier_street'] )          ? $_POST['supplier_street']          : '',
                'suburb'           => isset( $_POST['supplier_suburb'] )          ? $_POST['supplier_suburb']          : '',
                'state'            => isset( $_POST['supplier_state'] )           ? $_POST['supplier_state']           : '',
                'region'           => isset( $_POST['supplier_region'] )          ? $_POST['supplier_region']          : '',
                'distance'         => isset( $_POST['supplier_distance'] )        ? $_POST['supplier_distance']        : 150,
                'owner_name'       => isset( $_POST['supplier_owner_name'] )      ? $_POST['supplier_owner_name']      : '',
                'owner_email'      => isset( $_POST['supplier_owner_email'] )     ? $_POST['supplier_owner_email']     : '',
                'owner_phone'      => isset( $_POST['supplier_owner_phone'] )     ? $_POST['supplier_owner_phone']     : '',
                'main_img'         => isset( $_POST['supplier_main_img'] )        ? $_POST['supplier_main_img']        : '',
                'gallery_0'        => $gallery[0],
                'gallery_1'        => $gallery[1],
                'gallery_2'        => $gallery[2],
                'gallery_3'        => $gallery[3],
                'gallery'          => $gallery,
                'pay'              => isset( $_POST['supplier_pay'] )        ? $_POST['supplier_pay']        : '',
                'pay_statement'    => 'not yet decide'
            );
            $post_id = Wedding_Support::new_supplier( $supplier_detail, $desc );

            echo ( $post_id );
            die;
        }

        public function pay_supplier() {
            if( empty( $_POST['supplier_order_num'] ) || ! is_numeric( $_POST['supplier_order_num'] ) ) {
                die;
            }
            if( empty( $_POST['registered_post_id'] ) || ! is_numeric( $_POST['registered_post_id'] ) ) {
                die;
            }
            global $woocommerce;

            $product_id = $_POST['supplier_order_num'];

            $post_id = $_POST['registered_post_id'];
            $woocommerce->cart->add_to_cart( $product_id, 1, '', '', array( 'auto_id' => $post_id ) );

            $data = get_permalink( woocommerce_get_page_id( 'cart' ) );
            echo ( $data );
            die;
        }

        public function view_free_supplier() {
            if( empty( $_POST['registered_post_id'] ) || ! is_numeric( $_POST['registered_post_id'] ) ) {
                die;
            }
            $post_id = $_POST['registered_post_id'];
            $data = get_permalink( $post_id );
            echo( $data );
            die;
        }

        public function check_supplier_payment() {
            global $wpdb;

            $wp_supplier_list_table = $wpdb->prefix . "supplier_list_table";

            $supplier_lists = $wpdb->get_results( "SELECT * FROM $wp_supplier_list_table" );
            foreach( $supplier_lists as $supplier_list ) {
                $time = time();
                $post_id = $supplier_list->post_id;
                $order_id = get_post_meta( $post_id, "ws_order_id", true );
                $pay_statement = get_post_meta( $post_id, 'pay_statement', true );

                if( empty( $order_id ) && ( empty( $pay_statement ) || $pay_statement == "not yet decide" ) ) {
                    update_post_meta( $post_id, 'pay_statement', 'completed' );
                    $start_time = date('Y-m-d');
                    $end_time = $time + 61 * 24 * 3600;
                    $end_time = date('Y-m-d', $end_time);
                    update_post_meta( $post_id, 'ws_start_time', $start_time );
                    update_post_meta( $post_id, 'ws_end_time', $end_time );
                } else if( ! empty( $order_id ) ) {
                    $post_info = get_post( $order_id );

                    if( ! empty( $post_info ) && ! empty( $post_info->post_status ) ) {
                        $status = $post_info->post_status;
                        $status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;

                        if( $status == "cancelled" ) {
                            wp_delete_post( $post_id );
                            $wpdb->query( "DELETE FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'" );
                        } else {
                            update_post_meta( $post_id, 'pay_statement', $status );
                            $start_time = get_post_meta( $post_id, 'ws_start_time', true );
                            $during = $supplier_list->pay;
                            $quantity = get_post_meta( $post_id, 'ws_quantity', true );
                            if( $status == "completed" && empty( $start_time ) ) {
                                $time = time();
                                $start_time = date('Y-m-d');

                                if( $during == "weekly" ) {
                                    $end_time = $time + 7 * 24 * 3600 * $quantity;
                                    $end_time = date('Y-m-d', $end_time);
                                } else if ( $during == "monthly" ) {

                                    $end_time = $time + 31 * 24 * 3600 * $quantity;
                                    $end_time = date('Y-m-d', $end_time);
                                } else if ( $during == "annual" ) {
                                    $end_time = $time + 365 * 24 * 3600 * 1.5 * $quantity;
                                    $end_time = date('Y-m-d', $end_time);
                                } else {
                                    $end_time = $start_time;
                                }

                                update_post_meta( $post_id, 'ws_start_time', $start_time );
                                update_post_meta( $post_id, 'ws_end_time', $end_time );
                            }
                        }
                    }
                } else { }

                if( $pay_statement == "completed" ) {
                    $end_time = get_post_meta( $post_id, 'ws_end_time', true );
                    $end_time = strtotime( $end_time );
                    $dif = $end_time - $time;
                    if( $dif < 0 ) {
                        wp_delete_post( $post_id );
                        $wpdb->query( "DELETE FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$post_id'" );
                    }
                }
            }
        }

        public function supplier_register_page() {
            require_once( 'template/register_template.php' );
        }

        public function upload_file() {
            $result = true; //not error $result=true;
            if( ! isset( $_POST['cropped_file'] ) || ! isset( $_POST['phone_number'] ) || ! isset( $_POST['abn_number'] ) ) {
                $result = false;
            }

            if( empty( $_POST['cropped_file'] ) || empty( $_POST['phone_number'] ) || empty( $_POST['abn_number'] ) ) {
                $result = false;
            }

            if( $result ) {
                $phone_number = $_POST['phone_number'];
                $abn_number = $_POST['abn_number'];
                $base64_string = $_POST['cropped_file'];
                $data = explode(',', $base64_string);

                $time = time();
                $sub_dir = $phone_number . $abn_number . $time;

                $uploadDir = ABSPATH . '/supplier-images/';
                if ( ! file_exists($uploadDir)) {
                    mkdir($uploadDir);
                }

                $uploadDir = ABSPATH . '/supplier-images/' . $sub_dir . '/';
                if ( ! file_exists($uploadDir)) {
                    mkdir($uploadDir);
                }

                $file_name = "image.png";
                $file_path = $uploadDir . $file_name;
                $file_url = home_url() . '/supplier-images/' . $sub_dir . '/' . $file_name;

                $ifp = fopen($file_path, "wb");
                $result = fwrite($ifp, base64_decode($data[1]));
                fclose($ifp);
            }

            $result_data = ( ! $result ) ? array( 'error_occur' => 'There was an error uploading your files' ) : array( 'file_url' => $file_url );
            echo  json_encode( $result_data );

            die;
        }

        public function wedding_search_form() {
            ?>
            <form method="post" class="wedding-search-form">
                <table class="wedding-search-table">
                    <tr>
                        <td class="search-country"></td>
                        <td class="search-state"></td>
                        <td class="search-distance"></td>
                        <td class="search-postcode"></td>
                        <td class="search-btn"></td>
                    </tr>
                    <?php if( is_user_logged_in() ) {
                        $cuser_ID = get_current_user_id();
                        $cuser_distance = get_user_meta( $cuser_ID, 'distance_you_are_ready_to_travel', true );
                        ?>
                        <tr>
                            <td class="search-country">
                                <input type="text" placeholder="Country" name="search_country" id="search_country" value="<?php echo get_user_meta( $cuser_ID, 'country', true ); ?>"/>
                            </td>
                            <td class="search-state">
                                <input type="text" placeholder="State" name="search_state" id="search_state" value="<?php echo get_user_meta( $cuser_ID, 'state', true ); ?>"/>
                            </td>
                            <td class="search-distance"><select name="search_distance" id="search_distance">
                                    <option value="25" <?php selected( $cuser_distance, "25km" ); ?>>25km</option>
                                    <option value="50" <?php selected( $cuser_distance, "50km" ); ?>>50km</option>
                                    <option value="100" <?php selected( $cuser_distance, "100km" ); ?>>100km+</option>
                                </select>
                            </td>
                            <td class="search-postcode"><input type="text" placeholder="Suburb/Postcode" name="search_postcode" id="search_postcode" value="<?php echo get_user_meta( $cuser_ID, 'address', true ); ?>"/></td>
                            <td class="search-btn"><button id="supplier_search" name="supplier_search">Search</td>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <td class="search-country"><input type="text" placeholder="Country" name="search_country" id="search_country"/></td>
                            <td class="search-state"><input type="text" placeholder="State" name="search_state" id="search_state"/></td>
                            <td class="search-distance"><select name="search_distance" id="search_distance">
                                    <option value="25">25km</option>
                                    <option value="50">50km</option>
                                    <option value="100">100km+</option>
                                </select>
                            </td>
                            <td class="search-postcode"><input type="text" placeholder="Suburb/Postcode" name="search_postcode" id="search_postcode"/></td>
                            <td class="search-btn"><button id="supplier_search" name="supplier_search">Search</td>
                        </tr>
                    <?php } ?>
                </table>
            </form>
        <?php
        }

        public static function check_supplier_valid() {
            if( ! isset( $_POST['supplier_type'] ) ) {
                wp_die();
            }

            $show_html = "";
            $is_search = false;
            $search_country = $_POST['search_country'];

            global $wpdb;

            $query = "SELECT * FROM {$wpdb->prefix}supplier_list_table as slt WHERE ";
            if( ! empty( $_POST['search_state'] ) ) {
                $query .= "slt.state = '" . $_POST['search_state'] . "' AND ";
            }
            if( ! empty( $_POST['search_postcode'] ) ) {
                $query .= "(slt.region = '" . $_POST['search_postcode'] . "' OR slt.suburb = '" . $_POST['search_postcode'] . "' ) AND ";
            }
            if( ! empty( $_POST['search_distance'] ) ) {
                $query .= "slt.distance >= '" . $_POST['search_distance'] . "' AND ";
            }
            $query .= "1";

            $suppliers = $wpdb->get_results( $query );
            foreach( $suppliers as $key => $supplier ) {
                $supplier_id = $supplier->post_id;

                $supplier_payment = get_post_meta( $supplier_id, 'pay_statement', true );
                if( $supplier_payment != "completed" ) {
                    continue;
                }
                $is_category = false;
                $supplier_categories = $supplier->category;
                $supplier_categories = explode( "--", $supplier_categories );
                foreach( $supplier_categories as $category_key => $supplier_category ) {
                    if( $supplier_category == $_POST['supplier_type'] ) {
                        $is_category = true;
                    }
                }

                if( $is_category ) {
                    $is_search = true;
                    $supplier_phone = $wpdb->get_var("SELECT phone_number FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$supplier_id'");
                    $supplier_location = $wpdb->get_var("SELECT state FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$supplier_id'") . ' ';
                    $supplier_location .= $wpdb->get_var("SELECT address FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$supplier_id'");
                    $supplier_location .= ', ';
                    $supplier_location .= $wpdb->get_var("SELECT street FROM {$wpdb->prefix}supplier_list_table WHERE post_id = '$supplier_id'");

                    $show_html .= '<div class="suppliers" data-sup_id="' . $supplier_id . '">';
                    $wpnewcarousel_id = get_post_meta($supplier_id, 'wpnewcarousel_id', true);
                    if (!empty($wpnewcarousel_id)) {
                        $CarouselName = $wpdb->get_var("SELECT CarouselName FROM {$wpdb->prefix}wpnewcarousel WHERE Id = '$wpnewcarousel_id'");
                        $show_html .= do_shortcode("[wpnewcarousel name='$CarouselName' width='' height='' effect='' startslide='' animationspeed='' imagepausetime='10000' shownav='' hoverpause='' ]");
                    }
                    $show_html .= '<div class="carsoul">';
                    $show_html .= '<br>';
                    $cookie_name = "supplier-" . $supplier_id;
                    if ($_COOKIE[$cookie_name]) {
                        $show_html .= '<label class="save-favorite favorite-img" data-sup_id="' . $supplier_id . '"></label>';
                    } else {
                        $show_html .= '<label class="unsaved-favorite favorite-img" data-sup_id="' . $supplier_id . '"></label>';
                    }
                    $show_html .= "$supplier_location";
                    $show_html .= '<p></p>';
                    $show_html .= 'PHONE: ' . $supplier_phone;
                    $show_html .= '<div class="details"><a href="' . get_permalink($supplier_id) . '">Details</a></div>';
                    $show_html .= '</div>';
                    $show_html .= '</div>';
                }
            }

            if( ! $is_search ) {
                $show_html = "<div><h1>There is no matching result!</h1></div>";
            }

            echo json_encode( $show_html );
            wp_die();
        }

        public function supplier_single_template( $template_path ) {
            if ( get_post_type() == 'wedding_supplier' ) {
                if ( is_single() ) {
                    if ( $theme_file = locate_template( array( 'single-wedding_support.php' ) ) ) {
                        $template_path = $theme_file;
                    } else {
                        $template_path = plugin_dir_path( __FILE__ ) . '/template/single-wedding_support.php';
                    }

                }
            }
            return $template_path;
        }
    }


    /**
     * Plugin Init Process
     * */
    add_action( 'plugin_init', 'plugin_init_process' );

    function plugin_init_process() {
        new Wedding_Support();
    }

    do_action( 'plugin_init' );
}