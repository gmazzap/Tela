<?php namespace GM\Tela;

interface JsManagerInterface {

    public function enable();

    public function enabled();

    public function addNonces( Array $nonces );

    public function addEntryPoint( $tela_id );

    public function getNonces();

    public function getEntryPoints();

    public function addScript();

    public function addInstancesData();

    public function getHook();

    public function getHandle();
}