<?php namespace GM\Tela;

interface AjaxCheckerInterface {

    /**
     * Getter for the request array.
     *
     * @return array
     */
    public function getRequest();

    /**
     * Getter for the action object.
     *
     * @return \GM\Tela\ActionInterface
     */
    public function getAction();

    /**
     * Setter for the request array.
     *
     * @param array $request
     */
    public function setRequest( Array $request );

    /**
     * Setter for the action object.
     *
     * @param \GM\Tela\ActionInterface $action
     */
    public function setAction( ActionInterface $action );

    /**
     * Check sanity of current request and action
     *
     * @param array $request
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