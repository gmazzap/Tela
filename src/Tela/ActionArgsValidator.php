<?php namespace GM\Tela;

class ActionArgsValidator implements ActionArgsValidatorInterface {

    public function validate( Array $args ) {
        $valid_sides = [ \GM\Tela::BACKEND, \GM\Tela::FRONTEND, \GM\Tela::BOTHSIDES ];
        $all_sides = $this->getSides();
        $set = FALSE;
        if ( ! isset( $args[ 'side' ] ) ) {
            $args[ 'side' ] = \GM\Tela::BACKEND;
            $set = TRUE;
        }
        if ( ! $set && array_key_exists( strtolower( $args[ 'side' ] ), $all_sides ) ) {
            $args[ 'side' ] = $all_sides[ strtolower( $args[ 'side' ] ) ];
            $set = TRUE;
        }
        if ( ! $set && ! in_array( $args[ 'side' ], $valid_sides, TRUE ) ) {
            $args[ 'side' ] = \GM\Tela::BACKEND;
        }
        if ( ! isset( $args[ 'public' ] ) ) {
            $args[ 'public' ] = FALSE;
        } elseif ( (bool) $args[ 'public' ] === TRUE ) {
            $args[ 'side' ] = \GM\Tela::FRONTEND;
        }
        return $args;
    }

    public function getSides() {
        return [
            'backend'   => \GM\Tela::BACKEND,
            'back'      => \GM\Tela::BACKEND,
            'admin'     => \GM\Tela::BACKEND,
            'frontend'  => \GM\Tela::FRONTEND,
            'front'     => \GM\Tela::FRONTEND,
            'public'    => \GM\Tela::FRONTEND,
            'both'      => \GM\Tela::BOTHSIDES,
            'bothsides' => \GM\Tela::BOTHSIDES,
            'all'       => \GM\Tela::BOTHSIDES,
            'any'       => \GM\Tela::BOTHSIDES,
            '*'         => \GM\Tela::BOTHSIDES
        ];
    }

}