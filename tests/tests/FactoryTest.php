<?php namespace GM\Tela\Tests;

class FactoryTest extends TestCase {

    private function getFactory( $tela_id = 'test' ) {
        $factory = \Mockery::mock( 'GM\Tela\Factory' )->makePartial();
        $factory->setTelaId( $tela_id );
        $factory->registerType( 'stub', 'GM\Tela\Tests\StubInterface', 'GM\Tela\Tests\Stub' );
        return $factory;
    }

    function testFactoryError() {
        $error = $this->getFactory()->factory( 'stub', 'GM\Tela\Tests\EvilStub' );
        assertIsError( $error );
        assertArrayHasKey( 'tela-bad-factory-reflectionexception', $error->errors );
    }

    function testFactoryBadArgs() {
        $error = $this->getFactory()->factory( 'foo', 'GM\Tela\Tests\Stub' );
        assertIsError( $error );
        assertArrayHasKey( 'tela-bad-factory-args', $error->errors );
    }

    function testFactory() {
        $stub = $this->getFactory()->factory( 'stub', 'GM\Tela\Tests\Stub' );
        assertInstanceOf( 'GM\Tela\Tests\StubInterface', $stub );
    }

    function testFactoryFiltered() {
        \WP_Mock::onFilter( "tela_factory_test_stub" )
            ->with( 'GM\Tela\Tests\Stub' )
            ->reply( 'GM\Tela\Tests\StubAlt' );
        $stub = $this->getFactory()->factory( 'stub', 'GM\Tela\Tests\Stub' );
        assertEquals( 'GM\Tela\Tests\StubAlt', get_class( $stub ) );
    }

    function testFactoryFilteredFail() {
        \WP_Mock::onFilter( "tela_factory_test_stub" )
            ->with( 'GM\Tela\Tests\Stub' )
            ->reply( 'GM\Tela\Tests\Meh' );
        $stub = $this->getFactory()->factory( 'stub', 'GM\Tela\Tests\Stub' );
        assertNotInstanceOf( 'GM\Tela\Tests\StubAlt', $stub );
        assertEquals( 'GM\Tela\Tests\Stub', get_class( $stub ) );
    }

    function testFactoryAlt() {
        $stub = $this->getFactory()->factory( 'stub', 'GM\Tela\Tests\StubAlt' );
        assertEquals( 'GM\Tela\Tests\StubAlt', get_class( $stub ) );
    }

    function testFactoryTurnDefault() {
        $stub = $this->getFactory()->factory( 'stub', 'GM\Tela\Tests\Meh' );
        assertEquals( 'GM\Tela\Tests\Stub', get_class( $stub ) );
    }

    function testGetBadArgs() {
        $error = $this->getFactory()->get( 'foo', 'GM\Tela\Tests\Stub' );
        assertIsError( $error );
        assertArrayHasKey( 'tela-bad-factory-args', $error->errors );
    }

    function testGet() {
        $factory = $this->getFactory();
        $stub = new Stub;
        $factory->shouldReceive( 'factory' )->once()->with( 'stub', 'bar', [ ] )->andReturn( $stub );
        $first = $factory->get( 'stub', 'bar' );
        $second = $factory->get( 'stub', 'bar' );
        assertInstanceOf( 'GM\Tela\Tests\Stub', $first );
        assertSame( $second, $first );
    }

    function testGetNotRegistry() {
        $factory = $this->getFactory();
        $factory2 = $this->getFactory();
        $factory->shouldReceive( 'factory' )->with( 'stub', 'bar', [ ] )->andReturn( new Stub );
        $factory2->shouldReceive( 'factory' )->with( 'stub', 'bar', [ ] )->andReturn( new Stub );
        $first = $factory->get( 'stub', 'bar' );
        $second = $factory->get( 'stub', 'bar' );
        $third = $factory2->get( 'stub', 'bar' );
        assertFalse( $factory->hasRegistry( 'stub' ) );
        assertInstanceOf( 'GM\Tela\Tests\Stub', $first );
        assertInstanceOf( 'GM\Tela\Tests\Stub', $third );
        assertSame( $second, $first );
        assertNotSame( $third, $first );
    }

    function testGetFactoryError() {
        $factory = $this->getFactory();
        $error = new \GM\Tela\Error;
        $factory->shouldReceive( 'factory' )->once()->with( 'stub', 'bar', [ ] )->andReturn( $error );
        $factory->get( 'stub', 'bar' );
        assertIsError( $error );
    }

    function testRegistryBadArgs() {
        $error = $this->getFactory()->registry( 'foo', 'GM\Tela\Tests\Stub' );
        assertIsError( $error );
        assertArrayHasKey( 'tela-bad-factory-args', $error->errors );
    }

    function testRegistry() {
        $factory1 = $this->getFactory( 'foo' );
        $factory2 = $this->getFactory( 'bar' );
        $factory3 = $this->getFactory( 'baz' );
        $factory1->shouldReceive( 'factory' )
            ->with( 'stub', 'GM\Tela\Tests\Stub', [ ] )
            ->andReturn( new Stub );
        $factory2->shouldReceive( 'factory' )
            ->with( 'stub', 'GM\Tela\Tests\Stub', [ ] )
            ->andReturn( new Stub );
        $factory3->shouldReceive( 'factory' )
            ->with( 'stub', 'GM\Tela\Tests\Stub', [ ] )
            ->andReturn( new Stub );
        $one = $factory1->registry( 'stub', 'GM\Tela\Tests\Stub' );
        $two = $factory2->registry( 'stub', 'GM\Tela\Tests\Stub' );
        $three = $factory3->registry( 'stub', 'GM\Tela\Tests\Stub' );
        assertInstanceOf( 'GM\Tela\Tests\Stub', $one );
        assertSame( $one, $two );
        assertSame( $one, $three );
        assertSame( $two, $three );
    }

    function testFlushRegistry() {
        $factory1 = $this->getFactory( 'foo' );
        $factory2 = $this->getFactory( 'bar' );
        $factory1->shouldReceive( 'factory' )
            ->once()
            ->with( 'stub', 'GM\Tela\Tests\Stub', [ ] )
            ->andReturn( new Stub );
        $one = $factory1->registry( 'stub', 'GM\Tela\Tests\Stub' );
        $two = $factory2->registry( 'stub', 'GM\Tela\Tests\Stub' );
        assertInstanceOf( 'GM\Tela\Tests\Stub', $one );
        assertSame( $one, $two );
        assertTrue( $factory1->hasRegistry( 'stub' ) );
        \GM\Tela\Factory::flushRegistry();
        assertFalse( $factory1->hasRegistry( 'stub' ) );
    }

}