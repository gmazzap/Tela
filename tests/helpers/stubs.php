<?php namespace GM\Tela\Tests;

interface StubInterface {

    public function dump();
}

class Stub implements StubInterface {

    public function dump() {
        return get_object_vars( $this );
    }

}

class StubAlt extends Stub {

}

class EvilStub implements StubInterface {

    private function __construct() {
        $this->status = 'EVIL';
    }

    public function dump() {
        return get_object_vars( $this );
    }

}