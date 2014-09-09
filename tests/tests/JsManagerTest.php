<?php namespace GM\Tela\Tests;

use GM\Tela\JsManager as Manager;

class JsManagerTest extends TestCase {

    function testEnable() {
        $manager = \Mockery::mock( 'GM\Tela\JsManager' )->makePartial();
        $manager->shouldReceive( 'getHook' )->andReturn( 'wp_enqueue_scripts' );
        \WP_Mock::expectActionAdded( 'wp_enqueue_scripts', [ $manager, 'addScript' ] );
        \WP_Mock::expectActionAdded( 'wp_enqueue_scripts', [ $manager, 'addInstancesData' ], PHP_INT_MAX );
        $manager->enable();
        assertTrue( $manager->enabled() );
    }

    function testAddNonces() {
        $manager = new Manager;
        $manager->addNonces( [ 'foo' => 'bar' ] );
        $manager->addNonces( [ 'bar' => 'baz' ] );
        $manager->addNonces( [ 'baz' => 'foo' ] );
        assertSame( ['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo' ], $manager->getNonces() );
    }

    function testAddScript() {
        $data = [ 'url' => 'http://example.com/admin-ajax.php?telaajax=1', 'is_admin' => 0 ];
        $manager = \Mockery::mock( 'GM\Tela\JsManager' )->makePartial();
        $manager->shouldReceive( 'getHandle' )->andReturn( 'handle' );
        $manager->shouldReceive( 'getScriptData' )->withNoArgs()->andReturn( $data );
        $manager->shouldReceive( 'getScriptUrl' )
            ->with( \Mockery::type( 'string' ), \Mockery::type( 'string' ) )
            ->andReturn( 'http://example.com/script.js' );
        $manager->shouldReceive( 'getScriptVer' )
            ->with( \Mockery::type( 'string' ), \Mockery::type( 'string' ) )
            ->andReturn( '1' );
        \WP_Mock::wpFunction( 'wp_enqueue_script', [
            'times'  => 1,
            'return' => NULL,
            'args'   => [ 'handle', 'http://example.com/script.js', [ 'jquery' ], '1', TRUE ]
        ] );
        \WP_Mock::wpFunction( 'wp_localize_script', [
            'times'  => 1,
            'return' => NULL,
            'args'   => [ 'handle', 'TelaAjaxData', $data ]
        ] );
        assertNull( $manager->addScript() );
    }

    function testAddScriptRunOnce() {
        $manager = \Mockery::mock( 'GM\Tela\JsManager' )->makePartial();
        $manager->shouldReceive( 'scriptAdded' )->andReturn( TRUE );
        assertFalse( $manager->addScript() );
    }

    function testAddInstancesData() {
        $data = [
            'nonces'      => [ 'foo' => 'bar', 'bar' => 'baz' ],
            'entrypoints' => [ 'test' => 'http://www.example.com/foo' ]
        ];
        $manager = \Mockery::mock( 'GM\Tela\JsManager' )->makePartial();
        $manager->shouldReceive( 'getNonces' )->andReturn( $data[ 'nonces' ] );
        $manager->shouldReceive( 'getEntryPoints' )->andReturn( $data[ 'entrypoints' ] );
        $manager->shouldReceive( 'getHandle' )->andReturn( 'handle' );
        $manager->shouldReceive( 'scriptAdded' )->andReturn( TRUE );
        \WP_Mock::wpFunction( 'wp_localize_script', [
            'times'  => 1,
            'return' => NULL,
            'args'   => [ 'handle', 'TelaAjaxWideData', $data ]
        ] );
        assertNull( $manager->addInstancesData() );
    }

    function testAddInstancesDataRunOnce() {
        $manager = \Mockery::mock( 'GM\Tela\JsManager' )->makePartial();
        $manager->shouldReceive( 'noncesAdded' )->andReturn( TRUE );
        assertFalse( $manager->addInstancesData() );
    }

    function testAddInstancesDataNotRunIfNotScript() {
        $manager = \Mockery::mock( 'GM\Tela\JsManager' )->makePartial();
        $manager->shouldReceive( 'noncesAdded' )->andReturn( FALSE );
        $manager->shouldReceive( 'scriptAdded' )->andReturn( FALSE );
        assertFalse( $manager->addInstancesData() );
    }

    function testGetHandleNeverNull() {
        $manager = new Manager;
        assertTrue( strpos( $manager->getHandle(), 'tela_ajax_js' ) === 0 );
    }

    function testGetScriptVerNullIfNotDebug() {
        $base = dirname( __FILE__ ) . '/';
        $relative = basename( __FILE__ );
        $manager = new Manager;
        assertNull( $manager->getScriptVer( $base, $relative ) );
    }

    /**
     * @runInSeparateProcess
     */
    function testGetScriptVerUseFiletimeIfDebug() {
        define( 'WP_DEBUG', TRUE );
        \WP_Mock::wpPassthruFunction( 'plugin_dir_path' );
        $base = dirname( __FILE__ ) . '/';
        $relative = basename( __FILE__ );
        $time = @filemtime( $base . $relative );
        $manager = new Manager;
        assertSame( $time, $manager->getScriptVer( $base, $relative ) );
    }

    function testGetScriptData() {
        $url_args = [ 'telaajax' => '1', 'action' => 'telaajax_proxy', 'bid' => 1 ];
        \WP_Mock::wpFunction( 'admin_url', [
            'return' => 'http://example.com/admin-ajax.php',
            'args'   => [ 'admin-ajax.php' ]
        ] );
        \WP_Mock::wpFunction( 'add_query_arg', [
            'return' => 'http://example.com/admin-ajax.php?telaajax=1&action=telaajax_proxy&bid=1',
            'args'   => [ $url_args, 'http://example.com/admin-ajax.php' ]
        ] );
        \WP_Mock::wpFunction( 'is_admin', [
            'return' => FALSE,
        ] );
        $expected = [
            'url'      => 'http://example.com/admin-ajax.php?telaajax=1&action=telaajax_proxy&bid=1',
            'url_args' => 'telaajax=1&action=telaajax_proxy&bid=1',
            'is_admin' => '0'
        ];
        $manager = new Manager;
        assertSame( $expected, $manager->getScriptData() );
    }

}