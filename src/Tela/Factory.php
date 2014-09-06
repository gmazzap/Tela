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
     * Set an instance of Tela in a property, it is used in filter.
     *
     * @param string $tela_id
     */
    public function setTelaId( $tela_id ) {
        $this->tela_id = $tela_id;
    }

    /**
     * Getter for the current tela id.
     *
     * @return string
     */
    public function getTelaId() {
        return $this->tela_id;
    }

    /**
     * Check if a type id is valid.
     *
     * @param string $type Type id to check
     * @return boolean
     */
    public function hasType( $type ) {
        return is_string( $type ) && array_key_exists( $type, self::$types );
    }

    /**
     *  Get all registerd type ids.
     *
     * @return array
     */
    public function getTypes() {
        return array_keys( self::types );
    }

    /**
     * Check if a type id is valid and related object is stored in registry.
     *
     * @param string $type Type id to check
     * @return boolean
     */
    public function hasRegistry( $type ) {
        if ( ! $this->hasType( $type ) ) {
            return;
        }
        $interface = self::$types[ $type ][ 0 ];
        return isset( self::$registry[ $type ] ) && self::$registry[ $type ] instanceof $interface;
    }

    /**
     * Allow to register an additional object type.
     *
     * @param string $id Id for the type
     * @param string $interface Interface name
     * @param string $default_class Default class name for the type
     */
    public function registerType( $id, $interface, $default_class ) {
        if (
            ! isset( self::$types[ $id ] )
            && is_string( $id ) && is_string( $interface ) && is_string( $default_class )
            && interface_exists( $interface ) && class_exists( $default_class )
        ) {
            self::$types[ $id ] = [ $interface, $default_class ];
        }
    }

    /**
     * Build an object based of a registered type, an optional class ans constructor arguments.
     *
     * @param string $type Id for the allowed types
     * @param string $class Class name for the object, must implement the interface for the type
     * @param array $args Optional constructor arguments
     * @return \GM\Tela\Error
     */
    public function factory( $type, $class = '', Array $args = [ ] ) {
        $check = $this->checkArgs( $type, $class );
        if ( is_wp_error( $check ) ) {
            return $check;
        }
        $ref = $this->getReflection( $type, $class );
        try {
            return empty( $args ) ? $ref->newInstance() : $ref->newInstanceArgs( $args );
        } catch ( \Exception $e ) {
            return new Error( 'tela-bad-factory-' . strtolower( get_class( $e ) ), $e->getMessage() );
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
        $check = $this->checkArgs( $type, $class );
        if ( is_wp_error( $check ) ) {
            return $check;
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
        $check = $this->checkArgs( $type, $class );
        if ( is_wp_error( $check ) ) {
            return $check;
        }
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
        $id = $this->getTelaId();
        $class = apply_filters( "tela_factory_{$id}_{$type}", $class );
        if ( empty( $class ) || ! is_string( $class ) || ! class_exists( $class ) ) {
            $class = $default;
        }
        $ref = new \ReflectionClass( $class );
        if ( ( $class !== $default ) && ! $ref->implementsInterface( $interface ) ) {
            $ref = new \ReflectionClass( $default );
        }
        return $ref;
    }

    private function checkArgs( $type, $class ) {
        if ( ! is_string( $type ) || ! array_key_exists( $type, self::$types ) ) {
            return new Error( 'tela-bad-factory-args', 'Bad factory arguments' );
        }
    }

    /**
     * Testing utility, disabled in WordPress context.
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