<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;

interface DependentFixtureInterface extends FixtureInterface
{
    /**
     * Return the list of classes that must be loaded upfront.
     *
     * @return array
     */
    public function getDependencies();
}
