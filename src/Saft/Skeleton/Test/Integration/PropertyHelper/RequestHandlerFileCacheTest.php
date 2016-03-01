<?php

namespace Saft\Skeleton\Test\Integration\PropertyHelper;

class RequestHandlerFileCacheTest extends AbstractRequestHandlerTest
{
    public function setupCache()
    {
        $this->fixture->setupCache(array(
            'name' => 'file',
            'dir' => sys_get_temp_dir()
        ));
    }
}
