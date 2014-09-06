<?php namespace GM\Tela\Tests;

class TestCase extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        \WP_Mock::setUp();
    }

    public function tearDown() {
        \WP_Mock::tearDown();
        \GM\Tela::flush();
    }

    protected function getMockedFactory() {
        $factory = \Mockery::mock( 'GM\Tela\Factory' )->makePartial();
        return $factory;
    }

    protected function getTela( $id, $factory = NULL, $shared = NULL ) {
        if ( ! $factory instanceof GM\Tela\Factory ) {
            $factory = $this->getMockedFactory();
        }
        return new \GM\Tela( $id, $factory, $shared );
    }

    protected function getMockedTela( $id = 'test' ) {
        $tela = \Mockery::mock( 'GM\Tela' )->makePartial();
        $tela->shouldReceive( 'getId' )->withNoArgs()->andReturn( $id );
        return $tela;
    }

}