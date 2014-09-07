<?php namespace GM\Tela\Tests;

class AjaxCheckerTest extends TestCase {

    private $action;

    private function getChecker( $args = [ ] ) {
        \WP_Mock::wpPassthruFunction( 'wp_parse_args' );
        $this->action = \Mockery::mock( 'GM\Tela\ActionInterface' );
        $def = array_fill_keys( [ 'is_tela', 'action', 'nonce', 'blogid', 'from_admin' ], NULL );
        return new \GM\Tela\AjaxChecker( array_merge( $def, $args ), $this->action );
    }

    private function getValidArgs() {
        return [ 'is_tela' => TRUE, 'action' => 'test', 'nonce' => 'nonce', 'blogid' => 1 ];
    }

    private function validAction( $blogid = 1, $side = NULL ) {
        if ( is_null( $side ) ) {
            $side = \GM\Tela::FRONTEND;
        }
        $this->action->shouldReceive( 'isPublic' )->atLeast( 1 )->andReturn( FALSE );
        $this->action->shouldReceive( 'getBlogId' )->atLeast( 1 )->andReturn( $blogid );
        $this->action->shouldReceive( 'getVar' )->atLeast( 1 )->with( 'side' )->andReturn( $side );
    }

    function testCheckRequestFailsIfBadUrlVars() {
        $checker = $this->getChecker( [ 'is_tela' => TRUE ] );
        assertFalse( $checker->checkRequest() );
    }

    function testCheckRequestFailsIfActionNotPublicAndUserNotLogged() {
        \WP_Mock::wpFunction( 'is_user_logged_in', [ 'return' => FALSE ] );
        $checker = $this->getChecker( $this->getValidArgs() );
        $this->validAction();
        assertFalse( $checker->checkRequest() );
    }

    function testCheckRequestFailsIfBadBlogId() {
        \WP_Mock::wpFunction( 'is_user_logged_in', [ 'return' => TRUE ] );
        $checker = $this->getChecker( $this->getValidArgs() );
        // get_current_blog_id() returns 1 by design. See `tests/helpers/helpers.php`
        $this->validAction( 2 );
        assertFalse( $checker->checkRequest() );
    }

    function testCheckRequestFailsIfBadSide() {
        \WP_Mock::wpFunction( 'is_user_logged_in', [ 'return' => TRUE ] );
        $checker = $this->getChecker( $this->getValidArgs() );
        $this->validAction( \GM\Tela::BACKEND );
        assertFalse( $checker->checkRequest() );
    }

    function testCheckRequest() {
        \WP_Mock::wpFunction( 'is_user_logged_in', [ 'return' => TRUE ] );
        $checker = $this->getChecker( $this->getValidArgs() );
        $this->validAction();
        assertTrue( $checker->checkRequest() );
    }

    function testCheckNonce() {
        $checker = $this->getChecker( $this->getValidArgs() );
        \WP_Mock::wpFunction( 'wp_verify_nonce', [
            'return' => function( $a, $b ) {
            return $a === $b;
        } ] );
        $nonce = base64_encode( 'salt_test' );
        $this->action->shouldReceive( 'getNonce' )->withNoArgs()->andReturn( $nonce );
        assertTrue( $checker->checkNonce( 'salt_' ) );
    }

    function testCheckNonceFails() {
        $checker = $this->getChecker( $this->getValidArgs() );
        \WP_Mock::wpFunction( 'wp_verify_nonce', [
            'return' => function( $a, $b ) {
            return $a === $b;
        } ] );
        $nonce = base64_encode( 'salt_test' );
        $this->action->shouldReceive( 'getNonce' )->withNoArgs()->andReturn( $nonce );
        assertFalse( $checker->checkNonce( '_salt_' ) );
    }

}