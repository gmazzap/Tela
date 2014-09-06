<?php namespace GM\Tela;

interface RequestInterface {

    /**
     * Get and sanitize Tela-related variables from $_GET and $_POST and return them in a single
     * indexed array.
     *
     * @return array
     */
    public function get();
}