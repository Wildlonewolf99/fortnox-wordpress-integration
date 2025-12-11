<?php

namespace src\Wallspace;

if ( !defined( 'ABSPATH' ) ) die();

use src\fortnox\WF_Plugin;

if ( ! class_exists( __NAMESPACE__ . "\WF_NG_Fortnox_auth" ) ):
	class WF_NG_Fortnox_auth {
		public static function init() {
			if ( isset( $_REQUEST['fortnox_auth_code'] ) ) {
				add_action( 'admin_notices',
					function () {
						$class   = false;
						$message = false;
						switch ( $_REQUEST['fortnox_auth_code'] ) {
							case '1':
								$class   = 'notice notice-error';
								$message = __( "Fortnox auth link expired, please try again.", WF_Plugin::TEXTDOMAIN );
								break;
							case '2':
								$class   = 'notice notice-success';
								$message = __( "Successful fortnox auth", WF_Plugin::TEXTDOMAIN );
								break;
						}
						if ( $message && $class ) {
							printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
						}
					} );
			}
		}
	}
endif;
