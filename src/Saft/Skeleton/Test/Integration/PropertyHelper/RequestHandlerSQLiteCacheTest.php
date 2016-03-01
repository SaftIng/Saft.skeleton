<?php

namespace Saft\Skeleton\Test\Integration\PropertyHelper;

class RequestHandlerSQLiteCacheTest extends AbstractRequestHandlerTest
{
    public function setUp()
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PHP-extension pdo_sqlite is not loaded.');
        }

        parent::setUp();
    }

    public function setupCache()
    {
        $this->fixture->setupCache(array(
            'name' => 'sqlite',
            'path' => sys_get_temp_dir() . '/requestHandlerSQLiteBackend.sq3'
        ));
    }
}
