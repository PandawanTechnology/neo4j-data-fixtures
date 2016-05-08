<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use GraphAware\Common\Connection\ConnectionInterface;

class Executor
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
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
