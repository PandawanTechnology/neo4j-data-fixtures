<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use GraphAware\Common\Connection\ConnectionInterface;

interface FixtureInterface
{
    /**
     * Load some data fixtures.
     *
     * @param ConnectionInterface $connection
     */
    public function load(ConnectionInterface $connection);
}
