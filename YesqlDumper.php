<?php

namespace Ox\YesqlBundle;

class YesqlDumper
{
    public function dump($queries, $className)
    {
        $methods = $this->generateMethods($queries);

        return <<<PHP
<?php

use Doctrine\DBAL\Connection;

class ${className} {

    /**
     * @var Connection
     */
    private \$connection;

    public function __construct(Connection \$connection) {
        \$this->connection = \$connection;
    }
    
${methods}
}   

PHP;
    }

    public function generateMethods($queries)
    {
        $result = '';

        foreach ($queries as $query) {
            $sql = var_export($query['sql'], true);
            $name = $query['name'];
            $type = $query['type'];
            $line = $query['line'];
            $file = $query['file'];
            $multiple = $query['multiple'] ?? false;

            switch ($type) {
                case 'insert':
                    $returnStatement = <<<PHP
        return \$this->connection->lastInsertId();
PHP;
                    $returnType = 'int';
                    break;
                case 'select':
                    $method = $multiple ? 'fetchAll' : 'fetch';
                    $returnStatement = <<<PHP
        return \$statement->${method}(\PDO::FETCH_ASSOC);
PHP;
                    $returnType = 'array|false';
                    break;
                default:
                    $returnStatement = <<<PHP
        return \$statement->rowCount();
PHP;
                    $returnType = 'int';
            }

            $result .= <<<PHP
    /**
     * @return ${returnType}
     * @see ${file}:${line}
     */
    public function ${name}(...\$args) {
        if (count(\$args) == 1 && is_array(\$args[0])) {
            // named parameters
            \$args = \$args[0];
        }
        
        \$statement = \$this->connection->prepare(${sql});
        \$statement->execute(\$args);
        
${returnStatement}
    }


PHP;
        }

        return $result;
    }
}
