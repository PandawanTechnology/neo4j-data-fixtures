<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use GraphAware\Neo4j\Client\Connection\Connection;

interface FixtureInterface
{
    /**
     * Load some data fixtures.
     *
     * @param Connection $connection
     */
    public function load(Connection $connection);
}
