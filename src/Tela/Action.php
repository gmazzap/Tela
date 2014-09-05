<?php namespace GM\Tela;

class Action implements ActionInterface {

    protected static $defaults = [
        'public'        => FALSE,
        'data_sanitize' => NULL,
        'send_json'     => TRUE,
        'json_validate' => NULL
    ];
    private $id;
    private $blogid;
    private $args;
    private $callback;
    private $nonce;
    private $sanitizer;

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
        $this->sanitize();
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
            $this->sanitize();
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

    public function sanitize() {
        $this->args = $this->getSanitizer()->sanitize( $this->args );
    }

    public function setPublicAccess( $is = TRUE ) {
        return $this->setVar( 'public',  ! empty( $is ) );
    }

    public function sanitizeUsing( $callable ) {
        if ( ! is_callable( $callable ) ) {
            throw new \InvalidArgumentException;
        }
        return $this->setVar( 'data_sanitize', $callable );
    }

    public function setJsonResponse( $return = TRUE, $validate = NULL ) {
        $json = is_callable( $validate ) ? $validate :  ! empty( $return );
        return $this->setVar( 'json_validate', $json );
    }

    public function setSanitizer( ArgsSanitizerInterface $sanitizer = NULL ) {
        $this->sanitizer = $sanitizer;
    }

    public function getSanitizer() {
        if ( is_null( $this->sanitizer ) ) {
            $this->sanitizer = new ArgsSanitizer;
        }
        return $this->sanitizer;
    }

}