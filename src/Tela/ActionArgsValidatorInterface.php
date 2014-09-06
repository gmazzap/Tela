<?php namespace GM\Tela;

interface ActionArgsValidatorInterface {

    /**
     * Ensure consistency in Action object arguments
     *
     * @param array $args
     */
    public function validate( Array $args );
}