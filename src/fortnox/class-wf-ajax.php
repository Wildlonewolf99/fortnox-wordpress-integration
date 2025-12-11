<?php

namespace src\fortnox;

if ( !defined( 'ABSPATH' ) ) die();

use Exception;
use src\admin_views\WF_Admin_Listing_Actions;
use src\fortnox\api\WF_Auth;
use src\fortnox\api\WF_Delivery_Terms;
use src\wallspace\WF_NG_Fortnox_auth;

class WF_Ajax {

    const WF_ACTION_AJAX_SYNC_ORDER = "sync_order";
    const WF_ACTION_AJAX_SYNC_PRODUCT = "sync_product";
    const WF_ACTION_AJAX_BULK_SYNC_PRODUCTS = "fortnox_sync_products";
    const WF_ACTION_AJAX_BULK_SYNC_ORDERS = "fortnox_sync_orders_date_range";
    const WF_ACTION_AJAX_SEND_INVOICE = "send_invoice";
    const WF_ACTION_AJAX_FLUSH_ACCESS_TOKEN = "fortnox_flush_access_token";
	//const WF_ACTION_AJAX_AUTHORIZE_WITH_FORTNOX = "fortnox_authorize_with_fortnox";
    const WF_ACTION_AJAX_GET_SETTINGS = "fortnox_get_settings";

    /**
     * INIT
     */
    public static function init(){

        add_action( 'wp_ajax_fortnox_update_setting', __CLASS__ . '::update_setting' );
        add_action( 'wp_ajax_fortnox_bulk_action', __CLASS__ . '::bulk_action' );
	    add_action( 'wp_ajax_check_fortnox_auth_code', __CLASS__. '::check_auth_code' );
        add_action( 'wp_ajax_fetch_delivery_terms',  __CLASS__. 'check_auth_code' );
        add_action( 'wp_ajax_fortnox_action',  __CLASS__. '::process' );
        add_action( 'wp_ajax_fetch_delivery_terms',  __CLASS__. '::fetch_delivery_terms' );
        add_action( 'wp_ajax_fetch_payment_terms',  __CLASS__. '::fetch_payment_terms' );
	    if ( is_admin() ) {
		    WF_NG_Fortnox_auth::init();
	    }
    }

	/**
	 * Send AJAX response
	 *
	 * @param array $data
	 */
	public static function respond( $data = [] )
	{
		$defaults = [
			'error' => false
		];
		$data = array_merge( $defaults, $data );
		die( json_encode( $data ) );
	}

	/**
	 * Send AJAX error
	 *
	 * @param string $message
	 */
	public static function error( $message ){
		self::respond(
		    [
		        'message' => $message,
                'error' => true
            ]
        );
	}

	/**
	 * Update settings through AJAX
	 */
	public static function update_setting()
	{
		if( ! empty( $_REQUEST['settings'] ) ){
            foreach( $_REQUEST['settings'] as $option => $value ){
                if( 0 === strpos( $option, 'fortnox_'  ) ){
                    update_option( $option, $value );
                }
            }
        }

		self::respond();
	}

    /**
     * Process AJAX request
     * @throws \Exception
     */
    public static function fetch_delivery_terms(){

        $response = WF_Delivery_Terms::get_delivery_terms();
        self::respond( $response );
    }

	/**
	 * Process AJAX request
	 */
	public static function process(){

		$response = [];

		switch( $_REQUEST[ 'fortnox_action' ] ) {
            case self::WF_ACTION_AJAX_SYNC_ORDER:
                $response = WF_Admin_Listing_Actions::ajax_sync_order();
				break;
			case self::WF_ACTION_AJAX_SYNC_PRODUCT:
                $response = WF_Admin_Listing_Actions::ajax_sync_product();
                break;
            case self::WF_ACTION_AJAX_SEND_INVOICE:
                $response = WF_Admin_Listing_Actions::ajax_send_invoice();
                break;
		}

		self::respond( $response );
	}

	/**
	 * Do bulk action through AJAX
	 */
	public static function bulk_action(){

		$response = [ 'error' => false ];

		if( empty( $_REQUEST['bulk'] ) ){
            self::error( "Bulk action is missing." );
        }

		switch( $_REQUEST['bulk'] ) {
			/*case self::WF_ACTION_AJAX_AUTHORIZE_WITH_FORTNOX:
                $response = WF_Admin_Listing_Actions::ajax_authorize_with_fortnex();
				break;*/
            case self::WF_ACTION_AJAX_BULK_SYNC_PRODUCTS:
                $response = WF_Admin_Listing_Actions::bulk_sync_products();
                break;
            case self::WF_ACTION_AJAX_FLUSH_ACCESS_TOKEN:
                $response = WF_Admin_Listing_Actions::ajax_flush_access_token();
				break;
            case self::WF_ACTION_AJAX_BULK_SYNC_ORDERS:
                $response = WF_Admin_Listing_Actions::bulk_sync_orders();
                break;
            case self::WF_ACTION_AJAX_GET_SETTINGS:
                $response = WF_Admin_Listing_Actions::fetch_settings();
                break;

		}
		
		self::respond( $response );
	}
}
