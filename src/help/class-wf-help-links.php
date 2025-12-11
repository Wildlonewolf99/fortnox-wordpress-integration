<?php

namespace src\help;

if ( !defined( 'ABSPATH' ) ) die();

class WF_Help_Links{

    public static function get_error_log_text( $code ){
        $link = self::get_link( $code );

        if( $link ){
            return '<a href="' . $link . ' " target="_blank">HJÄLPAVSNITT</a>';
        }
        return '';
    }

    public static function get_error_text( $code ){
        $link = self::get_link( $code );

        if( $link ){
            return '<button class="button button-primary"><a href="' . $link . ' " target="_blank" style="color:white;">HJÄLPAVSNITT</a></button>';
        }
        return '';
    }

    private static function get_link( $code ){

        $ref_table = self::get_ref_table();
        if( array_key_exists( $code, $ref_table ) ){
            return $ref_table[$code];
        }
    }

    private static function get_ref_table(){
        return array();
    }
}
