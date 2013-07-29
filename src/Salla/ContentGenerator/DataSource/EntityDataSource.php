<?php

namespace Salla\ContentGenerator\DataSource;

class EntityDataSource implements DataSourceInterface
{

    const USE_METHODS    = 1;

    const USE_PROPERTIES = 2;

    const USE_BOTH       = 3;

    protected $aliases;

    protected $objects;

    protected $flags;

    public function __construct(array $objects, $flags = self::USE_BOTH)
    {
        $this->objects = $objects;
        $this->flags   = $flags;
    }

    public function getData($variables)
    {
        $results = array();

        foreach ($this->objects as $object) {
            $objectData = array();
            foreach ($variables as $variable) {
                $method = 'get' . ucfirst($variable);

                if ($this->flags & self::USE_METHODS && method_exists($object, $method)) {
                    $objectData[$variable] = $object->$method();
                } else if ($this->flags & self::USE_PROPERTIES && property_exists($object, $variable)) {
                    $objectData[$variable] = $object->$variable;
                }
            }

            $results[] = $objectData;
        }

        return $results;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

}
