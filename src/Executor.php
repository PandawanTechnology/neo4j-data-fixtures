<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use GraphAware\Neo4j\Client\Connection\Connection;

class Executor
{
    /**
     * @var Connection
     */
    private $connection;

    /** Logger callback for logging messages when loading data fixtures */
    private $logger;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set the logger callable to execute with the log() method.
     *
     * @param mixed $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
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
            if ($this->logger) {
                $this->log('loading ' . get_class($fixture));
            }

            $fixture->load($this->connection);
        }
    }

    /**
     * Logs a message using the logger.
     *
     * @param string $message
     */
    public function log($message)
    {
        $logger = $this->logger;
        $logger($message);
    }
}
