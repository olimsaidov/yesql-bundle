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

function booleanify(\$acc, \$value, \$index) {
    if (is_bool(\$value)) {
        \$acc[\$index] =  \$value ? 't' : 'f';
    } else {
        \$acc[\$index] = \$value;
    }
}


class ${className} {

${methods}
    private \$connection;

    private \$isPostgers;

    public function __construct(Connection \$connection) {
        \$this->connection = \$connection;
        \$this->isPostgers = \$connection->getDriver()->getName() == 'pdo_pgsql';
    }
    
    private function execute(\$args, \$sql) {
        \$statement = \$this->connection->prepare(\$sql);
        \$statement->execute(
            count(\$args) == 1 && is_array(\$args[0])
                ? \$this->isPostgers ? array_reduce(\$args[0], 'booleanify', []) : \$args[0]
                : \$this->isPostgers ? array_reduce(\$args, 'booleanify', []) : \$args);

        return \$statement;
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
