<?php

namespace Ox\YesqlBundle;

class YesqlParser
{
    public function parse($file)
    {
        $queries = [];

        foreach (file($file) as $line => $row) {
            $line += 1;
            $isComment = strpos($row, '--') === 0;

            if ($isComment && ($name = $this->getMethodName($row))) {
                $queries[] = [
                    'comment' => $row,
                    'name' => $name,
                    'line' => $line,
                    'file' => $file,
                    'sql' => '',
                ];
            } elseif (!$isComment) {
                if ($queries) {
                    $queries[count($queries) - 1]['sql'] .= $row;
                } else {
                    throw new \LogicException("Found query without comment header at ${path}:${line}");
                }
            }
        }

        foreach ($queries as &$query) {
            $query['sql'] = trim($query['sql']);

            if (!$query['sql']) {
                throw new \LogicException("Empty query at ${query['file']}:${query['line']}");
            }

            if (!preg_match('/^(select|insert|update|delete)/i', $query['sql'], $matches)) {
                throw new \LogicException("Cannot detect type of query at ${query['file']}:${query['line']}");
            }

            $query['type'] = strtolower($matches[1]);
            $query['returning'] = preg_match('/\sreturning\s/i', $query['sql']) == 1;

            preg_match('/^([^\|\*]+)(\||\*)*$/', $query['name'], $matches);

            $query['name'] = $matches[1];
            $query['fetch_all'] = $matches[2] == '*';
            $query['fetch_column'] = $matches[2] == '|';
        }

        return $queries;
    }

    public function getMethodName($line)
    {
        preg_match("/\bname:\s*(.+)/", $line, $matches);
        return $matches[1] ?? null;
    }
}
