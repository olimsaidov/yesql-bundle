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
            $sql = var_export("\n" . preg_replace('/^/m', '            ', $query['sql']), true);
            $name = $query['name'];
            $return = $query['return'];
            $arguments = $query['arguments'] ?? [];
            $returnType = '';

            if ($return == 'statement') {
                $returnStatement = <<<PHP
        return \$statement;
PHP;
                $returnType = '@return Doctrine\DBAL\Driver\PDOStatement';
            } else if ($return == 'lastInsertId') {
                $returnStatement = <<<PHP
        return \$this->connection->lastInsertId();
PHP;
                $returnType = '@return int';
            } else if ($return == 'rowCount') {
                $returnStatement = <<<PHP
        return \$statement->rowCount();
PHP;
                $returnType = '@return int';
            }
            else {
                $arguments = join(' | ', array_map(function($argument) {
                    return '\PDO::' . $argument;
                }, $arguments));
                $returnStatement = <<<PHP
        return \$statement->${return}(${arguments});
PHP;
                $returnType = '@return mixed';
            }

            $result .= <<<PHP
    /**
     * ${returnType}
     */
    public function ${name}(...\$args) {
        if (count(\$args) == 1 && is_array(\$args[0])) {
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
