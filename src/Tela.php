<?php namespace GM;

class Tela {

    /**
     * @var array Contains the intsnace ids
     */
    private static $instances;

    /**
     * @var string Instance id
     */
    private $id;

    /**
     * @var string Default action class name
     */
    private $action_class;

    /**
     * @var mixed Variable to be passed to all the registered ajax callbacks
     */
    private $shared;

    /**
     * @var boolean Set to true when instance has been inited
     */
    private $init = FALSE;

    /**
     * @var array Contains all the registered actions
     */
    private $actions = [ ];

    /**
     * @var array Contains all salted and base64-encoded  nonces for registered action.
     */
    private $nonces = [ ];

    /**
     * @var string Salt string used to build nonces to be passed to browser via wp_localize_scripts
     */
    private $salt;

    /**
     * Retrieve a specific instance of Tela
     *
     * @param string $id
     * @return GM\Tela
     */
    static function instance( $id, $shared = NULL, $action_class = NULL ) {
        if ( ! is_string( $id ) ) {
            return $this->error( 'bad-id', 'Tela instance id must be a string.' );
        }
        if ( ! isset( self::$instances[ $id ] ) ) {
            $class = get_called_class();
            self::$instances[ $id ] = new $class( $shared, $action_class, $id );
            self::$instances[ $id ]->init();
        }
        return self::$instances[ $id ];
    }

    /**
     * Constructor
     *
     * @param mixed $shared Variable to be passed to every ajax callback
     * @param string $action_class Default class name for action objects (overridable on action basis)
     * @param string $id Instance id, if mitted will be auto-generated.
     */
    public function __construct( $shared = NULL, $action_class = '\GM\Faber\Action', $id = NULL ) {
        if ( empty( $id ) || ! is_string( $id ) ) {
            $id = uniqid( 'tela_' );
        }
        $this->id = $id;
        $this->action_class = $action_class;
        if ( ! is_null( $shared ) ) {
            $this->shared = $shared;
        }
    }

    /**
     * Getter for id properties.
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Init instance once on 'wp_loaded' hook, acts differently when called during an ajax request.
     */
    public function init() {
        if ( ! $this->init && ! ( did_action( 'wp_loaded' ) || doing_action( 'wp_loaded' ) ) ) {
            add_action( 'wp_loaded', function() {
                $this->init = TRUE;
                $id = $this->getId();
                $this->salt = wp_create_nonce( "tela_{$id}" );
                do_action( "tela_register_{$id}", $this );
                return $this->isAjax() ? $this->initAjax() : $this->initFront();
            }, 0 );
        }
    }

    /**
     * Method to be used to register ajax callbacks.
     * Should be called on "tela_register_{$id}" action hook or on "wp_loaded".
     *
     * @param string $action Action id, must be unique per tela instance
     * @param callable $callback Ajax callback
     * @param array $args Args to be passed to callback
     * @param GM\Tela\ActionInterface $action_class Class name to be used for action object instance
     * @return GM\Tela\ActionInterface
     */
    public function register( $action, $callback, Array $args = [ ], $action_class = '' ) {
        if ( ! $this->init ) {
            $id = $this->getId();
            $error = "Please use 'tela_register_{$id}' action to register your Tela callbacks.";
        }
        if ( ! is_string( $action ) || ! is_callable( $callback ) ) {
            $error = 'Please use string action name and valid callback when register a Tela callback.';
        }
        if ( isset( $error ) ) {
            return $this->error( 'bad-register-args', $error, compact( 'action', 'callback' ) );
        }
        try {
            if ( ! isset( $this->actions[ $action ] ) ) {
                return $this->buildAction( $action, $callback, $args, $action_class );
            }
        } catch ( \Exception $e ) {
            return $this->error( get_class( $e ), $e->getMessage(), $e );
        }
        return $this->actions[ $action ];
    }

