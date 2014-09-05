<?php namespace GM\Tela;

class Proxy implements ProxyInterface {

    private $tela_instances;

    /**
     * Get and sanitize Tela-related variables from $_GET and $_POST and return them in a single
     * indexed array.
     *
     * @return array
     */
    public static function getRequestVars() {
        $input_post = filter_input_array( INPUT_POST, [
            'telaajax_action'   => FILTER_SANITIZE_STRING,
            'telaajax_nonce'    => FILTER_SANITIZE_STRING,
            'telaajax_is_admin' => FILTER_SANITIZE_NUMBER_INT,
            'telaajax_data'     => FILTER_REQUIRE_ARRAY
            ] );
        $input_get = filter_input_array( INPUT_GET, [
            'telaajax' => FILTER_SANITIZE_NUMBER_INT,
            'bid'      => FILTER_SANITIZE_NUMBER_INT,
            ] );
        return [
            'action'     => $input_post[ 'telaajax_action' ],
            'nonce'      => $input_post[ 'telaajax_nonce' ],
            'data'       => (array) $input_post[ 'telaajax_data' ],
            'from_admin' => (int) $input_post[ 'telaajax_is_admin' ] > 0,
            'is_tela'    => (int) $input_get[ 'telaajax' ] > 0,
            'blogid'     => (int) $input_get[ 'bid' ]
        ];
    }

    /**
     * Constructot
     *
     * @param array $tela_instances All registered instances of Tela
     */
    public function __construct( Array $tela_instances ) {
        $this->tela_instances = $tela_instances;
    }

    /**
     * Proxy ajax action to right tela instance looking at on action id
     *
     * @return void
     */
    public function proxy() {
        if (
            ( did_action( self::HOOK ) && ! doing_action( self::HOOK ) )
            || ( did_action( self::HOOKNOPRIV ) && ! doing_action( self::HOOKNOPRIV ) )
        ) {
            return;
        }
        remove_action( self::HOOK, [ $this, __FUNCTION__ ] );
        remove_action( self::HOOKNOPRIV, [ $this, __FUNCTION__ ] );
        $vars = self::getRequestVars();
        $action = explode( '::', $vars[ 'action' ] );
        if ( isset( $action[ 0 ] ) && array_key_exists( $action[ 0 ], $this->tela_instances ) ) {
            $done = $this->tela_instances[ $action[ 0 ] ]->performAction( $vars );
            $this->tela_instances = [ ];
            return $done;
        }
    }

}