<?php namespace GM\Tela\Tests;

use GM\Tela\ActionArgsValidator as Validator;
use GM\Tela as T;

class ActionArgsValidatorTest extends TestCase {

    function testValidateDefault() {
        $validator = new Validator;
        $validated = $validator->validate( [ ] );
        assertSame( T::BACKEND, $validated[ 'side' ] );
        assertFalse( $validated[ 'public' ] );
    }

    function testValidatePublicForceFrontendIfNotSide() {
        $validator = new Validator;
        $validated = $validator->validate( [ 'public' => TRUE ] );
        assertSame( T::FRONTEND, $validated[ 'side' ] );
        assertTrue( $validated[ 'public' ] );
    }

    function testValidatePublicFalseIfNotDefined() {
        $validator = new Validator;
        $validated_f = $validator->validate( [ 'side' => T::FRONTEND ] );
        $validated_b = $validator->validate( [ 'side' => T::BACKEND ] );
        assertSame( T::FRONTEND, $validated_f[ 'side' ] );
        assertFalse( $validated_f[ 'public' ] );
        assertSame( T::BACKEND, $validated_b[ 'side' ] );
        assertFalse( $validated_b[ 'public' ] );
    }

    function testValidatePublicForceFrontendIfSide() {
        $validator = new Validator;
        $validated = $validator->validate( [ 'side' => T::BACKEND, 'public' => TRUE ] );
        assertSame( T::BACKEND, $validated[ 'side' ] );
        assertFalse( $validated[ 'public' ] );
    }

    function testValidateSideAlias() {
        $aliases = [
            'backend'   => [ T::BACKEND, FALSE ],
            'back'      => [ T::BACKEND, FALSE ],
            'admin'     => [ T::BACKEND, FALSE ],
            'frontend'  => [ T::FRONTEND, TRUE ],
            'front'     => [ T::FRONTEND, TRUE ],
            'public'    => [ T::FRONTEND, TRUE ],
            'both'      => [ T::BOTHSIDES, TRUE ],
            'bothsides' => [ T::BOTHSIDES, TRUE ],
            'all'       => [ T::BOTHSIDES, TRUE ],
            'any'       => [ T::BOTHSIDES, TRUE ],
            '*'         => [ T::BOTHSIDES, TRUE ]
        ];
        $validator = new Validator;
        foreach ( $aliases as $alias => $side_data ) {
            $validated = $validator->validate( [ 'side' => $alias, 'public' => TRUE ] );
            assertSame( $side_data[ 0 ], $validated[ 'side' ] );
            assertSame( $side_data[ 1 ], $validated[ 'public' ] );
        }
    }

}