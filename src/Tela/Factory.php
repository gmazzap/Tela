<?php namespace GM\Tela;

class Factory {

    private static $types = [
        'action'    => [ '\GM\Tela\ActionInterface', '\GM\Tela\Action' ],
        'proxy'     => [ '\GM\Tela\ProxyInterface', '\GM\Tela\Proxy' ],
        'sanitizer' => [ '\GM\Tela\ArgsSanitizerInterface', '\GM\Tela\ArgsSanitizer' ],
        'jsmanager' => [ '\GM\Tela\JsManagerInterface', '\GM\Tela\JsManager' ],
        'checker'   => [ '\GM\Tela\AjaxCheckerInterface', '\GM\Tela\AjaxChecker' ]
    ];
    private static $registry = [ ];
    private $tela_id;

    /**
     * Set an instance of Tela in a property, it is used in filter
     *
     * @param string $tela_id
     */
    public function setTelaId( $tela_id ) {
        $this->tela_id = $tela_id;
    }

    /**
     * Getter for the current tela id
     *
     * @return string
     */
    public function getTelaId() {
        return $this->tela_id;
    }

    /**
     * Build an object based of a registered type, an optional class ans constructor arguments
     *
     * @param string $type Id for the allowed types
     * @param string $class Class name for the object, must implement the interface for the type
     * @param array $args Optional constructor arguments
     * @return \GM\Tela\Error
     */
    public function factory( $type, $class = '', Array $args = [ ] ) {
        $ref = $this->getReflection( $type, $class );
        try {
            return empty( $args ) ? $ref->newInstance() : $ref->newInstanceArgs( $args );
        } catch ( \Exception $e ) {
            return new Error( 'tela-bad-factory-' . get_class( $e ), $e->getMessage() );
        }
    }

    /**
     * Instantiate object of a registered type caching them for next calls.
     * Object storage is a dynamic property, so every instance (and so every tela instance)
     * has a separate storage.
     *
     * @param string $type Id for the allowed types
     * @param string $class Class name for the object, must implement the interface for the type
     * @param array $args optional constructor arguments
     * @return \GM\Tela\Error
     * @use \GM\Tela\Factory::factory()
     */
    public function get( $type, $class = NULL, Array $args = [ ] ) {
        if ( ! is_string( $type ) || ! array_key_exists( $type, self::$types ) ) {
            return new Error( 'tela-bad-factory-args', 'Bad factory arguments' );
        }
        if ( ! isset( $this->objects[ $type ] ) ) {
            $object = $this->factory( $type, $class, $args );
            if ( $object instanceof Error ) {
                return $object;
            }
            $this->objects[ $type ] = $object;
        }
        return $this->objects[ $type ];
    }

    /**
     * Instantiate object of a registered type caching them for next calls.
     * Object storage is a static property, so all instances (and so every tela instance) share
     * same storage.
     *
     * @param string $type Id for the allowed types
     * @param string $class Class name for the object, must implement the interface for the type
     * @param array $args optional constructor arguments
     * @return \GM\Tela\Error
     * @use \GM\Tela\Factory::factory()
     */
    public function registry( $type, $class = NULL, Array $args = [ ] ) {
        if ( ! isset( self::$registry[ $type ] ) ) {
            $object = $this->factory( $type, $class, $args );
            if ( $object instanceof Error ) {
                return $object;
            }
            self::$registry[ $type ] = $object;
        }
        return self::$registry[ $type ];
    }

    /**
     * Get the reflection class based on arguments passed to factory.
     * It ensure that optional class name passed to factory extend the interface for the given type.
     * Obtained reflecion class is also used to instantiate the required object.
     *
     * @param string $type Id for the allowed types
     * @param string $class Class name for the object, must implement the interface for the type
     * @return \ReflectionClass
     */
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

    /**
     * Testing utility, disabled in WordPress context
     */
    public static function flushRegistry() {
        if ( ! function_exists( 'the_post' ) && ! empty( self::$registry ) ) {
            foreach ( array_keys( self::$registry ) as $id ) {
                self::$registry[ $id ] = NULL;
            }
            self::$registry = [ ];
        }
    }

}