<?php namespace GM\Tela;

class AjaxChecker implements AjaxCheckerInterface {

    private $request;
    private $action;

    function __construct( Array $request, ActionInterface $action ) {
        $this->setRequest( $request );
        $this->setAction( $action );
    }

    public function setAction( ActionInterface $action ) {
        $this->action = $action;
    }

    public function getAction() {
        return $this->action;
    }

    public function getRequest() {
        return $this->request;
    }

    public function setRequest( Array $request ) {
        $keys = [ 'is_tela', 'action', 'nonce', 'blogid', 'from_admin' ];
        $this->request = wp_parse_args( $request, array_fill_keys( $keys, NULL ) );
    }

    public function checkRequest() {
        return $this->checkUrlVars() && $this->checkAction() && $this->checkSide();
    }

    public function checkNonce( $salt ) {
        $check = preg_replace( "#^{$salt}#", '', base64_decode( $this->getAction()->getNonce() ), 1 );
        return (bool) wp_verify_nonce( $check, $this->request[ 'action' ] );
    }

    private function checkUrlVars() {
        $request = $this->getRequest();
        return $request [ 'is_tela' ]
            && ! empty( $request [ 'action' ] )
            && ! empty( $request [ 'nonce' ] )
            && ! empty( $request[ 'blogid' ] );
    }

    private function checkAction() {
        $action = $this->getAction();
        $request = $this->getRequest();
        return ( $action->isPublic() || is_user_logged_in() )
            && $action->getBlogId() === $request[ 'blogid' ];
    }

    private function checkSide() {
        $request = $this->getRequest();
        $bad_side = $request[ 'from_admin' ] ? \GM\Tela::FRONTEND : \GM\Tela::BACKEND;
        $action_side = $this->getAction()->getVar( 'side' );
        return (int) $action_side > 0 && $action_side !== $bad_side;
    }

}