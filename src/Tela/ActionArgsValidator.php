<?php namespace GM\Tela;

class ActionArgsValidator implements ActionArgsValidatorInterface {

    public function validate( Array $args ) {
        $args[ 'public' ] = $this->normalizePublic( $args );
        $args[ 'side' ] = $this->normalizeSide( $args );
        if ( $args[ 'side' ] === \GM\Tela::BACKEND && $args[ 'public' ] ) {
            $args[ 'public' ] = FALSE;
        }
        return $args;
    }

    private function normalizePublic( Array $args ) {
        if ( ! isset( $args[ 'public' ] ) ) {
            return FALSE;
        }
        if (
            is_string( $args[ 'public' ] )
            && in_array( strtolower( $args[ 'public' ] ), [ 'yes', 'public', 'allowed' ], TRUE )
        ) {
            return TRUE;
        } elseif ( is_callable( $args[ 'public' ] ) ) {
            return (bool) call_user_func( $args[ 'public' ], $args );
        }
        return ! empty( $args[ 'public' ] );
    }

    private function normalizeSide( $args ) {
        $valid_sides = [ \GM\Tela::BACKEND, \GM\Tela::FRONTEND, \GM\Tela::BOTHSIDES ];
        $all_sides = $this->getSides();
        if ( ! isset( $args[ 'side' ] ) ) {
            return $args[ 'public' ] ? \GM\Tela::FRONTEND : \GM\Tela::BACKEND;
        }
        if (
            is_string( $args[ 'side' ] )
            && array_key_exists( strtolower( $args[ 'side' ] ), $all_sides )
        ) {
            return $all_sides[ strtolower( $args[ 'side' ] ) ];
        }
        if ( ! in_array( $args[ 'side' ], $valid_sides, TRUE ) ) {
            return \GM\Tela::BACKEND;
        }
        return $args[ 'side' ];
    }

    private function getSides() {
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