<?php namespace GM;

class Tela {

    private static $instances;
    private $id;
    private $action_class;
    private $shared;
    private $init = FALSE;
    private $actions = [ ];
    private $nonces = [ ];
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
        }
        return self::$instances[ $id ];
    }

    public function __construct( $shared = NULL, $action_class = '\GM\Faber\Action', $id = NULL ) {
        if ( empty( $id ) || ! is_string( $id ) ) {
            $this->id = uniqid( 'tela_' );
        }
        $this->action_class = $action_class;
        if ( ! is_null( $shared ) ) {
            $this->shared = $shared;
        }
    }

    public function getId() {
        return $this->id;
    }

    public function init() {
        if ( ! $this->init ) {
            $this->init = TRUE;
            $this->salt = wp_create_nonce( "tela_" . $this->getId() );
            return defined( 'DOING_AJAX' ) && DOING_AJAX ? $this->initAjax() : $this->initFront();
        }
    }

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
                $action_obj = $this->getActionInstance( $action, $action_class );
                $action_obj->setBlogId( get_current_blog_id() );
                $action_obj->setNonceSalt( $this->salt );
                $action_obj->setCallback( $callback );
                $action_obj->setArgs( $args );
                $nonce = $action_obj->getNonce();
                $this->actions[ $action ] = $action_obj;
                $this->nonces[ $action ] = $nonce;
            }
        } catch ( \Exception $e ) {
            return $this->error( get_class( $e ), $e->getMessage(), $e );
        }
        return $this->actions[ $action ];
    }

    public function run() {
        if ( ! ( $vars = $this->check() ) ) {
            return;
        }
        $sanitize_cb = $this->getActionVar( $vars[ 'action' ], 'data_sanitize' );
        if ( is_callable( $sanitize_cb ) ) {
            $vars[ 'data' ] = call_user_func( $sanitize_cb, $vars[ 'data' ] );
        }
        $args = ! is_null( $this->shared ) ?
            [ $vars[ 'data' ], $this->shared ] :
            [ $vars[ 'data' ] ];
        $callback = $this->actions[ $vars[ 'action' ] ]->getCallback();
        ob_start();
        $data = call_user_func_array( $callback, $args );
        $output = ob_get_clean();
        $this->handleExit( $vars[ 'action' ], $data, $output );
    }

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

    /* Internal Stuff */

    private function check() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! $this->checkUrlVars() ) {
            return FALSE;
        }
        $request = $this->getRequestVars();
        $action = $this->getAction( $request[ 'action' ] );
        if (
            ! $action instanceof Tela\ActionInterface
            || ( ! $action->getVar( 'access' ) && ! is_user_logged_in() )
            || $action->getBlogId() !== $request[ 'blogid' ]
        ) {
            return FALSE;
        }
        $salt = $action->getNonceSalt();
        $decoded_nonce = base64_decode( $this->nonces[ $request[ 'nonce' ] ] );
        $check = preg_replace( "#^{$salt}#", '', $decoded_nonce, 1 );
        return wp_verify_nonce( $check, $request[ 'action' ] ) ? $request : FALSE;
    }

    private function handleExit( $action, $data, $output = '' ) {
        $json = $this->getAction( $action )->getVar( 'json' );
        if ( empty( $json ) ) {
            wp_die( $output );
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

    private function initAjax() {
        if ( $this->checkUrlVars() ) {
            $id = $this->getId();
            do_action( "tela_register_{$id}", $this );
            add_action( "wp_ajax_telaajax_proxy", [ $this->getProxy(), 'run' ] );
            add_action( "wp_ajax_nopriv_telaajax_proxy", [ $this->getProxy(), 'run' ] );
        }
    }

    private function initFront() {
        if ( wp_script_is( 'tela_ajax' ) ) {
            return;
        }
        $hook = is_admin() ? 'admin_enqueue_scripts' : ' wp_enqueue_scripts';
        add_action( $hook, function() {
            $min = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.min' : '';
            $relative = "js/tela_ajax{$min}.js";
            $path = plugins_dir_path( __FILE__ ) . $relative;
            $url = plugins_url( $relative, __FILE__ );
            $ver = @filemtime( $path ) ? : uniqid();
            $url_args = [
                'telaajax' => '1',
                'action'   => 'telaajax_proxy',
                'bid'      => get_current_blog_id()
            ];
            $data = (object) [
                    'ajax_url' => add_query_arg( $url_args, admin_url( 'admin-ajax.php' ) ),
                    'nonces'   => (object) $this->nonces
            ];
            wp_enqueue_script( 'tela_ajax', $url, [ 'jquery' ], $ver, TRUE );
            wp_localize_script( 'tela_ajax', 'TelaAjaxData', $data );
        } );
    }

    private function isAction( $i ) {
        return isset( $this->actions[ $i ] ) && $this->actions[ $i ] instanceof Tela\ActionInterface;
    }

    private function getAction( $action ) {
        return $this->isAction( $action ) ? $this->actions[ $action ] : NULL;
    }

    private function getActionVar( $action, $i ) {
        $action = $this->getAction( $action );
        return is_null( $action ) ? $action : $action->getVar( $i );
    }

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
            'action' => $posted[ 'telaajax_action' ],
            'nonce'  => $posted[ 'telaajax_nonce' ],
            'data'   => (array) $posted[ 'telaajax_data' ],
            'is'     => (int) $qs[ 'telaajax' ] > 0,
            'blogid' => (int) $qs[ 'bid' ]
        ];
    }

    private function checkUrlVars() {
        $vars = $this->getRequestVars();
        return $vars[ 'is' ]
            && ! empty( $vars[ 'action' ] )
            && ! empty( $vars[ 'nonce' ] )
            && ! empty( $vars[ 'bid' ] );
    }

    private function error( $code = 'general', $message = '', $data = NULL ) {
        return new Tela\Error( 'tela-error-' . $code, $message, $data );
    }

}