    /**
     * Run requested registered ajax callback during ajax request.
     *
     * @return void
     */
    public function run() {
        $vars = $this->getRequestVars();
        if ( ! $this->isAjax() || ! $this->check( $vars ) ) {
            return $this->isAjax() ? $this->handleBadExit( $vars ) : NULL;
        }
        /**
         * @var GM\Tela\ActionInterface
         */
        $action = $this->getAction( $action );
        $sanitize_cb = $action->getVar( 'data_sanitize' );
        if ( is_callable( $sanitize_cb ) ) {
            $vars[ 'data' ] = call_user_func( $sanitize_cb, $vars[ 'data' ] );
        }
        $args = ! is_null( $this->shared ) ?
            [ $vars[ 'data' ], $this->shared ] :
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
     * Instantiate and return an action object.
     *
     * @param string $id Id to be set on the action object
     * @param string $class Class name for the action. class must implement GM\Tela\ActionInterface
     * @return \GM\Tela\ActionInterface
     */
    public function getActionInstance( $id, $class = NULL ) {
        $default = '\GM\Tela\Action';
        if ( empty( $class ) || ! is_string( $class ) ) {
            $class = $this->action_class ? : $default;
        }
        if ( ! class_exists( $class ) ) {
            $class = $default;
        } elseif ( $class !== $default ) {
            $ref = new \ReflectionClass( $class );
            if ( ! $ref->implementsInterface( '\GM\Tela\ActionInterface' ) ) {
                $class = $default;
            }
        }
        return new $class( $id );
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
     * Take an register action id and return the related action object (if exists).
     *
     * @param string $action
     * @return GM\Tela\ActionInterface|vois
     */
    public function getAction( $action ) {
        return $this->isAction( $action ) ? $this->actions[ $action ] : NULL;
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

    /* Internal Stuff */

    /**
     * Instantiate an action object and setup it according to argumants.
     *
     * @param string $action Action object id
     * @param callable $callback Callback for the action
     * @param array $args Arguments to be passed to action callback
     * @param string $action_class Action class name, class must implement GM\Tela\ActionInterface
     * @return void|GM\Tela\ActionInterface Return the action object during non-ajax requests
     * @access protected
     */
    protected function buildAction( $action, $callback, $args, $action_class ) {
        /**
         * @var GM\Tela\ActionInterface
         */
        $action_obj = $this->getActionInstance( $action, $action_class );
        $action_obj->setBlogId( get_current_blog_id() );
        $action_obj->setNonceSalt( $this->salt );
        $nonce = $action_obj->getNonce();
        $this->nonces[ $action ] = $nonce;
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            return $action_obj;
        }
        $action_obj->setCallback( $callback );
        $action_obj->setArgs( $args );
        $this->actions[ $action ] = $action_obj;
    }

    /**
     * During ajax request check posted vars and returns true if everything seems fine:
     * action is registered, user is logged in for private-only actions and nonce pass validation.
     *
     * @return boolean
     * @access private
     */
    private function check( $request = NULL ) {
        if ( ! $this->isAjax() || ! $this->checkUrlVars( $request ) ) {
            return FALSE;
        }
        if ( ! is_array( $request ) ) {
            $request = $this->getRequestVars();
        }
        /**
         * @var GM\Tela\ActionInterface
         */
        $action = $this->getAction( $request[ 'action' ] );
        if (
            ! $action instanceof Tela\ActionInterface
            || ( ! $action->getVar( 'access' ) && ! is_user_logged_in() )
            || $action->getBlogId() !== $request[ 'blogid' ]
        ) {
            return FALSE;
        }
        $salt = $action->getNonceSalt();
        $decoded_nonce = base64_decode( $this->nonces[ $request[ 'action' ] ] );
        $check = preg_replace( "#^{$salt}#", '', $decoded_nonce, 1 );
        return wp_verify_nonce( $check, $request[ 'action' ] );
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
     * When an action does not pass check for sanity (is regisered, pass nonce validation...) before
     * being ran, this method handle the flow quit
     *
     * @return void|boolean
     * @access private
     */
    private function handleBadExit( $vars = NULL ) {
        if ( ! is_array( $vars ) ) {
            $vars = $this->getRequestVars();
        }
        if ( isset( $vars[ 'is_tela' ] ) ) {
            // Chance to die() something different than default WordPress '0'
            $action = $this->getAction( $vars[ 'action' ] );
            do_action( 'tela_not_pass_check', $action, $vars, $this );
        }
        return FALSE;
    }

    /**
     * Init class on ajax requests
     *
     * @return void
     * @access private
     */
    private function initAjax() {
        if ( $this->checkUrlVars() ) {
            add_action( "wp_ajax_telaajax_proxy", [ $this, 'run' ] );
            add_action( "wp_ajax_nopriv_telaajax_proxy", [ $this, 'run' ] );
        }
    }

    /**
     * Init class on non-ajax requests
     *
     * @return void
     * @access private
     */
    private function initFront() {
        if ( wp_script_is( 'tela_ajax' ) ) {
            return;
        }
        $hook = is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
        add_action( $hook, function() {
            $min = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.min' : '';
            $relative = "js/tela_ajax{$min}.js";
            $path = plugin_dir_path( dirname( __FILE__ ) ) . $relative;
            $url = plugins_url( $relative, dirname( __FILE__ ) );
            $ver = @filemtime( $path ) ? : uniqid();
            $url_args = [
                'telaajax' => '1',
                'action'   => 'telaajax_proxy',
                'bid'      => get_current_blog_id()
            ];
            $data = [
                'ajax_url' => add_query_arg( $url_args, admin_url( 'admin-ajax.php' ) ),
                'nonces'   => (object) $this->nonces
            ];
            wp_enqueue_script( 'tela_ajax', $url, [ 'jquery' ], $ver, TRUE );
            wp_localize_script( 'tela_ajax', 'TelaAjaxData', $data );
        } );
    }

    /**
     * Get and sanitize Tela-related variables from $_GET and $_POST and return them in a single
     * indexd array.
     *
     * @return array
     * @access private
     */
    private function getRequestVars() {
        $posted = filter_input_array( INPUT_POST, [
            'telaajax_action' => FILTER_SANITIZE_STRING,
            'telaajax_nonce'  => FILTER_SANITIZE_STRING,
            'telaajax_data'   => FILTER_REQUIRE_ARRAY
            ] );
        $qs = filter_input_array( INPUT_GET, [
            'telaajax' => FILTER_SANITIZE_NUMBER_INT,
            'bid'      => FILTER_SANITIZE_NUMBER_INT,
            ] );
        return [
            'action'  => $posted[ 'telaajax_action' ],
            'nonce'   => $posted[ 'telaajax_nonce' ],
            'data'    => (array) $posted[ 'telaajax_data' ],
            'is_tela' => (int) $qs[ 'telaajax' ] > 0,
            'blogid'  => (int) $qs[ 'bid' ]
        ];
    }

    /**
     * Check if http request ($_GET and $_POST) contains valid Tela-related variables.
     *
     * @param array $vars Var array to check, if not given is retrieved from superglobals
     * @return boolean
     * @access private
     */
    private function checkUrlVars( $vars = NULL ) {
        if ( ! is_array( $vars ) ) {
            $vars = $this->getRequestVars();
        }
        return $vars[ 'is_tela' ]
            && ! empty( $vars[ 'action' ] )
            && ! empty( $vars[ 'nonce' ] )
            && ! empty( $vars[ 'blogid' ] );
    }

    /**
     * Check if current http request is an ajax one looking at WordPress constant or server var.
     *
     * @return boolean
     * @access private
     *
     */
    private function isAjax() {
        return ( defined( 'DOING_AJAX' ) && DOING_AJAX )
            || 'xmlhttprequest' === strtolower( filter_input(
                    INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', FILTER_SANITIZE_STRING
                )
        );
    }

    /**
     * Handle errors by returning an instance of \GM\Tela\Error that extends WP_Error.
     * Accepts all arguments to be passed to WP_Error constructor.
     *
     * @param string $code
     * @param string $message
     * @param mixed $data
     * @return \GM\Tela\Error
     * @access protected
     * @see http://codex.wordpress.org/Class_Reference/WP_Error
     */
    protected function error( $code = 'general', $message = '', $data = NULL ) {
        return new Tela\Error( "tela-error-{$code}", $message, $data );
    }

}