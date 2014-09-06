<?php namespace GM\Tela\Tests;

class TelaTest extends TestCase {

    function testInitDoNothingIfDidAction() {
        \WP_Mock::wpFunction( 'did_action', [
            'times'  => 1,
            'args'   => [ 'wp_loaded' ],
            'return' => TRUE,
        ] );
        $tela = $this->getTela( 'test' );
        $tela->init();
        assertFalse( $tela->inited() );
    }

    function testInit() {
        \WP_Mock::wpFunction( 'did_action', [
            'times'  => 1,
            'args'   => [ 'wp_loaded' ],
            'return' => FALSE,
        ] );
        \WP_Mock::wpFunction( 'doing_action', [
            'times'  => 1,
            'args'   => [ 'wp_loaded' ],
            'return' => FALSE,
        ] );
        $tela = $this->getTela( 'test' );
        \WP_Mock::expectActionAdded( 'wp_loaded', [ $tela, 'whenLoaded' ], 0 );
        $tela->init();
        assertEquals( 1, $tela->inited() );
    }

    function testWhenLoadedDoNothingIfInited() {
        $tela = $this->getMockedTela();
        $tela->shouldReceive( 'inited' )->once()->withNoArgs()->andReturn( TRUE );
        assertNull( $tela->whenLoaded() );
    }

    function testWhenLoadedDoNothingIfWrongFilter() {
        \WP_Mock::wpFunction( 'current_filter', [
            'times'  => 1,
            'args'   => [ ],
            'return' => 'init',
        ] );
        $tela = $this->getMockedTela();
        $tela->shouldReceive( 'inited' )->once()->withNoArgs()->andReturn( 1 );
        assertNull( $tela->whenLoaded() );
    }

