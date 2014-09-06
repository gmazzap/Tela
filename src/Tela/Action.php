<?php namespace GM\Tela;

class Action implements ActionInterface {

    private $id;
    private $blogid;
    private $args;
    private $callback;
    private $nonce;
    private $sanitizer;

    /**
     * @var array Default action arguments
     */
    protected static $defaults = [
        'public'        => FALSE,
        'data_sanitize' => NULL,
        'send_json'     => TRUE,
        'json_validate' => NULL
    ];

    /**
     * Constructor
     *
     * @param string $id Action id
     * @param array $args Action arguments to optionally set on action instantiation
     */
    public function __construct( $id, Array $args = [ ] ) {
        $this->setId( $id );
        if ( ! empty( $args ) ) {
            $this->setArgs( $args );
        }
    }

    /**
     * Getter for the id.
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Getter for action-stored blog id.
     *
     * @return string
     */
    public function getBlogId() {
        return $this->blogid;
    }

    /**
     * Getter for the action callback.
     *
     * @return callable
     */
    public function getCallback() {
        return $this->callback;
    }

    /**
     * Get a variable from action context
     *
     * @param string $var Variable to get
     * @return mized
     * @throws \InvalidArgumentException
     */
    public function getVar( $var = '' ) {
        if ( ! is_string( $var ) ) {
            throw new \InvalidArgumentException;
        }
        return isset( $this->args[ $var ] ) ? $this->args[ $var ] : NULL;
    }

    /**
     * Getter for action nonce.
     *
     * @return string
     */
    public function getNonce() {
        return $this->nonce;
    }

    /**
     * Check if current action is public i.e. available in frontend for non logged users.
     * @return boolean
     */
    public function isPublic() {
        return (bool) $this->getVar( 'public' );
    }

    /**
     * Setter for action id.
     *
     * @param string $id
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     */
    public function setId( $id ) {
        if ( ! is_string( $id ) ) {
            throw new \InvalidArgumentException;
        }
        $this->id = $id;
        return $this;
    }

    /**
     * Setter for action blog id.
     *
     * @param string $id
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     */
    public function setBlogId( $id ) {
        if ( ! is_numeric( $id ) ) {
            throw new \InvalidArgumentException;
        }
        $this->blogid = (int) $id;
        return $this;
    }

    /**
     * Setter for action callback.
     *
     * @param callable $callback
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     */
    public function setCallback( $callback ) {
        if ( ! is_callable( $callback ) ) {
            throw new \InvalidArgumentException;
        }
        $this->callback = $callback;
        return $this;
    }

    /**
     * Set a variable in the action context.
     *
     * @param string $var
     * @param mixed $value
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     */
    public function setVar( $var, $value = NULL ) {
        if ( ! is_string( $var ) ) {
            throw new \InvalidArgumentException;
        }
        $this->args[ $var ] = $value;
        $this->sanitize();
        return $this;
    }

    /**
     * Set an array of variables in the action context.
     * Can be used only if no arguments is already set, to update arguments use `setVar` or one
     * of the specific setter.
     *
     * @param array $args
     * @return \GM\Tela\Action
     */
    public function setArgs( Array $args = [ ] ) {
        if ( empty( $this->args ) ) {
            $args = wp_parse_args( $args, static::$defaults );
            $json = is_callable( $args[ 'json_validate' ] ) ?
                $args[ 'json_validate' ] :
                $args[ 'send_json' ];
            unset( $args[ 'json_validate' ] );
            unset( $args[ 'send_json' ] );
            $this->args = array_merge( $args, [ 'json' => $json ] );
            $this->sanitize();
        }
        return $this;
    }

    /**
     * setter for action nonce.
     *
     * @param string $nonce
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     */
    public function setNonce( $nonce ) {
        if ( ! is_string( $nonce ) ) {
            throw new \InvalidArgumentException;
        }
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * Ensure consistency for action arguemnts
     */
    public function sanitize() {
        $this->args = $this->getSanitizer()->sanitize( $this->args );
    }

    /**
     * A specific setter for 'public' argument in action context.
     *
     * @param boolean $is
     * @return \GM\Tela\Action
     * @uses \GM\Tela\Action::setVar()
     */
    public function setPublicAccess( $is = TRUE ) {
        return $this->setVar( 'public',  ! empty( $is ) );
    }

    /**
     * A specific setter for 'data_sanitize' argument in action context.
     *
     * @param callable $callable
     * @return \GM\Tela\Action
     * @throws \InvalidArgumentException
     * @uses \GM\Tela\Action::setVar()
     */
    public function sanitizeUsing( $callable ) {
        if ( ! is_callable( $callable ) ) {
            throw new \InvalidArgumentException;
        }
        return $this->setVar( 'data_sanitize', $callable );
    }

    /**
     * A specific setter for 'json_validate' argument in action context.
     * First argument is used to set if the action should return json data or not.
     * Second optional argument is callback to be used to check the json data: when set and
     * returns a falsey value `wp_send_json_error` is used, otherwise `wp_send_json_success`.
     * If callback is omitted and firs argument is true `wp_send_json` is used.
     *
     * @param boolean $return If the action should return json data or not
     * @param callable $validate sanitize callback
     * @return \GM\Tela\Action
     * @uses \GM\Tela\Action::setVar()
     * @see http://codex.wordpress.org/Function_Reference/wp_send_json_success
     * @see http://codex.wordpress.org/Function_Reference/wp_send_json_error
     * @see http://codex.wordpress.org/Function_Reference/wp_send_json
     */
    public function setJsonResponse( $return = TRUE, $validate = NULL ) {
        $json = $return && is_callable( $validate ) ? $validate :  ! empty( $return );
        return $this->setVar( 'json_validate', $json );
    }

    /**
     * Set a sanitizer object to be used for the action
     *
     * @param \GM\Tela\ArgsSanitizerInterface $sanitizer
     * @return \GM\Tela\Action
     */
    public function setSanitizer( ArgsSanitizerInterface $sanitizer = NULL ) {
        $this->sanitizer = $sanitizer;
        return $this;
    }

    /**
     * Get the saniter instance stored in the action.
     *
     * @return \GM\Tela\ArgsSanitizerInterface|void
     */
    public function getSanitizer() {
        if ( is_null( $this->sanitizer ) ) {
            $this->sanitizer = new ArgsSanitizer;
        }
        return $this->sanitizer;
    }

}