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
        $this->request = $request;
    }

    public function checkRequest() {
        return $this->checkUrlVars() && $this->checkAction() && $this->checkSide();
    }

    public function checkNonce( $nonce, $salt ) {
        $check = preg_replace( "#^{$salt}#", '', base64_decode( $nonce ), 1 );
        return (bool) wp_verify_nonce( $check, $this->request[ 'action' ] );
    }

    /**
     * Check if http request ($_GET and $_POST) contains valid Tela-related variables.
     *
     * @param array $vars Var array to check, if not given is retrieved from superglobals
     * @return boolean
     * @access private
     */
    private function checkUrlVars() {
        $request = $this->getRequest();
        return $request[ 'is_tela' ]
            && ! empty( $request[ 'action' ] )
            && ! empty( $request[ 'nonce' ] )
            && ! empty( $request[ 'blogid' ] );
    }

    private function checkAction() {
        $action = $this->getAction();
		$request = $this->getRequest();     
        return ( $action->getVar( 'public' ) || is_user_logged_in() )
			&& $action->getBlogId() === $request[ 'blogid' ];
    }

    private function checkSide() {
        $action = $this->getAction();
		$request = $this->getRequest();
		$bad_side = $request['from_admin'] ? \GM\Tela::FRONTEND : \GM\Tela::BACKEND;
        return $this->action->getVar( 'side' ) !== $bad_side;
    }

}