    function testWhenLoaded() {
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'inited' )->atLeast( 1 )->withNoArgs()->andReturn( 1 );
        $tela->shouldReceive( 'isAjax' )->once()->withNoArgs()->andReturn( TRUE );
        \WP_Mock::wpFunction( 'current_filter', [
            'times'  => 1,
            'args'   => [ ],
            'return' => 'wp_loaded',
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'times'  => 1,
            'args'   => [ 'wp_loaded', [ $tela, 'whenLoaded' ], 0 ],
            'return' => NULL,
        ] );
        \WP_Mock::wpFunction( 'wp_create_nonce', [
            'times'  => 1,
            'args'   => [ 'tela_test' ],
            'return' => 'this_is_a_mocked_salt',
        ] );
        \WP_Mock::expectAction( 'tela_register_test', $tela );
        assertNull( $tela->whenLoaded() );
        assertEquals( 'this_is_a_mocked_salt', $tela->getNonceSalt() );
    }

    function testWhenLoadedInitAjax() {
        $proxy = \Mockery::mock( 'ProxyInterface' );
        $tela = $this->getMockedTela( 'test' );
        \WP_Mock::wpFunction( 'current_filter', [
            'times'  => 1,
            'args'   => [ ],
            'return' => 'wp_loaded',
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'times'  => 1,
            'args'   => [ 'wp_loaded', [ $tela, 'whenLoaded' ], 0 ],
            'return' => NULL,
        ] );
        \WP_Mock::wpFunction( 'wp_create_nonce', [
            'times'  => 1,
            'args'   => [ 'tela_test' ],
            'return' => 'this_is_a_mocked_salt',
        ] );
        \WP_Mock::wpFunction( 'has_action', [
            'times'  => 1,
            'args'   => [ \GM\Tela\Proxy::HOOK, [ $proxy, 'proxy' ] ],
            'return' => FALSE,
        ] );
        $tela->shouldReceive( 'inited' )->atLeast( 1 )->withNoArgs()->andReturn( 1, 2 );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'isTelaAjax' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'getFactory->registry' )
            ->once()
            ->with( 'proxy', '', [ [ ] ] )
            ->andReturn( $proxy );
        \WP_Mock::expectAction( 'tela_register_test', $tela );
        \WP_Mock::expectActionAdded( \GM\Tela\Proxy::HOOK, [ $proxy, 'proxy' ] );
        \WP_Mock::expectActionAdded( \GM\Tela\Proxy::HOOKNOPRIV, [ $proxy, 'proxy' ] );
        assertNull( $tela->whenLoaded() );
    }

    function testWhenLoadedInitFront() {
        $tela = $this->getMockedTela( 'test' );
        $js_manager = \Mockery::mock( 'JsManagerInterface' );
        \WP_Mock::wpFunction( 'current_filter', [
            'times'  => 1,
            'args'   => [ ],
            'return' => 'wp_loaded',
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'times'  => 1,
            'args'   => [ 'wp_loaded', [ $tela, 'whenLoaded' ], 0 ],
            'return' => NULL,
        ] );
        \WP_Mock::wpFunction( 'wp_create_nonce', [
            'times'  => 1,
            'args'   => [ 'tela_test' ],
            'return' => 'this_is_a_mocked_salt',
        ] );
        \WP_Mock::expectAction( 'tela_register_test', $tela );
        $tela->shouldReceive( 'inited' )->atLeast( 1 )->withNoArgs()->andReturn( 1, 2 );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( FALSE );
        $tela->shouldReceive( 'hasActions' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'getFactory->registry' )
            ->once()
            ->with( 'jsmanager' )
            ->andReturn( $js_manager );
        $js_manager->shouldReceive( 'addNonces' )->once()->with( [ ] )->andReturnNull();
        $js_manager->shouldReceive( 'enabled' )->once()->withNoArgs()->andReturn( FALSE );
        $js_manager->shouldReceive( 'enable' )->once()->withNoArgs()->andReturnNull();
        assertNull( $tela->whenLoaded() );
    }

    function testRegisterOnFront() {
        \WP_Mock::wpFunction( 'is_admin', [
            'times'  => 1,
            'args'   => [ ],
            'return' => FALSE,
        ] );
        \WP_Mock::wpFunction( 'wp_create_nonce', [
            'args'   => [ 'test::foo' ],
            'return' => 'nonce_for_foo',
        ] );
        $args = [ 'side' => \GM\Tela::FRONTEND ];
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'sanitizeArgs' )->once()->with( \Mockery::type( 'array' ) )->andReturn( $args );
        $tela->shouldReceive( 'isAjax' )->once()->withNoArgs()->andReturn( FALSE );
        $expected = base64_encode( 'nonce_for_foo' );
        assertNull( $tela->register( 'foo', NULL, $args ) );
        assertEquals( $expected, $tela->getActionNonce( 'test::foo' ) );
    }

    function testRegisterOnAjax() {
        \WP_Mock::wpFunction( 'wp_create_nonce', [
            'args'   => [ 'test::foo' ],
            'return' => 'nonce_for_foo',
        ] );
        $args = [ 'side' => \GM\Tela::FRONTEND ];
        $nonce = base64_encode( 'nonce_for_foo' );
        $action_obj = \Mockery::mock( 'GM\Tela\ActionInterface' );
        $action_obj->shouldReceive( 'setBlogId' )->once()->with( 1 )->andReturnNull();
        $action_obj->shouldReceive( 'setNonce' )->once()->with( $nonce )->andReturnNull();
        $action_obj->shouldReceive( 'setCallback' )->once()->with( NULL )->andReturnNull();
        $action_obj->shouldReceive( 'setArgs' )->once()->with( $args )->andReturnNull();
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'sanitizeArgs' )->once()->with( \Mockery::type( 'array' ) )->andReturn( $args );
        $tela->shouldReceive( 'checkRegisterVars' )->once()->with( 'test::foo', NULL )->andReturnNull();
        $tela->shouldReceive( 'isAjax' )->once()->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'getFactory->get' )->with( 'action', '', [ 'test::foo' ] )
            ->andReturn( $action_obj );
        $registered = $tela->register( 'foo', NULL, $args );
        assertEquals( $action_obj, $registered );
        assertEquals( $action_obj, $tela->getAction( 'test::foo' ) );
        assertEquals( $nonce, $tela->getActionNonce( 'test::foo' ) );
    }

    function testPerformAjaxNullIfNonAjax() {
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( FALSE );
        assertNull( $tela->performAction() );
    }

    function testPerformAjaxBadExitIfAjaxAndNotCheck() {
        $args = [ 'action' => 'foo' ];
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'isTelaAjax' )->atLeast( 1 )->with( $args )->andReturn( TRUE );
        $tela->shouldReceive( 'getAction' )->atLeast( 1 )->with( 'test' )->andReturnNull();
        \WP_Mock::expectAction( 'tela_not_pass_check', NULL, $args, $tela );
        assertFalse( $tela->performAction( $args ) );
    }

    function testPerformAjax() {
        \WP_Mock::wpFunction( 'wp_send_json', [
            'times'  => 1,
            'args'   => [ [ 'foo' => 'bar' ] ],
            'return' => NULL
        ] );
        $args = [ 'action' => 'foo', 'data' => [ 'foo' => 'bar' ] ];
        $closure = function( $args ) {
            return $args;
        };
        $action_obj = \Mockery::mock( 'GM\Tela\ActionInterface' );
        $action_obj->shouldReceive( 'getId' )->atLeast( 1 )->withNoArgs()->andReturn( 'foo' );
        $action_obj->shouldReceive( 'getVar' )->atLeast( 1 )->with( 'data_sanitize' )->andReturnNull();
        $action_obj->shouldReceive( 'getVar' )->atLeast( 1 )->with( 'json' )->andReturn( TRUE );
        $action_obj->shouldReceive( 'getCallback' )->atLeast( 1 )->withNoArgs()->andReturn( $closure );
        $checker = \Mockery::mock( 'GM\Tela\AjaxCheckerInterface' );
        $checker->shouldReceive( 'checkRequest' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $checker->shouldReceive( 'checkNonce' )->atLeast( 1 )->with( '' )->andReturn( TRUE );
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'getAction' )->atLeast( 1 )->with( 'foo' )->andReturn( $action_obj );
        $tela->shouldReceive( 'getActionNonce' )->atLeast( 1 )->with( 'foo' )->andReturn( 'nonce' );
        $tela->shouldReceive( 'getFactory->get' )
            ->with( 'checker', NULL, [ $args, $action_obj ] )
            ->andReturn( $checker );
        assertNull( $tela->performAction( $args ) );
    }

    function testCheckRegisterVarsErrorIfNotInit() {
        $tela = $this->getTela( 'test' );
        assertInstanceOf( '\WP_Error', $tela->checkRegisterVars( 'foo', 'print' ) );
    }

    function testCheckRegisterVarsErrorIfBadAction() {
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'inited' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        assertInstanceOf( '\WP_Error', $tela->checkRegisterVars( TRUE, 'print' ) );
    }

    function testCheckRegisterVarsErrorIfBadCallable() {
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'inited' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        assertInstanceOf( '\WP_Error', $tela->checkRegisterVars( 'foo', 'bar' ) );
    }

    function testSanitizeArgsErrorIfBadSanitizer() {
        $error = new \WP_Error;
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'getFactory->get' )
            ->with( 'sanitizer', NULL )->andReturn( $error );
        assertEquals( $error, $tela->sanitizeArgs( [ ] ) );
    }

    function testSanitizeArgsUseSanitizer() {
        $args = [ 'foo', 'bar' ];
        $tela = $this->getMockedTela( 'test' );
        $sanitizer = \Mockery::mock( 'GM\Tela\ArgsSanitizerInterface' );
        $sanitizer->shouldReceive( 'sanitize' )->once()->with( $args )->andReturn( 'Sanitized!' );
        $tela->shouldReceive( 'getFactory->get' )
            ->with( 'sanitizer', NULL )->andReturn( $sanitizer );
        assertEquals( 'Sanitized!', $tela->sanitizeArgs( $args ) );
    }

}