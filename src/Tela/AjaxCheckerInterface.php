<?php namespace GM\Tela;

interface AjaxCheckerInterface {

    public function getRequest();

    public function getAction();

    public function setRequest( Array $request );

    public function setAction( ActionInterface $action );

    public function checkRequest();

    public function checkNonce( $nonce, $salt );
}