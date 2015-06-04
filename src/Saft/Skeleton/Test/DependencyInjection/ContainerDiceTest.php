<?php

namespace Saft\Skeleton\Test\DependencyInjection;

use Saft\Rdf\LiteralImpl;
use Saft\Skeleton\DependencyInjection\ContainerDice;
use Saft\Skeleton\Test\TestCase;

class ContainerDiceTest extends TestCase
{
    /*
     * Tests for createInstanceOf
     */

    public function testCreateInstanceOfBasic()
    {
        $fixture = new ContainerDice();
        $this->assertEquals(
            new LiteralImpl('foo'),
            $fixture->createInstanceOf('Saft\Rdf\LiteralImpl', array('foo'))
        );
    }

    public function testCreateInstanceOfUsageOfSubstitutionsVirtuoso()
    {
        if (false === isset($this->config['virtuosoConfig'])) {
            $this->markTestSkipped('Array virtuosoConfig is not set in the test-config.yml.');
        }

        $fixture = new ContainerDice();

        $virtuoso = $fixture->createInstanceOf(
            'Saft\Addition\Virtuoso\Store\Virtuoso',
            array($this->config['virtuosoConfig'])
        );

        $this->assertTrue(is_array($virtuoso->getGraphs()));
    }
}
