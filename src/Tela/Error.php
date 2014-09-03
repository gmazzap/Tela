<?php namespace GM\Tela;

class Error extends \WP_Error {

    function __call( $name, $arguments ) {
        $code = "tela-error-bad-{$name}-call";
        $message = "The function {$name} was called on a Tela error object.";
        $this->add( $code, $message, $arguments );
        return $this;
    }

    function __toString() {
        return 'ERROR: ' . $this->get_error_message();
    }

}