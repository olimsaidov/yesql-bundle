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

${methods}
    private \$connection;

    private \$isPostgres;

    public function __construct(Connection \$connection) 
    {
        \$this->connection = \$connection;
        \$this->isPostgres = \$connection->getDriver()->getName() == 'pdo_pgsql';
    }
    
    private function execute(\$args, \$sql) 
    {
        \$statement = \$this->connection->prepare(\$sql);

        \$args = count(\$args) == 1 && is_array(\$args[0]) ? \$args[0] : \$args;
        \$args = \$this->isPostgres ? \$this->booleanify(\$args) : \$args;
        \$statement->execute(\$args);

        return \$statement;
    }
    
    public function booleanify(\$array) 
    {
        \$result = [];

        foreach (\$array as \$key => \$value) {
            \$result[\$key] =  is_bool(\$value)
                ? (\$value ? 't' : 'f')
                : \$value;
        }

        return \$result;
    }
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
        \$statement = \$this->execute(\$args, ${sql});
        
${returnStatement}
    }


PHP;
        }

        return $result;
    }
}
