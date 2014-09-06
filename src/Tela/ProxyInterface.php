<?php namespace GM\Tela;

interface ProxyInterface {

    const HOOK = 'wp_ajax_telaajax_proxy';
    const HOOKNOPRIV = 'wp_ajax_nopriv_telaajax_proxy';

    /**
     * Proxy ajax action to right tela instance looking at on action id
     *
     * @return void
     */
    public function proxy();

    /**
     * Getter for the stored request object
     *
     * @return GM\Tela\Request
     */
    public function getRequest();

    /**
     * Setter for the request object property
     *
     */
    public function setRequest( Request $request );
}