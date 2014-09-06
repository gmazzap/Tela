<?php namespace GM\Tela;

class Proxy implements ProxyInterface {

    private $tela_instances;
    private $request;
    private $run = FALSE;

    public function __construct( Array $tela_instances, Request $request = NULL ) {
        $this->tela_instances = $tela_instances;
        if ( ! is_null( $request ) ) {
            $this->setRequest( $request );
        }
    }

    public function getRequest() {
        return $this->request;
    }

    public function setRequest( Request $request ) {
        $this->request = $request;
    }

    public function proxy() {
        if ( $this->run || ! in_array( current_filter(), [ self::HOOK, self::HOOKNOPRIV ], TRUE ) ) {
            return;
        }
        $this->run = TRUE;
        remove_action( self::HOOK, [ $this, __FUNCTION__ ] );
        remove_action( self::HOOKNOPRIV, [ $this, __FUNCTION__ ] );
        $vars = $this->getRequest()->get();
        $action = explode( '::', $vars[ 'action' ] );
        if ( isset( $action[ 0 ] ) && array_key_exists( $action[ 0 ], $this->tela_instances ) ) {
            $done = $this->tela_instances[ $action[ 0 ] ]->performAction( $vars );
            $this->tela_instances = [ ];
            return $done;
        }
    }

}