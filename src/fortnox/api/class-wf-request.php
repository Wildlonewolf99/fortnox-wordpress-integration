<?php

namespace src\fortnox\api;

if ( !defined( 'ABSPATH' ) ) die();

use Exception;
use src\fortnox\WF_Ajax;
use src\fortnox\WF_Plugin;

class WF_Request
{

    const FORTNOX_API_URL = 'https://api.fortnox.se/3';
    const PAYLOAD_KEYS_TO_NOT_CLEAN = ['ArticleNumber'];

    /**
     * Make a GET API request to Fortnox
     * @throws \Exception
     * @param string $path
     * @param bool $print_error
     * @param array $data
     * @return mixed
     */
    public static function get( $path, $print_error=true ) {

        fortnox_write_log( "GET: " . $path );
        fortnox_write_log( self::get_headers() );

        $headers = self::get_headers();
        if ( empty( $headers ) ) {
            throw new \Exception( 'Fortnox access token missing. Please authenticate the integration.' );
        }

        $response = wp_remote_get( self::FORTNOX_API_URL . $path, array( 'headers' => $headers, 'timeout' => 10 ) );

        if( is_a( $response, 'WP_Error'  ) ){
            throw new \Exception( $response->get_error_message() );
        }

        if( 429 === intval( $response['response']['code'] ) ){
            sleep(1);
            fortnox_write_log( $response );
            return self::get( $path, [] );
        }

        $data = json_decode( $response['body'] );

        fortnox_write_log( $data );

        if( self::is_error( $data ) ) {
            throw new \Exception( self::get_error( $data, 'GET', $path  ), self::get_error_code( $data ) );
        }

        return $data;
    }

    /**
     * Make a POST API request to Fortnox
     * @param string $path
     * @param array $payload
     * @return mixed
     * @throws \Exception
     */
    public static function post( $path, $payload = [] ) {

        fortnox_write_log( "POST: " . $path );
        fortnox_write_log( $payload );

        $headers = self::get_headers();
        if ( empty( $headers ) ) {
            throw new \Exception( 'Fortnox access token missing. Please authenticate the integration.' );
        }

        $args = [
            'headers' => $headers,
            'body' => html_entity_decode( json_encode( self::clean_data( $payload ) ) ),
            'method' => 'POST',
            'data_format' => 'body'
        ];

        $response = wp_remote_post( self::FORTNOX_API_URL . $path, $args );

        if( is_a( $response, 'WP_Error'  ) ){
            throw new \Exception( $response->get_error_message() );
        }

        $data = json_decode( $response['body'] );

        fortnox_write_log( $data );

        if( 429 === intval( $response['response']['code'] ) ){
            sleep(1);
            fortnox_write_log( $response );
            return self::post( $path, $payload );
        }

        if( self::is_error( $data ) ) {
            throw new \Exception( self::get_error( $data, 'POST', $path ), self::get_error_code( $data ) );
        }

        return $data;
    }

    /**
     * Make a PUT API request to Fortnox
     * @param string $path
     * @param array $payload
     * @return mixed
     * @throws \Exception
     */
    public static function put( $path, $payload = [] ) {

        fortnox_write_log( "PUT: " . $path );
        fortnox_write_log( $payload );

        $headers = self::get_headers();
        if ( empty( $headers ) ) {
            throw new \Exception( 'Fortnox access token missing. Please authenticate the integration.' );
        }

        $args = [
            'headers' => $headers,
            'body' =>  html_entity_decode( json_encode( self::clean_data( $payload ) ) ),
            'method' => 'PUT',
            'data_format' => 'body',
            'timeout' => 10
        ];

        // Fortnox expects PUT calls to the same endpoint; wp_remote_post is used historically
        $response = wp_remote_post( self::FORTNOX_API_URL . $path, $args );

        if( is_a( $response, 'WP_Error'  ) ){
            throw new \Exception( $response->get_error_message() );
        }

        $data = json_decode( $response['body'] );

        fortnox_write_log( $data );

        if( 429 === intval( $response['response']['code'] ) ){
            sleep(1);
            fortnox_write_log( $response );
            return self::put( $path, $payload );
        }

        if( self::is_error( $data ) ) {
            throw new \Exception( self::get_error( $data, 'PUT', $path ), self::get_error_code( $data ) );
        }

        return $data;
    }

