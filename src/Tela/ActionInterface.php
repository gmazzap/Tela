<?php namespace GM\Tela;

interface ActionInterface {

    public function getId();

    public function getBlogId();

    public function getCallback();

    public function getVar( $var );

    public function getNonce();

    public function setNonce( $nonce );

    public function setId( $id );

    public function setBlogId( $id );

    public function setCallback( $callback );

    public function setArgs( Array $args = [ ] );

    public function setVar( $var );

    public function isPublic();

    public function sanitize();
}