<?php namespace GM\Tela\Tests;

interface StubInterface {

    public function dump();
}

class Stub implements TestStubInterface {

    public function dump() {
        return get_object_vars( $this );
    }

}