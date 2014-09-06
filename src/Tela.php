<?php namespace GM;

class Tela {

    const BACKEND = 10;
    const FRONTEND = 20;
    const BOTHSIDES = 30;

    /**
     * @var array Contains the intsnace ids
     */
    private static $instances = [ ];

    /**
     * @var string Handle for the main javascript file
     */
    private static $js_handle = '';

    /**
     * @var string Instance id
     */
    private $id;

    /**
     * @var GM\Tela\Factory
     */
    private $factory;

    /**
     * @var bool|callable Init the instance if this is TRUE (or returns TRUE)
     */
    private $when;

    /**
     * @var mixed Variable to be passed to all the registered ajax callbacks
     */
    private $shared;

    /**
     * @var int Set to true when instance has been inited
     */
    private $init = 0;

    /**
     * @var array Contains all the registered actions
     */
    private $actions = [ ];

    /**
     * @var int Count for registered action on current "side" (frontend / backend)
     */
    private $on_side = 0;

    /**
     * @var array Contains all salted and base64-encoded  nonces for registered action.
     */
    private $nonces = [ ];

    /**
     * @var string Salt string used to build nonces to be passed to browser via wp_localize_scripts
     */
    private $salt = '';

    /**
     * Retrieve a specific instance of Tela.
     *
     * @param string $id
     * @param bool|callable $when Init the instance id this is TRUE (or return TRUE)
     * @param mixed $shared a variable to be passed to all ajax registered actions.
     * @param GM\Tela\Factory $factory Factory to be used for the instance
     * @return GM\Tela|GM\Tela\Error
     */
    final static function instance( $id, $when = TRUE, $shared = NULL, Tela\Factory $factory = NULL ) {
        if ( ! is_string( $id ) ) {
            return $this->error( 'bad-id', 'Tela instance id must be a string.' );
        }
        if ( ! isset( self::$instances[ $id ] ) ) {
            $class = get_called_class();
            if ( is_null( $factory ) ) {
                $factory = new Tela\Factory;
            }
            $tela = new $class( $id, $when, $factory, $shared );
            if ( ! is_bool( $tela->when ) && ! is_callable( $tela->when ) ) {
                $tela->when = $when;
            }
            if ( $factory->getTelaId() !== $id ) {
                $factory->setTelaId( $id );
            }
            if ( ! isset( self::$instances[ $id ] ) || self::$instances[ $id ] !== $tela ) {
                self::$instances[ $id ] = $tela;
            }
            $tela->init();
        }
        return self::$instances[ $id ];
    }

    /**
     * Testing utility, disabled in WordPress context
     */
    public static function flush() {
        if ( ! function_exists( 'the_post' ) ) {
            self::$instances = [ ];
            self::$js_handle = NULL;
            Tela\Factory::flushRegistry();
        }
    }

    /**
     * Getter for handle object.
     *
     * @return string
     */
    public static function getJsHandle() {
        return self::$js_handle;
    }

    /**
     * Wrapper for wp_enqueue_script() can be used to quickly add js having Tela js as dependency.
     *
     * @param string $handle
     * @param string $url
     * @param array $deps
     * @param string $ver
     * @return void
     * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_script
     */
    public static function enqueueJs( $handle = '', $url = '', $deps = [ ], $ver = NULL ) {
        if (
            ! is_string( $handle )
            || ! filter_var( $url, FILTER_VALIDATE_URL )
            || ! is_string( self::getJsHandle() )
        ) {
            return;
        }
        $deps = array_filter( (array) $deps, 'is_string' );
        array_unshift( $deps, self::getJsHandle() );
        array_unshift( $deps, 'jquery' );
        wp_enqueue_script( $handle, $url, array_filter( array_unique( $deps ) ), $ver, TRUE );
    }

    /**
     * Constructor
     *
     * @param string $id
     * @param bool|callable $when Init the instance id this is TRUE (or return TRUE)
     * @param GM\Tela\Factory $factory Factory to be used for the instance
     * @param mixed $shared a variable to be passed to all ajax registered actions.
     */
    public function __construct( $id, $when, Tela\Factory $factory, $shared = NULL ) {
        if ( empty( $id ) || ! is_string( $id ) ) {
            $id = uniqid( 'tela_' );
        }
        $factory->setTelaId( $id );
        $this->id = $id;
        $this->when = $when;
        $this->factory = $factory;
        if ( ! is_null( $shared ) ) {
            $this->shared = $shared;
        }
    }

