<?php

namespace src\fortnox\api;

if ( !defined( 'ABSPATH' ) ) die();

use Exception;
use src\fortnox\WF_Plugin;

class WF_Auth
{
    const REFRESH_URL = 'https://apps.fortnox.se/oauth-v1/token';

    public static function get_client_id(){
        $client_id = get_option( 'fortnox_client_id' );
        return $client_id;
    }

    public static function get_client_secret(){
        $client_secret = get_option( 'fortnox_client_secret' );
        return $client_secret;
    }

    /**
     * Exchange authorization code for access + refresh tokens and save them
     *
     * This expects the plugin to have stored the authorization code in option 'fortnox_auth_code'
     * and that 'fortnox_redirect_uri' contains the redirect URI used (if stored).
     *
     * @throws Exception
     */
    public static function get_access_token()
    {
        $auth_code = get_option( 'fortnox_auth_code'  );
        $client_id = self::get_client_id();
        $client_secret = self::get_client_secret();
        $redirect_uri = get_option( 'fortnox_redirect_uri' );

        if( empty( $auth_code ) || empty( $client_id ) || empty( $client_secret ) ){
            throw new Exception( "Authorisation code / client credentials are missing." );
        }

        // Exchange authorization code for tokens per OAuth2
        $headers = [
            'Content-type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret )
        ];

        $body = [
            'grant_type'   => 'authorization_code',
            'code'         => $auth_code,
        ];

        // Include redirect_uri only if we have it (Fortnox requires exact match)
        if ( ! empty( $redirect_uri ) ) {
            $body['redirect_uri'] = $redirect_uri;
        }

        $args = [
            'headers' => $headers,
            'body'    => $body,
            'method'  => 'POST',
            'data_format' => 'body'
        ];

        fortnox_write_log( "Exchanging authorization code for tokens" );
        fortnox_write_log( $args );

        $response = wp_remote_post( self::REFRESH_URL, $args );

        if( is_a( $response, 'WP_Error'  ) ){
            throw new \Exception( $response->get_error_message() );
        }

        $data = json_decode( $response['body'] );

        fortnox_write_log( "Token exchange response:" );
        fortnox_write_log( $data );

        if( self::is_error( $data ) ){
            throw new Exception( 'Token exchange failed. Please re-authorize the integration.' );
        }

        if ( property_exists( $data, 'access_token') ) {
            update_option('fortnox_access_token_oauth2', $data->access_token);
            if ( property_exists( $data, 'refresh_token') ) {
                update_option('fortnox_refresh_token', $data->refresh_token);
            }
            // store expiry (use exp if present otherwise assume 3600)
            $expires_in = property_exists($data, 'expires_in') ? intval($data->expires_in) : 3600;
            update_option('fortnox_access_token_expiry_time', time() + $expires_in);
            return $data->access_token;
        }

        throw new Exception( 'Token exchange did not return an access_token.' );
    }

    public static function custom_http_request_timeout( $timeout_value ) {
        return 20;
    }

    /**
     * Refreshes token (uses refresh_token grant)
     *
     * @throws \Exception
     */
    public static function refresh_token(){

        add_filter( 'http_request_timeout', [ 'src\fortnox\api\WF_Auth', 'custom_http_request_timeout' ], 10, 1);

        $client_id = self::get_client_id();
        $client_secret = self::get_client_secret();
        $refresh_token = get_option('fortnox_refresh_token');

        if ( empty( $client_id ) || empty( $client_secret ) || empty( $refresh_token ) ) {
            throw new \Exception( 'Missing client credentials or refresh token (please re-authorize the Fortnox integration).', 401 );
        }

        $headers = [
            'Content-type' =>  'application/x-www-form-urlencoded',
            'Authorization' =>  'Basic ' . base64_encode( $client_id . ':' . $client_secret)
        ];

        $args = [
            'headers' => $headers,
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            ],
            'method' => 'POST',
            'data_format' => 'body'
        ];

        fortnox_write_log( "Refreshing access_token: " );
        fortnox_write_log( $args );

        $response = wp_remote_post( self::REFRESH_URL, $args );

        if( is_a( $response, 'WP_Error'  ) ){
            throw new \Exception( $response->get_error_message() );
        }

        $data = json_decode( $response['body'] );

        fortnox_write_log( "Response " );
        fortnox_write_log( $data );

        if( self::is_error( $data ) ){
            throw new \Exception( 'Fortnox integrationen har blivit utloggad, logga in igen genom att göra om steg 1 och 2', 401 );
        }

        if ( property_exists( $data, 'access_token') ) {
            update_option('fortnox_access_token_oauth2', $data->access_token);
            if ( property_exists( $data, 'refresh_token') ) {
                update_option('fortnox_refresh_token', $data->refresh_token);
            }
            $expires_in = property_exists($data, 'expires_in') ? intval($data->expires_in) : 3600;
            update_option('fortnox_access_token_expiry_time', time() + $expires_in);
        }

    }

    /**
     * Check if response has an error
     *
     * @param $response
     * @return mixed
     */
    public static function is_error( $data ){
        return isset( $data->error ) || isset( $data->ErrorInformation );
    }
}
