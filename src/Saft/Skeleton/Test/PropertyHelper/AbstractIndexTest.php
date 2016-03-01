<?php

namespace Saft\Skeleton\Test\PropertyHelper;

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Saft\Rdf\LiteralImpl;
use Saft\Rdf\NodeFactoryImpl;
use Saft\Rdf\NamedNodeImpl;
use Saft\Rdf\StatementFactoryImpl;
use Saft\Rdf\StatementImpl;
use Saft\Rdf\StatementIteratorFactoryImpl;
use Saft\Skeleton\Test\TestCase;
use Saft\Sparql\Query\QueryFactoryImpl;
use Saft\Store\BasicTriplePatternStore;

abstract class AbstractIndexTest extends TestCase
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
     * Tests for createIndex
     */

    public function testCreateIndex()
    {
        $this->fillStoreWithTestData();

        // create property index
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
            $this->fixture->createIndex()
        );

        // test created cache entries
        $this->assertEquals(
            array(
                'titles' => array(array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's1 dcterms title'))
            ),
            $this->cache->load($this->testGraph->getUri() . '.http://saft/test/s1')
        );
        $this->assertEquals(
            array(
                'titles' => array(
                    array('uri' => 'http://www.w3.org/2000/01/rdf-schema#label', 'title' => 's2 rdfs label'),
                    array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's2 dcterms title - 2'),
                    array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's2 dcterms title'),
                )
            ),
            $this->cache->load($this->testGraph->getUri() . '.http://saft/test/s2')
        );
    }

    public function testCreateIndexMultipleProperties()
    {
        $this->fillStoreWithTestData();

        $this->fixture = $this->getMockForAbstractClass(
            '\Saft\Skeleton\PropertyHelper\AbstractIndex',
            array(
                $this->cache,
                $this->store,
                $this->testGraph,
                array(
                    'http://a',
                    'http://b',
                )
            )
        );

        // create property index
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
            $this->fixture->createIndex()
        );

        // test created cache entries
        $this->assertEquals(
            array(
                'titles' => array(array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's1 dcterms title'))
            ),
            $this->cache->load($this->testGraph->getUri() . '.http://saft/test/s1')
        );
        $this->assertEquals(
            array(
                'titles' => array(
                    array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's2 dcterms title - 2'),
                    array('uri' => 'http://purl.org/dc/terms/title', 'title' => 's2 dcterms title'),
                    array('uri' => 'http://www.w3.org/2000/01/rdf-schema#label', 'title' => 's2 rdfs label'),
                )
            ),
            $this->cache->load($this->testGraph->getUri() . '.http://saft/test/s2')
        );
    }

    /*
     * Tests fetchTitles
     */

    public function testFetchTitles()
    {
        $this->fillStoreWithTestData();

        // create property index
        $this->fixture->createIndex();

        // test created cache entries
        $this->assertEquals(
            array(
                'http://saft/test/s1' => 's1 dcterms title'
            ),
            $this->fixture->fetchTitles(
                array(
                    'http://saft/test/s1'
                )
            )
        );
    }

    public function testFetchTitlesNotAvailableUri()
    {
        $this->fillStoreWithTestData();

        // create property index
        $this->fixture->createIndex();

        // test created cache entries
        $this->assertEquals(
            array(
                'http://not_available' => ''
            ),
            $this->fixture->fetchTitles(
                array(
                    'http://not_available'
                )
            )
        );
    }

    public function testFetchTitlesEmptyUriList()
    {
        $this->fillStoreWithTestData();

        // create property index
        $this->fixture->createIndex();

        // test created cache entries
        $this->assertEquals(
            array(),
            $this->fixture->fetchTitles(
                array()
            )
        );
    }
}
