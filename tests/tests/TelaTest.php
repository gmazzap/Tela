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

    function testWhenLoadedDoNothingIfNotAllowed() {
        \WP_Mock::wpFunction( 'current_filter', [
            'times'  => 1,
            'args'   => [ ],
            'return' => 'wp_loaded',
        ] );
        $tela = $this->getMockedTela();
        $tela->shouldReceive( 'inited' )->once()->withNoArgs()->andReturn( 1 );
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( FALSE );
        assertNull( $tela->whenLoaded() );
    }

    function testWhenLoaded() {
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'inited' )->atLeast( 1 )->withNoArgs()->andReturn( 1 );
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( TRUE );
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
        $proxy = \Mockery::mock( 'GM\Tela\ProxyInterface' );
        $request = \Mockery::mock( 'GM\Tela\Request' );
        $factory = \Mockery::mock( 'GM\Tela\Factory' );
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
        $factory->shouldReceive( 'registry' )->atLeast( 1 )->with( 'request' )->andReturn( $request );
        $factory->shouldReceive( 'registry' )
            ->atLeast( 1 )
            ->with( 'proxy', '', [ [ ], $request ] )
            ->andReturn( $proxy );
        $tela->shouldReceive( 'inited' )->atLeast( 1 )->withNoArgs()->andReturn( 1, 2 );
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'isTelaAjax' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'getFactory' )->atLeast( 1 )->withNoArgs()->andReturn( $factory );
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
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( TRUE );
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

    function testRegisterDoNothingIfNotAllowed() {
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( FALSE );
        assertNull( $tela->register( 'foo', NULL, [ ] ) );
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
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( TRUE );
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
        $validator = \Mockery::mock( 'GM\Tela\ActionArgsValidatorInterface' );
        $action_obj = \Mockery::mock( 'GM\Tela\ActionInterface' );
        $action_obj->shouldReceive( 'setBlogId' )->once()->with( 1 )->andReturnNull();
        $action_obj->shouldReceive( 'setNonce' )->once()->with( $nonce )->andReturnNull();
        $action_obj->shouldReceive( 'setCallback' )->once()->with( NULL )->andReturnNull();
        $action_obj->shouldReceive( 'setValidator' )->once()->with( $validator )->andReturnNull();
        $action_obj->shouldReceive( 'setArgs' )->once()->with( $args )->andReturnNull();
        $factory = \Mockery::mock( 'GM\Tela\Factory' );
        $factory->shouldReceive( 'get' )->with( 'action', '', [ 'test::foo' ] )->andReturn( $action_obj );
        $factory->shouldReceive( 'get' )->with( 'validator' )->andReturn( $validator );
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'sanitizeArgs' )->once()->with( \Mockery::type( 'array' ) )->andReturn( $args );
        $tela->shouldReceive( 'checkRegisterVars' )->once()->with( 'test::foo', NULL )->andReturnNull();
        $tela->shouldReceive( 'isAjax' )->once()->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'getFactory' )->withNoArgs()->andReturn( $factory );
        $registered = $tela->register( 'foo', NULL, $args );
        assertEquals( $action_obj, $registered );
        assertEquals( $action_obj, $tela->getAction( 'test::foo' ) );
        assertEquals( $nonce, $tela->getActionNonce( 'test::foo' ) );
    }

    function testPerformActionAjaxNullIfNonAjax() {
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( FALSE );
        assertNull( $tela->performAction() );
    }

    function testPerformActionBadExitIfNotAllowed() {
        $args = [ 'action' => 'foo' ];
        $tela = $this->getMockedTela();
        $tela->shouldReceive( 'isAjax' )->twice()->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( FALSE ); // <-- FALSE
        $tela->shouldReceive( 'isTelaAjax' )->once()->with( $args )->andReturn( TRUE );
        $tela->shouldReceive( 'getAction' )->once()->with( 'foo' )->andReturn( 'I am an Action' );
        \WP_Mock::expectAction( 'tela_not_pass_check', 'I am an Action', $args, $tela );
        assertFalse( $tela->performAction( $args ) );
    }

    function testPerformActionAjaxBadExitIfAjaxAndNotCheck() {
        $args = [ 'action' => 'foo' ];
        $tela = $this->getMockedTela( 'test' );
        $tela->shouldReceive( 'isAjax' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( TRUE );
        $tela->shouldReceive( 'isTelaAjax' )->atLeast( 1 )->with( $args )->andReturn( TRUE );
        $tela->shouldReceive( 'getAction' )->atLeast( 1 )->with( 'test' )->andReturnNull();
        \WP_Mock::expectAction( 'tela_not_pass_check', NULL, $args, $tela );
        assertFalse( $tela->performAction( $args ) );
    }

    function testPerformActionAjax() {
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
        $tela->shouldReceive( 'allowed' )->once()->withNoArgs()->andReturn( TRUE );
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
            ->with( 'validator', 'foo' )->andReturn( $error );
        assertEquals( $error, $tela->sanitizeArgs( [ ], 'foo' ) );
    }

    function testSanitizeArgsUseValidator() {
        $args = [ 'foo', 'bar' ];
        $tela = $this->getMockedTela( 'test' );
        $validator = \Mockery::mock( 'GM\Tela\ActionArgsValidatorInterface' );
        $validator->shouldReceive( 'validate' )->once()->with( $args )->andReturn( 'Valid!' );
        $tela->shouldReceive( 'getFactory->get' )
            ->with( 'validator', 'ActionArgsValidator' )->andReturn( $validator );
        assertEquals( 'Valid!', $tela->sanitizeArgs( $args, 'ActionArgsValidator' ) );
    }

    function testErrorReturnsWpError() {
        $tela = $this->getTela( 'test' );
        assertInstanceOf( 'WP_Error', $tela->error() );
    }

}