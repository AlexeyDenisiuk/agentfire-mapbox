<?php

    namespace AGFM;

    /**
     * Different helper functions 
     */
    class Helpers
    {
        /**
         * Check whether the string in '$data' is JSON
         * 
         * @param string $data Data to check
         * @return boolean 
         */
        static function is_json( string $data )
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
         * @return boolean Returns 'true' if the function was succefully completed
         */
        static function dd( $variable )
        {
            echo '<pre>';
            print_r( $variable );
            echo '</pre>';
            die();

            // return that the function successfully completed
            return true;
        }

        /**
         * Print variable
         * 
         * @param mixed $variable Data to print
         * @return boolean Returns 'true' if the function was succefully completed
         */
        static function d( $variable )
        {
            echo '<pre>';
            print_r( $variable );
            echo '</pre>';

            // return that the function successfully completed
            return true;
        }

        /**
         * Translate the string
         * 
         * @param string $str String to translate
         * @return string Translated string
         */
        static function __( string $str )
        {
            return __( $str, AGFM_TEXTDOMAIN );
        }

    }

