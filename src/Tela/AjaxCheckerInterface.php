<?php namespace GM\Tela;

interface AjaxCheckerInterface {

    /**
     * Getter for the request vars array.
     *
     * @return array
     */
    public function getRequestVars();

    /**
     * Getter for the action object.
     *
     * @return \GM\Tela\ActionInterface
     */
    public function getAction();

    /**
     * Setter for the request vars array.
     *
     * @param array $request_vars
     */
    public function setRequestVars( Array $request_vars );

    /**
     * Setter for the action object.
     *
     * @param \GM\Tela\ActionInterface $action
     */
    public function setAction( ActionInterface $action );

    /**
     * Check sanity of current request and action
     *
     */
    public function checkRequest();

    /**
     * Check action nonce against a salt,
     *
     * @param string $salt
     * @return boolean
     */
    public function checkNonce( $salt );
}