<?php namespace GM\Tela;

interface ActionInterface {

    public function getId();

    public function getBlogId();

    public function getCallback();

    public function getVar( $var );

    public function getNonceSalt();

    public function getNonce();

    public function setId( $id );

    public function setBlogId( $id );

    public function setCallback( $callback );

    public function setArgs( Array $args = [ ] );

    public function setVar( $var );

    public function setNonceSalt( $salt );
}