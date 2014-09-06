<?php namespace GM\Tela;

class AjaxChecker implements AjaxCheckerInterface {

    private $request_vars;
    private $action;

    function __construct( Array $request_vars, ActionInterface $action ) {
        $this->setRequestVars( $request_vars );
        $this->setAction( $action );
    }

    public function setAction( ActionInterface $action ) {
        $this->action = $action;
    }

    public function getAction() {
        return $this->action;
    }

    public function getRequestVars() {
        return $this->request_vars;
    }

    public function setRequestVars( Array $request_vars ) {
        $keys = [ 'is_tela', 'action', 'nonce', 'blogid', 'from_admin' ];
        $this->request_vars = wp_parse_args( $request_vars, array_fill_keys( $keys, NULL ) );
    }

    public function checkRequest() {
        return $this->checkUrlVars() && $this->checkAction() && $this->checkSide();
    }

    public function checkNonce( $salt ) {
        $check = preg_replace( "#^{$salt}#", '', base64_decode( $this->getAction()->getNonce() ), 1 );
        $vars = $this->getRequestVars();
        return (bool) wp_verify_nonce( $check, $vars[ 'action' ] );
    }

    private function checkUrlVars() {
        $request_vars = $this->getRequestVars();
        return $request_vars [ 'is_tela' ]
            && ! empty( $request_vars [ 'action' ] )
            && ! empty( $request_vars [ 'nonce' ] )
            && ! empty( $request_vars[ 'blogid' ] );
    }

    private function checkAction() {
        $action = $this->getAction();
        $request_vars = $this->getRequestVars();
        return ( $action->isPublic() || is_user_logged_in() )
            && $action->getBlogId() === $request_vars[ 'blogid' ];
    }

    private function checkSide() {
        $request_vars = $this->getRequestVars();
        $bad_side = $request_vars[ 'from_admin' ] ? \GM\Tela::FRONTEND : \GM\Tela::BACKEND;
        $action_side = $this->getAction()->getVar( 'side' );
        return (int) $action_side > 0 && $action_side !== $bad_side;
    }

}