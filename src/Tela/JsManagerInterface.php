<?php namespace GM\Tela;

interface JsManagerInterface {

    public function enable();
	
	public function enabled();

    public function addNonces( Array $nonces );

    public function getNonces();

    public function addScript();

    public function addNoncesData();

    public function getHook();

    public function getHandle();
}