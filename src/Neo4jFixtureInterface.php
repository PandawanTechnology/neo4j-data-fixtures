<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use GraphAware\Neo4j\Client\Connection\Connection;

interface Neo4jFixtureInterface
{
    /**
     * Load some data fixtures.
     *
     * @param Connection $connection
     */
    public function load(Connection $connection);
}
