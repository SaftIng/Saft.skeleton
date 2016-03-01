<?php

namespace Saft\Skeleton\PropertyHelper;

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Saft\Rdf\NamedNode;
use Saft\Rdf\NamedNodeImpl;
use Saft\Store\Store;

/**
 * Encapsulates PropertyHelper related classes, ensures correct usage and helps users that way
 * to use this stuff properly.
 */
class RequestHandler
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var NamedNode
     */
    protected $graph;

    /**
     * @var AbstractIndex
     */
    protected $index;

    /**
     * @var IStorage
     */
    protected $storage;

    /**
     * @param Store $store
     * @param NamedNode $graph Instance of the graph, whose ressources will be collected for the index
     */
    public function __construct(Store $store, NamedNode $graph)
    {
        $this->graph = $graph;
        $this->store = $store;
    }

    /**
     * @return array Array of string containing available property types.
     */
    public function getAvailableCacheBackends()
    {
        return array(
            'file', 'memory'
        );
    }

    /**
     * @return array Array of string containing available property types.
     */
    public function getAvailableTypes()
    {
        return array(
            'title'
        );
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param string $action
     * @param array $payload Neccessary configuration to execute the requested action.
     */
    public function handle($action, $payload = array())
    {
        if (null == $this->index) {
            throw new \Exception('Please call setType before handle to initialize the index.');
        }

        $action = strtolower($action);

        /*
         * create index for all resources of a graph
         */
        if ('createindex' == $action) {
            return $this->index->createIndex();

        } elseif('fetchtitles' == $action) {
            return $this->index->fetchTitles($payload);
        }

        throw new \Exception('Unknown $action given: '. $action);
    }

    /**
     * Initializes the cache backend and storage.
     *
     * Configuration information (besides name) for each backend:
     *
     * - file
     *   - dir - Path to the store where the data to be stored.
     *
     * - memory - No additional configuration needed.
     *
     * @param array $configuration
     * @throws \Exception if parameter $configuration is empty
     * @throws \Exception if parameter $configuration does not have key "name" set
     * @throws \Exception if an unknown name was given.
     */
    public function setupCache(array $configuration)
    {
        if (0 == count(array_keys($configuration))) {
            throw new \Exception('Parameter $configuration must not be empty.');
        } elseif (false === isset($configuration['name'])) {
            throw new \Exception('Parameter $configuration does not have key "name" set.');
        }

        switch($configuration['name']) {
            // file storage: stores data in files
            case 'file':
                $this->storage = new FileStorage($configuration['dir']);
                break;

            // memory storage: lasts as long as the current PHP session is executed.
            case 'memory':
                $this->storage = new MemoryStorage();
                break;

            default:
                throw new \Exception('Unknown name given: '. $configuration['name']);
        }

        $this->cache = new Cache($this->storage);
    }

    /**
     * Based on given type, according index will be setup.
     *
     * @param string $type Type of the property, e.g. title. Check getAvailableTypes for more information.
     * @throws \Exception if unknown type was given.
     */
    public function setType($type)
    {
        if (null == $this->cache) {
            throw new \Exception('Please call setupCache before setType to initialize the cache environment.');
        }

        // type recognized
        if (in_array($type, $this->getAvailableTypes())) {
            switch($type) {
                case 'title':
                    $this->index = new TitleHelperIndex($this->cache, $this->store, $this->graph);
                    return;
            }
        }

        throw new \Exception('Unknown type given: '. $type);
    }
}