    /**
     * Getter for id property.
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Getter for factory object.
     *
     * @return \GM\Tela\Factory
     */
    public function getFactory() {
        return $this->factory;
    }

    /**
     * Getter for shared var.
     *
     * @return mixed
     */
    public function getShared() {
        return $this->shared;
    }

    /**
     * Check if a string is a valid action id
     *
     * @param string $i The string to check
     * @return boolean
     */
    public function isAction( $i ) {
        return is_string( $i )
            && isset( $this->actions[ $i ] )
            && $this->actions[ $i ] instanceof Tela\ActionInterface;
    }

    /**
     * Check if there are any registered action for current side
     *
     * @return boolean
     */
    public function hasActions() {
        return $this->on_side > 0 || ( $this->isAjax() && count( $this->actions ) > 0 );
    }

    /**
     * Take an register action id and return the related action object (if exists).
     *
     * @param string $action
     * @return GM\Tela\ActionInterface|vois
     */
    public function getAction( $action ) {
        return $this->isAction( $action ) ? $this->actions[ $action ] : NULL;
    }

    /**
     * Getter for salt string.
     *
     * @return string
     */
    public function getNonceSalt() {
        return $this->salt;
    }

    /**
     * Get a strored nonce fo a specific action id.
     *
     * @param string $action
     * @return string
     */
    public function getActionNonce( $action ) {
        return isset( $this->nonces[ $action ] ) ? $this->nonces[ $action ] : '';
    }

    /**
     * Check if the instance is allowed to run.
     */
    public function allowed() {
        $user = is_user_loggen_in() ? wp_get_current_user() : FALSE;
        return is_callable( $this->when ) ?
            (bool) call_user_func( $this->when, $this->isAjax(), $user, $this->getShared() ) :
             ! empty( $this->when );
    }

    /**
     * Check if the instance has been inited, and between init() is called an 'wp_loaded' ran return 1
     */
    public function inited() {
        return $this->init === 1 ? 1 : $this->init > 1;
    }

    /**
     * Init instance once on 'wp_loaded' hook, acts differently when called during an ajax request.
     */
    public function init() {
        if ( $this->init === 0 && ! ( did_action( 'wp_loaded' ) || doing_action( 'wp_loaded' ) ) ) {
            $this->init = 1;
            add_action( 'wp_loaded', [ $this, 'whenLoaded' ], 0 );
        }
    }

    /**
     * Launch the class initialization on wp_loaded hook
     *
     * @return void
     */
    public function whenLoaded() {
        if ( $this->inited() !== 1 || current_filter() !== 'wp_loaded' || ! $this->allowed() ) {
            return;
        }
        $this->init ++;
        remove_action( 'wp_loaded', [ $this, __FUNCTION__ ], 0 );
        $id = $this->getId();
        $this->salt = wp_create_nonce( "tela_{$id}" );
        do_action( "tela_register_{$id}", $this );
        $this->isAjax() ? $this->initAjax() : $this->initFront();
    }

    /**
     * Method to be used to register ajax callbacks.
     * Should be called on "tela_register_{$id}" action hook or on "wp_loaded".
     *
     * @param string $action Action id, must be unique for tela instance.
     * @param callable $callback Ajax callback
     * @param array $args Action args
     * @param GM\Tela\ActionInterface $action_class Class name to be used for action object instance
     * @return GM\Tela\ActionInterface
     */
    public function register( $action, $callback, Array $args = [ ], $action_class = '' ) {
        if ( ! $this->allowed() ) {
            return;
        }
        $action = $this->getId() . "::{$action}";
        $args = $this->sanitizeArgs( $args );
        if ( ! $this->isAjax() ) {
            return $this->registerOnFront( $action, $args );
        }
        $check = $this->checkRegisterVars( $action, $callback );
        if ( is_wp_error( $check ) ) {
            return $check;
        }
        try {
            $regitered = $this->buildAction( $action, $callback, $args, $action_class );
        } catch ( \Exception $e ) {
            $regitered = $this->error( 'tela-error-' . strtolower( get_class( $e ) ), $e->getMessage() );
        }
        return $regitered;
    }

