<?php

namespace Saft\Skeleton\Test\Integration\PropertyHelper;

class RequestHandlerMemcachedCacheTest extends AbstractRequestHandlerTest
{
    public function setUp()
    {
        if (false === extension_loaded('memcache')) {
            $this->markTestSkipped('PHP-extension memcache is not loaded. Try sudo apt-get install php5-memcache');
        }

        parent::setUp();
    }

    public function setupCache()
    {
        $this->fixture->setupCache(array(
            'name' => 'memcached',
            'host' => 'localhost',
            'port' => 11211,
        ));
    }
}
