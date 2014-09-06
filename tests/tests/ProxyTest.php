<?php namespace GM\Tela\Tests;

use GM\Tela\Proxy as Proxy;

class ProxyTest extends TestCase {

    private $instances = [ ];

    private function getProxy( $args = [ ] ) {
        $this->instances[ 'test' ] = \Mockery::mock( 'GM\Tela' );
        $request = \Mockery::mock( 'GM\Tela\Request' );
        $request->shouldReceive( 'get' )->withNoArgs()->andReturn( $args );
        return new Proxy( $this->instances, $request );
    }

    function testProxyDoNothingIfBadHook() {
        \WP_Mock::wpFunction( 'current_filter', [ 'return' => 'init' ] );
        $proxy = $this->getProxy();
        assertNull( $proxy->proxy() );
    }

    function testProxy() {
        $vars = [ 'action' => 'test::test' ];
        \WP_Mock::wpFunction( 'current_filter', [ 'return' => Proxy::HOOK ] );
        \WP_Mock::wpFunction( 'remove_action', [ 'return' => NULL ] );
        $proxy = $this->getProxy( $vars );
        $this->instances[ 'test' ]
            ->shouldReceive( 'performAction' )
            ->with( $vars )
            ->andReturn( 'Done!' );
        assertSame( 'Done!', $proxy->proxy() );
    }

    function testProxyRunOnce() {
        $vars = [ 'action' => 'test::test' ];
        \WP_Mock::wpFunction( 'current_filter', [ 'return' => Proxy::HOOK ] );
        \WP_Mock::wpFunction( 'remove_action', [ 'return' => NULL ] );
        $proxy = $this->getProxy( $vars );
        $this->instances[ 'test' ]
            ->shouldReceive( 'performAction' )
            ->with( $vars )
            ->andReturn( 'Done!' );
        assertSame( 'Done!', $proxy->proxy() );
        assertNull( $proxy->proxy() );
    }

}