    /**
     * Perform the callback associated to the required action.
     * Request is stopped (die) at end of the function.
     * Output is handled according to action settings.
     *
     * @param array $vars Request variables
     * @return void
     */
    public function performAction( Array $vars = [ ] ) {
        if ( ! $this->isAjax() || ! $this->allowed() || $this->check( $vars ) !== TRUE ) {
            return $this->isAjax() ? $this->handleBadExit( $vars ) : NULL;
        }
        /**
         * @var GM\Tela\ActionInterface
         */
        $action = $this->getAction( $vars[ 'action' ] );
        $sanitize_cb = $action->getVar( 'data_sanitize' );
        if ( is_callable( $sanitize_cb ) ) {
            $vars[ 'data' ] = call_user_func( $sanitize_cb, $vars[ 'data' ] );
        }
        $args = ! is_null( $this->getShared() ) ?
            [ $vars[ 'data' ], $this->getShared() ] :
            [ $vars[ 'data' ] ];
        ob_start();
        $data = call_user_func_array( $action->getCallback(), $args );
        $output = ob_get_clean();
        if ( empty( $data ) && ! empty( $output ) ) { // callbacks are echoing instead of return?
            $data = $output;
        }
        $this->handleExit( $action, $data );
    }

    /**
     * Check arguments sanity for register() method
     *
     * @param string $action
     * @param callable $callback
     * @return GM\Tela\Error|void
     */
    public function checkRegisterVars( $action, $callback ) {
        $error = '';
        if ( $this->inited() !== TRUE ) {
            $id = $this->getId();
            $error .= "Please use 'tela_register_{$id}' action to register your Tela callbacks.";
        }
        if ( ! is_string( $action ) || ! is_callable( $callback ) ) {
            $error .= $error === '' ? '' : ' ';
            $error .= 'Name and/or callback not valid to register a Tela callback.';
        }
        if ( ! empty( $error ) ) {
            return $this->error( 'register-error', $error, [ $action, $callback ] );
        }
    }

    /**
     * Ensure consistency for action arguments
     *
     * @param array $args
     * @return array
     */
    public function sanitizeArgs( Array $args, $validator_class = NULL ) {
        /** @var GM\Tela\ActionArgsValidatorInterface */
        $validator = $this->getFactory()->get( 'validator', $validator_class );
        if ( is_wp_error( $validator ) ) {
            return $validator;
        }
        return $validator->validate( $args );
    }

    /**
     * Take an action objetc id and a variable name and return the related action object property.
     *
     * @param string $action
     * @param string $i
     * @return mixed
     */
    public function getActionVar( $action, $i ) {
        /**
         * @var GM\Tela\ActionInterface
         */
        $action = $this->getAction( $action );
        return is_null( $action ) ? $action : $action->getVar( $i );
    }

    /**
     * Check if current http request is an ajax one looking at WordPress constant or server var.
     *
     * @return boolean
     * @access private
     *
     */
    public function isAjax() {
        return ( defined( 'DOING_AJAX' ) && DOING_AJAX )
            || 'xmlhttprequest' === strtolower( filter_input(
                    INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', FILTER_SANITIZE_STRING
                )
        );
    }

    /**
     * Check if current http request is an ajax one and tela variable is present
     *
     * @param mixed $vars
     * @return boolean
     */
    public function isTelaAjax( $vars = NULL ) {
        if ( ! $this->isAjax() ) {
            return FALSE;
        }
        if ( ! is_array( $vars ) ) {
            $vars = Tela\Proxy::getRequestVars();
        }
        return isset( $vars[ 'is_tela' ] ) && $vars[ 'is_tela' ];
    }

    /**
     * Handle errors by returning an instance of \GM\Tela\Error that extends WP_Error.
     * Accepts all arguments accpeted by WP_Error constructor.
     *
     * @param string $code
     * @param string $message
     * @param mixed $data
     * @return \GM\Tela\Error
     * @see http://codex.wordpress.org/Class_Reference/WP_Error
     */
    public function error( $code = 'general', $message = '', $data = NULL ) {
        return new Tela\Error( "tela-error-{$code}", $message, $data );
    }

################################################################################# INTERNAL STUFF

    /**
     *
     *
     * @param string $action Action id
     * @param array $args Action arguments
     */
    private function registerOnFront( $action, Array $args = [ ] ) {
        $not_on_side = is_admin() ? self::FRONTEND : self::BACKEND;
        if ( $args[ 'side' ] !== $not_on_side ) {
            $this->on_side ++;
            $this->nonces[ $action ] = $this->buildNonce( $action );
        }
    }

