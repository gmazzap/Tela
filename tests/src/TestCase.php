<?php namespace GM\Tela\Tests;

class TestCase extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        \WP_Mock::setUp();
        \WP_Mock::wpFunction( 'get_current_blog_id', [
            'return' => 1,
        ] );
        \WP_Mock::wpFunction( 'wp_die', [
            'return' => NULL,
        ] );
    }

    public function tearDown() {
        \WP_Mock::tearDown();
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