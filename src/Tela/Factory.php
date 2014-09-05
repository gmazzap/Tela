<?php namespace GM\Tela;

class Factory {

    private static $types = [
        'action'    => [ '\GM\Tela\ActionInterface', '\GM\Tela\Action' ],
        'sanitizer' => [ '\GM\Tela\ArgsSanitizerInterface', '\GM\Tela\ArgsSanitizer' ],
        'jsmanager' => [ '\GM\Tela\JsManagerInterface', '\GM\Tela\JsManager' ],
		'checker' 	=> [ '\GM\Tela\AjaxCheckerInterface', '\GM\Tela\AjaxChecker' ],
    ];
    private $tela_id;
    private $objects = [ ];

    public function setTelaId( $tela_id ) {
        $this->tela_id = $tela_id;
    }

    public function getTelaId() {
        return $this->tela_id;
    }

    public function factory( $type, $class = NULL, Array $args = [ ] ) {
        if ( ! is_string( $type ) || ! array_key_exists( $type, self::$types ) ) {
            return new Error( 'tela-bad-factory-args', 'Bad factory arguments' );
        }
        if ( ! isset( $this->objects[ $type ] ) ) {
            $ref = $this->getReflection( $type, $class );
            try {
                $object = empty( $args ) ? $ref->newInstance() : $ref->newInstanceArgs( $args );
                $this->objects[ $type ] = $object;
            } catch ( \Exception $e ) {
                return new Error( 'tela-bad-factory-' . get_class( $e ), $e->getMessage() );
            }
        }
        return $this->objects[ $type ];
    }

    private function getReflection( $type, $class ) {
        $default = self::$types[ $type ][ 1 ];
        $interface = self::$types[ $type ][ 0 ];
        $class = apply_filters( "tela_factory_{$this->tela_id}_{$type}", $class );
        if ( empty( $class ) || ! is_string( $class ) || ! class_exists( $class ) ) {
            $class = $default;
        }
        $ref = new \ReflectionClass( $class );
        if ( ( $class !== $default ) && ! $ref->implementsInterface( $interface ) ) {
            $ref = new \ReflectionClass( $default );
        }
        return $ref;
    }

}