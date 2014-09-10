<?php namespace GM\Tela;

class Request implements RequestInterface {

    private $vars;

    function get() {
        if ( ! is_array( $this->vars ) ) {
            $input_post = filter_input_array( INPUT_POST, [
                'telaajax_action'   => FILTER_SANITIZE_STRING,
                'telaajax_nonce'    => FILTER_SANITIZE_STRING,
                'telaajax_is_admin' => FILTER_SANITIZE_NUMBER_INT,
                'telaajax_data'     => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'flags'  => FILTER_REQUIRE_ARRAY,
                ],
                ] );
            $input_get = filter_input_array( INPUT_GET, [
                'telaajax' => FILTER_SANITIZE_NUMBER_INT,
                'bid'      => FILTER_SANITIZE_NUMBER_INT,
                ] );
            $this->vars = [
                'action'     => $input_post[ 'telaajax_action' ],
                'nonce'      => $input_post[ 'telaajax_nonce' ],
                'data'       => (array) $input_post[ 'telaajax_data' ],
                'from_admin' => (int) $input_post[ 'telaajax_is_admin' ] > 0,
                'is_tela'    => (int) $input_get[ 'telaajax' ] > 0,
                'blogid'     => (int) $input_get[ 'bid' ]
            ];
        }
        return $this->vars;
    }

}