    /**
     * Build headers for Fortnox requests
     * Uses OAuth2 access token if available; otherwise falls back to legacy token (not recommended).
     */
    private static function get_headers(){

        // Prefer OAuth2 token
        $oauth_token = get_option( 'fortnox_access_token_oauth2' );
        if ( ! empty( $oauth_token ) ) {

            if ( self::is_access_token_valid() ){
                return [
                    'Authorization'  =>  "Bearer " . $oauth_token,
                    'Content-Type'   => 'application/json',
                    'Accept'         => 'application/json'
                ];
            } else {
                // try refresh and then return headers (refresh_token() will throw if it fails)
                WF_Auth::refresh_token();
                $new = get_option( 'fortnox_access_token_oauth2' );
                if ( ! empty( $new ) ) {
                    return [
                        'Authorization'  =>  "Bearer " . $new,
                        'Content-Type'   => 'application/json',
                        'Accept'         => 'application/json'
                    ];
                }
            }
        }

        // Legacy fallback (old API key + client secret) — note: Fortnox now prefers OAuth2
        $legacy_token = get_option( 'fortnox_access_token' );
        if ( ! empty( $legacy_token ) ) {
            $client_secret = WF_Auth::get_client_secret();
            if ( ! empty( $client_secret ) ) {
                return [
                    'Access-Token'  =>  $legacy_token,
                    'Client-Secret' =>  $client_secret,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json'
                ];
            }
        }

        // No usable headers found
        return [];
    }

    /**
     * Returns if true Access Token is valid
     */
    public static function is_access_token_valid(){
        return intval( get_option( 'fortnox_access_token_expiry_time' ) ) > time();
    }

    /**
     * Clean Data
     *
     * @param $data
     * @return mixed
     */
    public static function clean_data( $data ){
        if ( is_array( $data ) ) {
            if( self::is_assoc_array( $data ) ){
                foreach ( $data as $k => $v ) {
                    if( ! in_array($k, self::PAYLOAD_KEYS_TO_NOT_CLEAN ) ){
                        $data[$k] = self::clean_data( $v );
                    }
                }
            }
            else{
                for ( $i = 0; $i <  count( $data ); $i++ ) {
                    $data[$i] = self::clean_data( $data[$i] );
                }
            }

        }
        else if ( is_string ( $data ) ) {
            return apply_filters( 'wf_filter_payload', $data );//htmlspecialchars( preg_replace( '/[\/]/', '_', $data ) );
        }
        return $data;
    }

    public static function is_assoc_array(array $arr)
    {
        if ( array() === $arr) return false;
        return array_keys( $arr ) !== range(0, count( $arr ) - 1);
    }

	/**
	 * Check if response has en error
	 *
	 * @param $response
     * @return mixed
	 */
	public static function is_error( $response ){
        if( property_exists( $response, 'message' ) &&  $response->message == 'unauthorized' ) {
            throw new \Exception( 'Fortnox integrationen har blivit utloggad, logga in igen genom att göra om steg 1 och 2', 401 );
        }
		return isset( $response->ErrorInformation );
	}

    /**
     * Extract error message and code from error response
     *
     * 
     * @param $response
     * @param null $method
     * @param null $path
     * @return mixed
     */
	public static function get_error( $data, $method = null, $path = null )
	{
        $message = '';

		if( ! empty( $data->ErrorInformation->Message ) ){
            $message = $data->ErrorInformation->Message;
        }

		if( ! empty( $data->ErrorInformation->message ) ){
            $message = $data->ErrorInformation->message;
        }

		if( ! empty( $method ) && defined( 'WP_DEBUG'  ) && WP_DEBUG ){
            $message .= "\nMETHOD: {$method}\n";
        }


		if( ! empty( $path ) && defined( 'WP_DEBUG'  ) && WP_DEBUG ){
            $message .= "PATH: {$path}\n";
        }


		if( ! empty( $data ) && defined( 'WP_DEBUG'  ) && WP_DEBUG ){
            $message .= "METHOD: " . json_encode( $data ) . "\n";
        }

		$code = self::get_error_code( $data );

		return "{$message} (Felkod: {$code})";
	}

    /**
     * Extract error code from error response
     * @param $response
     * @return mixed
     */
	public static function get_error_code( $data )
	{
		if( ! empty( $data->ErrorInformation->Code ) ){
            return $data->ErrorInformation->Code;
        }

        if( ! empty( $data->ErrorInformation->code ) ){
            return $data->ErrorInformation->code;
        }

		return false;
	}

    /**
     * Get response code
     * @throws \Exception
     *
     * @param $method
     * @param $path
     * @param $data
     * @return mixed
     */
    public static function get_response_code( $method, $path, $data = [] )
    {
        try {
            self::make( $method, $path, $data );
        }
        catch( \Exception $error ) {
            return $error->getCode();
        }

        return false;
    }
}
