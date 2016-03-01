<?php

namespace Saft\Skeleton\Test\PropertyHelper;

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Saft\Rdf\LiteralImpl;
use Saft\Rdf\NamedNodeImpl;
use Saft\Rdf\NodeFactoryImpl;
use Saft\Rdf\StatementFactoryImpl;
use Saft\Rdf\StatementImpl;
use Saft\Rdf\StatementIteratorFactoryImpl;
use Saft\Skeleton\PropertyHelper\RequestHandler;
use Saft\Skeleton\Test\TestCase;
use Saft\Sparql\Query\QueryFactoryImpl;
use Saft\Store\BasicTriplePatternStore;

class RequestHandlerTest extends TestCase
{
    protected $cache;
    protected $storage;
    protected $store;

    public function setUp()
    {
        parent::setUp();

        // cache environment
        $this->storage = new MemoryStorage();
        $this->cache = new Cache($this->storage);

        // store
        $this->store = new BasicTriplePatternStore(
            new NodeFactoryImpl(),
            new StatementFactoryImpl(),
            new QueryFactoryImpl(),
            new StatementIteratorFactoryImpl()
        );

        $this->fixture = new RequestHandler($this->store, $this->testGraph);
    }

    public function fillStoreWithTestData()
    {
        // add test data to store
        $this->store->addStatements(
            array(
                new StatementImpl(
                    new NamedNodeImpl('http://saft/test/s1'),
                    new NamedNodeImpl('http://purl.org/dc/terms/title'),
                    new LiteralImpl('s1 dcterms title')
                ),
                new StatementImpl(
                    new NamedNodeImpl('http://saft/test/s2'),
                    new NamedNodeImpl('http://www.w3.org/2000/01/rdf-schema#label'),
                    new LiteralImpl('s2 rdfs label')
                ),
                new StatementImpl(
                    new NamedNodeImpl('http://saft/test/s2'),
                    new NamedNodeImpl('http://purl.org/dc/terms/title'),
                    new LiteralImpl('s2 dcterms title')
                ),
                new StatementImpl(
                    new NamedNodeImpl('http://saft/test/s2'),
                    new NamedNodeImpl('http://purl.org/dc/terms/title'),
                    new LiteralImpl('s2 dcterms title - 2')
                )
            ),
            $this->testGraph
        );
    }

    /*
     * Tests for getAvailableCacheBackends
     */

    public function testGetAvailableCacheBackends()
    {
        $this->assertEquals(
            array('file', 'memory'),
            $this->fixture->getAvailableCacheBackends()
        );
    }

    /*
     * Tests for getAvailableTypes
     */

    public function testGetAvailableTypes()
    {
        $this->assertEquals(
            array('title'),
            $this->fixture->getAvailableTypes()
        );
    }

    /*
     * Tests for handle
     */

    public function testHandleActionCreateIndex()
    {
        $this->fillStoreWithTestData();

        // setup cache using memory
        $this->fixture->setupCache(array('name' => 'memory'));

        // set titlehelper
        $this->fixture->setType('title');

        $this->assertEquals(
            array(
                'http://saft/test/s1' => array(
                    'titles' => array(array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's1 dcterms title'))
                ),
                'http://saft/test/s2' => array(
                    'titles' => array(
                        array('uri' => 'http://www.w3.org/2000/01/rdf-schema#label', 'title' => 's2 rdfs label'),
                        array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's2 dcterms title'),
                        array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's2 dcterms title - 2'),
                    )
                )
            ),
            $this->fixture->handle('createIndex')
        );
    }

    public function testHandleActionCreateIndexEmptyStore()
    {
        // setup cache using memory
        $this->fixture->setupCache(array('name' => 'memory'));

        // set titlehelper
        $this->fixture->setType('title');

        $this->assertEquals(array(), $this->fixture->handle('createIndex'));
    }

    public function testHandleActionCreateIndexNoIndexInitialized()
    {
        $this->setExpectedException('Exception');

        $this->assertNull($this->fixture->handle('createIndex'));
    }

    public function testHandleActionFetchTitles()
    {
        $this->fillStoreWithTestData();

        // setup cache using memory
        $this->fixture->setupCache(array('name' => 'memory'));

        // set titlehelper
        $this->fixture->setType('title');

        $this->assertTrue(0 < count($this->fixture->handle('createIndex')));

        $this->assertEquals(
            array('http://saft/test/s2' => 's2 rdfs label'),
            $this->fixture->handle('fetchTitles', array('http://saft/test/s2'))
        );
    }

    public function testHandleActionFetchTitlesEmptyPayload()
    {
        $this->fillStoreWithTestData();

        // setup cache using memory
        $this->fixture->setupCache(array('name' => 'memory'));

        // set titlehelper
        $this->fixture->setType('title');

        $this->assertTrue(0 < count($this->fixture->handle('createIndex')));

        $this->assertEquals(
            array(),
            $this->fixture->handle('fetchTitles')
        );
    }

    public function testHandleUnknownAction()
    {
        $this->setExpectedException('Exception');

        $this->fixture->handle('unknown');
    }

    /*
     * Tests for setType
     */

    public function testSetType()
    {
        $this->fixture->setupCache(array('name' => 'memory'));

        $this->assertNull($this->fixture->setType('title'));
    }

    public function testSetTypeSetupCacheNotCalledBefore()
    {
        $this->setExpectedException('Exception');

        $this->fixture->setType('title');
    }

    public function testSetTypeUnknownType()
    {
        $this->setExpectedException('Exception');

        $this->fixture->setType('unknown');
    }

    /*
     * Tests for setupCache
     */

    public function testSetupCacheParameter()
    {
        $this->assertNull($this->fixture->setupCache(array('name' => 'memory')));
    }

    public function testSetupCacheParameterConfigurationIsEmpty()
    {
        $this->setExpectedException('Exception');

        $this->fixture->setupCache(array());
    }

    public function testSetupCacheParameterConfigurationDoesNotHaveKeyNameSet()
    {
        $this->setExpectedException('Exception');

        $this->fixture->setupCache(array('foo' => 'bar'));
    }

    public function testSetupCacheUnknownNameGiven()
    {
        $this->setExpectedException('Exception');

        $this->fixture->setupCache(array('name' => 'unknown'));
    }
}
