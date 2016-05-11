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
            $fetch_all = $query['fetch_all'];
            $fetch_column = $query['fetch_column'];
            $returning = $query['returning'];

            if ($returning || $type == 'select') {
                $method = $fetch_all
                    ? 'fetchAll(\PDO::FETCH_ASSOC)'
                    : ($fetch_column
                        ? 'fetchColumn()'
                        : 'fetch(\PDO::FETCH_ASSOC)');

                $returnStatement = <<<PHP
        return \$statement->${method};
PHP;
                $returnType = $fetch_column
                    ? 'mixed|false'
                    : 'array|false';
            }
            else if ($type == 'insert') {
                $returnStatement = <<<PHP
        return \$this->connection->lastInsertId();
PHP;
                $returnType = 'int';
            }
            else {
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
        
        \$statement = \$this->connection->prepare(/** @lang SQL */${sql});
        \$statement->execute(\$args);
        
${returnStatement}
    }


PHP;
        }

        return $result;
    }
}
