<?php
if ( ! class_exists( 'WP_Error' ) ) {

    class WP_Error {

        public $errors = [ ];

        function __construct( $code = '', $message = '', $arguments = '' ) {
            $this->add( $code, $message, $arguments );
        }

        function add( $code = '', $message = '', $arguments = '' ) {
            if ( ! isset( $this->errors[ $code ] ) ) {
                $this->errors[ $code ] = [ ];
            }
            $this->errors[ $code ][] = [ 'code' => $code, 'message' => $message, 'data' => $arguments ];
        }

    }
}