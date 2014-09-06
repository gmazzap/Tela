<?php namespace GM\Tela;

class Action implements ActionInterface {

    private $id;
    private $blogid;
    private $args;
    private $callback;
    private $nonce;
    private $validator;

    /**
     * @var array Default action arguments
     */
    protected static $defaults = [
        'public'        => FALSE,
        'data_sanitize' => NULL,
        'send_json'     => TRUE,
        'json_validate' => NULL
    ];

    public function __construct( $id, Array $args = [ ] ) {
        $this->setId( $id );
        if ( ! empty( $args ) ) {
            $this->setArgs( $args );
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getBlogId() {
        return $this->blogid;
    }

    public function getCallback() {
        return $this->callback;
    }

    public function getVar( $var = '' ) {
        if ( ! is_string( $var ) ) {
            throw new \InvalidArgumentException;
        }
        return isset( $this->args[ $var ] ) ? $this->args[ $var ] : NULL;
    }

    public function getNonce() {
        return $this->nonce;
    }

    public function isPublic() {
        return (bool) $this->getVar( 'public' );
    }

    public function setId( $id ) {
        if ( ! is_string( $id ) ) {
            throw new \InvalidArgumentException;
        }
        $this->id = $id;
        return $this;
    }

    public function setBlogId( $id ) {
        if ( ! is_numeric( $id ) ) {
            throw new \InvalidArgumentException;
        }
        $this->blogid = (int) $id;
        return $this;
    }

    public function setCallback( $callback ) {
        if ( ! is_callable( $callback ) ) {
            throw new \InvalidArgumentException;
        }
        $this->callback = $callback;
        return $this;
    }

    public function setVar( $var, $value = NULL ) {
        if ( ! is_string( $var ) ) {
            throw new \InvalidArgumentException;
        }
        $this->args[ $var ] = $value;
        $this->validate();
        return $this;
    }

    public function setArgs( Array $args = [ ] ) {
        if ( empty( $this->args ) ) {
            $args = wp_parse_args( $args, static::$defaults );
            $json = is_callable( $args[ 'json_validate' ] ) ?
                $args[ 'json_validate' ] :
                $args[ 'send_json' ];
            unset( $args[ 'json_validate' ] );
            unset( $args[ 'send_json' ] );
            $this->args = array_merge( $args, [ 'json' => $json ] );
            $this->validate();
        }
        return $this;
    }

    public function setNonce( $nonce ) {
        if ( ! is_string( $nonce ) ) {
            throw new \InvalidArgumentException;
        }
        $this->nonce = $nonce;
        return $this;
    }

    public function validate() {
        $this->args = $this->getValidator()->validate( $this->args );
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
     * Set a validator object to be used for the action
     *
     * @param \GM\Tela\ActionArgsValidatorInterface $validator
     * @return \GM\Tela\Action
     */
    public function setValidator( ActionArgsValidatorInterface $validator = NULL ) {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Get the validator instance stored in the action (or instantiate and return it).
     *
     * @return \GM\Tela\ActionArgsValidatorInterface|void
     */
    public function getValidator() {
        if ( is_null( $this->validator ) ) {
            $this->validator = new ActionArgsValidator;
        }
        return $this->validator;
    }

}