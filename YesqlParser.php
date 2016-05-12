<?php

namespace Ox\YesqlBundle;

class YesqlParser
{
    public function parse($file)
    {
        $blocks = [];

        $comment = '';
        $sql = '';
        $state = 'comment';
        $trim = " \t\n\r\0\x0B;";

        foreach (file($file) as $row) {
            $isComment = strpos($row, '--') === 0;

            if ($isComment) {
                if ($state != 'comment' && trim($comment) && trim($sql, $trim)) {
                    $blocks[] = [trim($comment), trim($sql, $trim)];

                    $comment = '';
                    $sql = '';
                }

                $state = 'comment';
                $comment .= $row;
            } else {
                $sql .= $row;
                $state = '$sql';
            }

        }

        if ($state != 'comment'  && trim($comment) && trim($sql, $trim)) {
            $blocks[] = [trim($comment), trim($sql, $trim)];
        }

        $queries = [];
        foreach ($blocks as list ($comment, $sql)) {
            $query = ['sql' => $sql];

            if (!preg_match('/--\s*name:\s*(\S+)/', $comment, $matches)) {
                throw new \LogicException('Query name not found: ' . $file);
            }
            $query['name'] = $matches[1];

            if (!preg_match('/^\s*(select|insert|update|delete)/i', $sql, $matches)) {
                throw new \LogicException('Query type not detected: ' . $file);
            }
            $type = strtolower($matches[1]);

            if ($type == 'insert') {
                $query['return'] = 'lastInsertId';
            } else if ($type == 'select') {
                $query['return'] = 'fetch';
            } else {
                $query['return'] = 'rowCount';
            }

            if (preg_match('/--\s*return:\s*(\S+)\s*(\S.*)?/', $comment, $matches)) {
                $query['return'] = $matches[1];

                if (isset($matches[2]) && preg_match_all('/(\S+)\s*/', $matches[2], $matches)) {
                    $query['arguments'] = $matches[1];
                }
            }

            $queries[] = $query;
        }

        return $queries;
    }
}