    /**
     * Init class on ajax requests
     *
     * @return void
     * @access private
     */
    private function initAjax() {
        if ( $this->inited() !== 2 ) {
            return;
        }
        $proxy = $this->getFactory()->registry( 'proxy', '', [ self::$instances ] );
        if ( is_wp_error( $proxy ) ) {
            return $proxy;
        }
        if ( $this->isTelaAjax() && ! has_action( Tela\Proxy::HOOK, [ $proxy, 'proxy' ] ) ) {
            add_action( Tela\Proxy::HOOK, [ $proxy, 'proxy' ] );
            add_action( Tela\Proxy::HOOKNOPRIV, [ $proxy, 'proxy' ] );
        }
    }

    /**
     * Init class on non-ajax requests
     *
     * @return void
     * @access private
     */
    private function initFront() {
        if ( ( $this->inited() !== 2 || ! $this->hasActions() ) ) {
            return;
        }
        $js_manager = $this->getFactory()->registry( 'jsmanager' );
        if ( is_wp_error( $js_manager ) ) {
            return $js_manager;
        }
        $js_manager->addNonces( $this->nonces );
        if ( ! $js_manager->enabled() ) {
            $js_manager->enable();
        }
    }

    /**
     * Instantiate an action object and setup it according to argumants.
     *
     * @param string $action Action object id
     * @param callable $callback Callback for the action
     * @param array $args Action arguments
     * @param string $action_class Action class name, class must implement GM\Tela\ActionInterface
     * @return void|GM\Tela\ActionInterface Return the action object during non-ajax requests
     * @access private
     */
    private function buildAction( $action, $callback, $args, $action_class ) {
        if ( $this->isAction( $action ) ) {
            return FALSE;
        }
        /** @var Tela\ActionInterface */
        $action_obj = $this->getFactory()->get( 'action', $action_class, [ $action ] );
        if ( is_wp_error( $action_obj ) ) {
            return $action_obj;
        }
        $action_obj->setBlogId( get_current_blog_id() );
        $nonce = $this->buildNonce( $action );
        $action_obj->setNonce( $nonce );
        $action_obj->setCallback( $callback );
        $action_obj->setArgs( $args );
        $this->nonces[ $action ] = $nonce;
        $this->actions[ $action ] = $action_obj;
        return $action_obj;
    }

    /**
     * Build a nonce for the action
     *
     * @param string $action Action id
     * @return string
     */
    private function buildNonce( $action ) {
        return base64_encode( $this->salt . wp_create_nonce( $action ) );
    }

    /**
     * During ajax request check posted vars and returns true if everything seems fine:
     * action is registered, user is logged in for private-only actions and nonce pass validation.
     *
     * @return boolean
     * @access private
     */
    private function check( Array $vars = [ ], $checker_class = NULL ) {
        if ( empty( $vars ) ) {
            $vars = Tela\Proxy::getRequestVars();
        }
        $action = isset( $vars[ 'action' ] ) ? $this->getAction( $vars[ 'action' ] ) : FALSE;
        if ( ! $action instanceof Tela\ActionInterface ) {
            return FALSE;
        }
        /** @var Tela\AjaxCheckerInterface */
        $checker = $this->getFactory()->get( 'checker', $checker_class, [ $vars, $action ] );
        if ( is_wp_error( $checker ) ) {
            return $checker;
        }
        return $checker->checkRequest() && $checker->checkNonce( $this->salt );
    }

    /**
     * After an ajax callback ran, return / echo its output according to action settings, than exit.
     *
     * @param GM\Tela\ActionInterface $action
     * @param mixed $data
     * @return void
     * @access private
     */
    private function handleExit( Tela\ActionInterface $action, $data ) {
        $json = $action->getVar( 'json' );
        if ( empty( $json ) ) {
            wp_die( $data );
        }
        if ( is_callable( $json ) ) {
            if ( call_user_func( $json, $data ) ) {
                wp_send_json_success( $data );
            } else {
                wp_send_json_error( $data );
            }
        }
        wp_send_json( $data );
    }

    /**
     * When an action does not pass check for sanity (is registered, pass nonce validation...) before
     * being ran, this method handle the flow quit
     *
     * @return void|boolean
     * @access private
     */
    private function handleBadExit( $vars = NULL ) {
        if ( ! is_array( $vars ) ) {
            $vars = Tela\Proxy::getRequestVars();
        }
        if ( $this->isTelaAjax( $vars ) ) {
            $action = $this->getAction( $vars[ 'action' ] );
            // Chance to die() something different than default
            do_action( 'tela_not_pass_check', $action, $vars, $this );
            wp_die( '' );
        }
        return FALSE;
    }

}