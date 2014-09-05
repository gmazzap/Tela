<?php namespace GM\Tela;

class ArgsSanitizer implements ArgsSanitizerInterface {

    public function sanitize( Array $args ) {
		$set = FALSE;
		if ( ! isset( $args[ 'side' ] ) ) {
            $args[ 'side' ] = \GM\Tela::BACKEND;
			$set = TRUE;
        }
        $sides = [
            'backend'   =>  \GM\Tela::BACKEND,
            'back'      =>  \GM\Tela::BACKEND,
            'admin'     =>  \GM\Tela::BACKEND,
            'frontend'  =>  \GM\Tela::FRONTEND,
            'front'     =>  \GM\Tela::FRONTEND,
            'public'    =>  \GM\Tela::FRONTEND,
            'both'      =>  \GM\Tela::BOTHSIDES,
            'bothsides' =>  \GM\Tela::BOTHSIDES,
            'all'       =>  \GM\Tela::BOTHSIDES,
            'any'       =>  \GM\Tela::BOTHSIDES,
            '*'         =>  \GM\Tela::BOTHSIDES
        ];
		$valid = [ \GM\Tela::BACKEND, \GM\Tela::FRONTEND, \GM\Tela::BOTHSIDES ];
        if ( ! $set && array_key_exists( strtolower( $args[ 'side' ] ), $sides ) ) {
            $args[ 'side' ] = $sides[ strtolower( $args[ 'side' ] ) ];
			$set = TRUE;
        }
		if ( ! $set && ! in_array( $args[ 'side' ], $valid, TRUE ) ) {
			$args[ 'side' ] = \GM\Tela::BACKEND;
		}
        if ( ! isset( $args[ 'public' ] ) ) {
            $args[ 'public' ] = FALSE;
        } elseif ( (bool) $args[ 'public' ] === TRUE ) {
            $args[ 'side' ] =  \GM\Tela::FRONTEND;
        }
        return $args;
    }

}