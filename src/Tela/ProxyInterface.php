<?php namespace GM\Tela;

interface ProxyInterface {

    const HOOK = 'wp_ajax_telaajax_proxy';
    const HOOKNOPRIV = 'wp_ajax_nopriv_telaajax_proxy';

    public function proxy();
}