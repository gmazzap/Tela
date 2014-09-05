<?php
if ( ! class_exists( 'WP_Error' ) ) {

    class WP_Error {

        public $errors = [ ];

        function __construct( $code = '', $message = '', $args = '' ) {
            $this->add( $code, $message, $args );
        }

        function add( $code = '', $message = '', $args = '' ) {
            if ( ! isset( $this->errors[ $code ] ) ) {
                $this->errors[ $code ] = [ ];
            }
            $this->errors[ $code ][] = [ 'code' => $code, 'message' => $message, 'data' => $args ];
        }

    }
}

if ( ! function_exists( 'is_wp_error' ) ) {

    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }

}

if ( ! function_exists( 'get_current_blog_id' ) ) {

    function get_current_blog_id() {
        return 1;
    }

}

if ( ! function_exists( 'wp_die' ) ) {

    function wp_die() {
        return;
    }

}