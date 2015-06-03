<?php

namespace Saft\Skeleton\Test\DependencyInjection\Dice;

use Saft\Rdf\LiteralImpl;
use Saft\Skeleton\DependencyInjection\Dice\Container;
use Saft\Skeleton\Test\TestCase;

class ContainerTest extends TestCase
{
    /*
     * Tests for createInstanceOf
     */

    public function testCreateInstanceOfBasic()
    {
        $fixture = new Container();
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

        $fixture = new Container();

        $virtuoso = $fixture->createInstanceOf(
            'Saft\Backend\Virtuoso\Store\Virtuoso',
            array($this->config['virtuosoConfig'])
        );

        $this->assertTrue(is_array($virtuoso->getGraphs()));
    }
}
