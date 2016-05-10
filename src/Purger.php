<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use GraphAware\Neo4j\Client\Connection\Connection;

class Purger
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
     * Empty the database content.
     */
    public function purge()
    {
        $this->connection->getSession()->run('MATCH (n) DETACH DELETE n');
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
