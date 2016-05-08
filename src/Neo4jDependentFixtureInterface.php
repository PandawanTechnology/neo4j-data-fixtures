<?php

namespace PandawanTechnology\Neo4jDataFixtures;

interface Neo4jDependentFixtureInterface extends Neo4jFixtureInterface
{
    /**
     * Return the list of classes that must be loaded upfront.
     *
     * @return array
     */
    public function getDependencies();
}
