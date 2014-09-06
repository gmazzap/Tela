<?php
if ( function_exists( 'do_action' ) ) {
    return;
}
if ( ! defined( 'TELAPATH' ) ) define( 'TELAPATH', dirname( dirname( __FILE__ ) ) );

foreach ( glob( TELAPATH . "/tests/helpers/*.php" ) as $file ) {
    require_once $file;
}

require_once TELAPATH . '/vendor/autoload.php';

require_once TELAPATH . '/vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';
