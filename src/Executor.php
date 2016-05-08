<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use GraphAware\Neo4j\Client\Connection\Connection;

class Executor
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $fixtures
     * @param bool  $haltOnEmpty
     */
    public function execute(array $fixtures = [], $haltOnEmpty = true)
    {
        if (!count($fixtures) && $haltOnEmpty) {
            throw new \RuntimeException('No fixtures found. You should load them first.');
        }

        foreach ($fixtures as $fixture) {
            $fixture->load($this->connection);
        }
    }
}
