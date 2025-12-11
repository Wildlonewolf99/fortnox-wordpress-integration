<?php

namespace src\admin_views;

if ( !defined( 'ABSPATH' ) ) die();

use src\fortnox\api\WF_Company_Information;
use src\fortnox\WF_Plugin;
use src\wallspace\admin\WF_Admin_Settings;

class WF_General_Settings_View{

    /**
     * Adds all required setting fields for General Settings View
     */
    public static function add_settings()
    {
        $page = "fortnox";

        // General tab
        WF_Admin_Settings::add_tab([
            'page' => $page,
            'name' => "general",
            'title' => __("General", WF_Plugin::TEXTDOMAIN )
        ]);

        // API section
        WF_Admin_Settings::add_section([
            'page' => $page,
            'tab' => "general",
            'name' => "api",
            'title' => __("Integration credentials", WF_Plugin::TEXTDOMAIN ),
            'description' => __( 'Your credentials to communicate with Fortnox', WF_Plugin::TEXTDOMAIN )
        ]);       

        // API key field
        WF_Admin_Settings::add_field([
            'page' => $page,
            'tab' => "general",
            'section' => "api",
            'name' => "fortnox_client_id",
            'title' => __("Fortnox Client ID", WF_Plugin::TEXTDOMAIN ),
            'description' => __( 'The Client ID used for Fortnox' , WF_Plugin::TEXTDOMAIN ),
            'type' => "text",
        ]);

        WF_Admin_Settings::add_field([
            'page' => $page,
            'tab' => "general",
            'section' => "api",
            'name' => "fortnox_client_secret",
            'title' => __("Fortnox Client Secret", WF_Plugin::TEXTDOMAIN ),
            'description' => __( 'The Client secret used for Fortnox' , WF_Plugin::TEXTDOMAIN ),
            'type' => "text",
        ]);

        WF_Admin_Settings::add_field( [
			'page' => $page,
			'tab' => "general",
			'section' => "api",
			'title' => __( "Authorize with Fortnox", WF_Plugin::TEXTDOMAIN ),
			'type' => "button",
            'href' => admin_url('admin-post.php?action=fortnox_authorize'),
			'button' => [
				'text' => __( "Authorize with Fortnox", WF_Plugin::TEXTDOMAIN ),
			],
			'description' => sprintf( __( 'Get Fortnox Authorization Code. (Please save client ID, client secret and set Redirect URI as https://yourdomain.com/wp-admin/admin-post.php?action=fortnox_callback )', WF_Plugin::TEXTDOMAIN ) )
		] );

        WF_Admin_Settings::add_field([
            'page' => $page,
            'tab' => "general",
            'section' => "api",
            'name' => "fortnox_auth_code",
            'title' => __("Fortnox Authorization Code", WF_Plugin::TEXTDOMAIN ),
            'description' => __( 'Do not edit, please use Authorize with Fortnox to get new code.' , WF_Plugin::TEXTDOMAIN ),
            'type' => "text",
        ]);

        WF_Admin_Settings::add_field( [
            'page' => $page,
            'tab' => "general",
            'section' => "api",
            'name' => "fortnox_access_token_oauth2",
            'title' => __( "Oauth Access Token", WF_Plugin::TEXTDOMAIN ),
            'tooltip' => __( "Access Token.", WF_Plugin::TEXTDOMAIN ),
        ] );

        WF_Admin_Settings::add_field( [
            'page' => $page,
            'tab' => "general",
            'section' => "api",
            'name' => "fortnox_refresh_token",
            'title' => __( "Refresh Token", WF_Plugin::TEXTDOMAIN ),
            'tooltip' => __( "Refresh Token.", WF_Plugin::TEXTDOMAIN ),
        ] );

        WF_Admin_Settings::add_field( [
            'page' => $page,
            'tab' => "general",
            'section' => "api",
            'name' => "fortnox_access_token_expiry_time",
            'title' => __( "Access Token Expiry time", WF_Plugin::TEXTDOMAIN ),
            'tooltip' => __( "Access Token Expiry time.", WF_Plugin::TEXTDOMAIN ),
        ] );

        WF_Admin_Settings::add_field( [
			'page' => $page,
			'tab' => "general",
			'section' => "api",
			'title' => __( "Flush access token", WF_Plugin::TEXTDOMAIN ),
			'type' => "button",
			'button' => [
				'text' => __( "Flush access token", WF_Plugin::TEXTDOMAIN ),
			],
			'data' => [
				[
					'key' => "fortnox-bulk-action",
					'value' => "fortnox_flush_access_token"
				]
			],
			'description' => sprintf( __( 'Delete Fortnox refresh token.', WF_Plugin::TEXTDOMAIN ) )
		] );

        // class-wf-products section
        WF_Admin_Settings::add_section([
            'page' => $page,
            'tab' => "general",
            'name' => "debug",
        ]);

        WF_Admin_Settings::add_field([
            'page' => $page,
            'tab' => 'general',
            'section' => 'debug',
            'type' => 'checkboxes',
            'title' => __( 'Debug', WF_Plugin::TEXTDOMAIN ),
            'options' => [
                [
                    'name' => 'fortnox_debug_log',
                    'label' => __( 'Activate logging', WF_Plugin::TEXTDOMAIN ),
                    'description' => __( 'Unnecessary logging can clog your system resources.', WF_Plugin::TEXTDOMAIN ) . ' <span class="red warning">' . __( 'Turn off when not debugging!', WF_Plugin::TEXTDOMAIN ) . '</span><br>' . __( 'The debug log can be found in <b>WooCommerce</b> -> <b>Status</b> -> <b>Logs</b>', WF_Plugin::TEXTDOMAIN )
                ]
            ]
        ]);
    }
}
