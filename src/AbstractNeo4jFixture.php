<?php

namespace PandawanTechnology\Neo4jDataFixtures;

abstract class AbstractNeo4jFixture implements Neo4jFixtureInterface
{
    /**
     * @var array
     */
    protected static $references = [];

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setReference($name, $value)
    {
        static::$references[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addReference($name, $value)
    {
        if (isset(static::$references[$name])) {
            throw new \InvalidArgumentException(sprintf('An entry already exists with the name "%s"', $name));
        }

        $this->setReference($name, $value);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getReference($name)
    {
        if (!isset(static::$references[$name])) {
            throw new \OutOfBoundsException(sprintf('The reference "%s" does not exist.', $name));
        }

        return static::$references[$name];
    }
}
