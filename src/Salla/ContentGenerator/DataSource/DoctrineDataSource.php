<?php

namespace Salla\ContentGenerator\DataSource;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Query\QueryBuilder;

class DoctrineDataSource implements DataSourceInterface
{

    protected $doctrine;

    protected $query;

    public function __construct($doctrine, $query)
    {
        $this->doctrine = $doctrine;
        $this->query    = $query;
    }

    public function getData($variables)
    {
        if ($this->query instanceof QueryBuilder) {
            $sql = $this->query->getSql();
        } else if (is_string($this->query) && $this->doctrine->getSchemaManager()->tablesExist($this->query)) {
            $sql = 'SELECT * FROM ' . $this->query;
        } else {
            
        }

        if (is_string($this->query)) {
            if ($this->doctrine->getSchemaManager()->tablesExist($this->query)) {
                $sql = 'SELECT * FROM ' . $this->query;
            } else {
                $sql = $this->query;
            }

            $stmt = $this->doctrine->prepare($sql);
        } else if ($this->query instanceof Statement) {
            $stmt = $this->query;
        } else {
            throw new \InvalidArgumentException('DoctrineDataSource accepts a table name, an sql string or a Doctrine\\DBAL\\Statement object.');
        }

        $stmt->execute();

        $results = array();

        while ($row = $stmt->fetch()) {
            $rowData = array();

            foreach ($variables as $var) {
                $rowData[$var] = $row[$var];
            }

            $results[] = $rowData;
        }

        return $results;

    }

}
