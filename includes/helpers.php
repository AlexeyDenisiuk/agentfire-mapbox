<?php

    namespace AGFM\Helpers;

    class AGFM_Helpers
    {
        /**
         * Check whether the variable is JSON
         * 
         * @param string $data
         * return boolean 
         */
        static function is_json( $data )
        {
            if ( !empty( $data ) )
            {
                @json_decode( $data );
                return ( json_last_error() === JSON_ERROR_NONE );
            }
            return false;
        }

        /**
         * Print variable and die
         * 
         * @param mixed $variable
         */
        static function dd( $variable )
        {
            echo '<pre>';
            print_r( $variable );
            echo '</pre>';
            die();
        }

        /**
         * Print variable
         * 
         * @param mixed $variable
         */
        static function d( $variable )
        {
            echo '<pre>';
            print_r( $variable );
            echo '</pre>';
        }

        /**
         * Translate the string
         * 
         * @param string $str
         * return string 
         */
        static function __( $str )
        {
            return __( $str, AGFM_TEXTDOMAIN );
        }

    }

