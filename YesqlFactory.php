<?php

namespace Ox\YesqlBundle;

use Doctrine\DBAL\Connection;

class YesqlFactory
{
    public function create($path, Connection $connection)
    {
        $class = basename($path, '.php');

        require_once $path;
        return new $class($connection);
    }
}
