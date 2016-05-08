<?php

namespace PandawanTechnology\Neo4jDataFixtures;

use PandawanTechnology\Neo4jDataFixtures\Exception\CircularReferenceException;

class Loader
{
    /**
     * @var FixtureInterface[]
     */
    private $fixtures = [];

    /**
     * @var FixtureInterface[]
     */
    private $orderedFixtures = [];

    /**
     * @var bool
     */
    private $orderFixturesByDependencies = false;

    /**
     * @var string
     */
    private $fileExtension = '.php';

    /**
     * Find fixtures classes in a given directory and load them.
     *
     * @param string $dir Directory to find fixture classes in.
     *
     * @return array $fixtures Array of loaded fixture object instances.
     */
    public function loadFromDirectory($dir)
    {
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf('"%s" does not exist', $dir));
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        return $this->loadFromIterator($iterator);
    }

    /**
     * Find fixtures classes in a given file and load them.
     *
     * @param string $fileName File to find fixture classes in.
     *
     * @return array $fixtures Array of loaded fixture object instances.
     */
    public function loadFromFile($fileName)
    {
        if (!is_readable($fileName)) {
            throw new \InvalidArgumentException(sprintf('"%s" does not exist or is not readable', $fileName));
        }

        $iterator = new \ArrayIterator(array(new \SplFileInfo($fileName)));

        return $this->loadFromIterator($iterator);
    }

    /**
     * Has fixture?
     *
     * @param FixtureInterface $fixture
     *
     * @return bool
     */
    public function hasFixture($fixture)
    {
        return isset($this->fixtures[get_class($fixture)]);
    }

    /**
     * Add a fixture object instance to the loader.
     *
     * @param FixtureInterface $fixture
     */
    public function addFixture(FixtureInterface $fixture)
    {
        $fixtureClass = get_class($fixture);

        if (isset($this->fixtures[$fixtureClass])) {
            return;
        }

        $this->fixtures[$fixtureClass] = $fixture;

        if ($fixture instanceof DependentFixtureInterface) {
            $this->orderFixturesByDependencies = true;

            foreach ($fixture->getDependencies() as $class) {
                if (class_exists($class)) {
                    $this->addFixture(new $class());
                }
            }
        }
    }

    /**
     * Returns the array of data fixtures to execute.
     *
     * @return array $fixtures
     */
    public function getFixtures()
    {
        $this->orderedFixtures = [];

        if ($this->orderFixturesByDependencies) {
            $this->orderFixturesByDependencies();
        }

        if (!$this->orderFixturesByDependencies) {
            $this->orderedFixtures = $this->fixtures;
        }

        return $this->orderedFixtures;
    }

    /**
     * Check if a given fixture is transient and should not be considered a data fixtures
     * class.
     *
     * @param string $className
     *
     * @return bool
     */
    public function isTransient($className)
    {
        $rc = new \ReflectionClass($className);

        if ($rc->isAbstract()) {
            return true;
        }

        $interfaces = class_implements($className);

        return !in_array('PandawanTechnology\Neo4jDataFixtures\FixtureInterface', $interfaces);
    }

    /**
     * Orders fixtures by dependencies.
     */
    private function orderFixturesByDependencies()
    {
        $sequenceForClasses = [];

        // If fixtures were already ordered by number then we need
        // to remove classes which are not instances of OrderedFixtureInterface
        // in case fixtures implementing DependentFixtureInterface exist.
        // This is because, in that case, the method orderFixturesByDependencies
        // will handle all fixtures which are not instances of
        // OrderedFixtureInterface

        // First we determine which classes has dependencies and which don't
        foreach ($this->fixtures as $fixture) {
            $fixtureClass = get_class($fixture);

            if ($fixture instanceof DependentFixtureInterface) {
                $dependenciesClasses = $fixture->getDependencies();

                $this->validateDependencies($dependenciesClasses);
                if (!is_array($dependenciesClasses) || empty($dependenciesClasses)) {
                    throw new \InvalidArgumentException(sprintf('Method "%s" in class "%s" must return an array of classes which are dependencies for the fixture, and it must be NOT empty.', 'getDependencies', $fixtureClass));
                }

                if (in_array($fixtureClass, $dependenciesClasses)) {
                    throw new \InvalidArgumentException(sprintf('Class "%s" can\'t have itself as a dependency', $fixtureClass));
                }

                // We mark this class as unsequenced
                $sequenceForClasses[$fixtureClass] = -1;
            } else {
                // This class has no dependencies, so we assign 0
                $sequenceForClasses[$fixtureClass] = 0;
            }
        }
        // Now we order fixtures by sequence
        $sequence = 1;
        $lastCount = -1;

        while (($count = count($unsequencedClasses = $this->getUnsequencedClasses($sequenceForClasses))) > 0 && $count !== $lastCount) {
            foreach ($unsequencedClasses as $key => $class) {
                /** @var DependentFixtureInterface $fixture */
                $fixture = $this->fixtures[$class];
                $dependencies = $fixture->getDependencies();
                $unsequencedDependencies = $this->getUnsequencedClasses($sequenceForClasses, $dependencies);

                if (count($unsequencedDependencies) === 0) {
                    $sequenceForClasses[$class] = $sequence++;
                }
            }

            $lastCount = $count;
        }
        $orderedFixtures = array();

        // If there're fixtures unsequenced left and they couldn't be sequenced,
        // it means we have a circular reference
        if ($count > 0) {
            $msg = 'Classes "%s" have produced a CircularReferenceException. ';
            $msg .= 'An example of this problem would be the following: Class C has class B as its dependency. ';
            $msg .= 'Then, class B has class A has its dependency. Finally, class A has class C as its dependency. ';
            $msg .= 'This case would produce a CircularReferenceException.';

            throw new CircularReferenceException(sprintf($msg, implode(',', $unsequencedClasses)));
        }

        // We order the classes by sequence
        asort($sequenceForClasses);
        foreach ($sequenceForClasses as $class => $sequence) {
            // If fixtures were ordered
            $orderedFixtures[] = $this->fixtures[$class];
        }

        $this->orderedFixtures = array_merge($this->orderedFixtures, $orderedFixtures);
    }

    /**
     * @param array $dependenciesClasses
     *
     * @return bool
     */
    private function validateDependencies($dependenciesClasses)
    {
        $loadedFixtureClasses = array_keys($this->fixtures);

        foreach ($dependenciesClasses as $class) {
            if (!in_array($class, $loadedFixtureClasses)) {
                throw new \RuntimeException(sprintf('Fixture "%s" was declared as a dependency, but it should be added in fixture loader first.', $class));
            }
        }

        return true;
    }

    /**
     * @param array      $sequences
     * @param null|array $classes
     *
     * @return array
     */
    private function getUnsequencedClasses(array $sequences, $classes = null)
    {
        $unsequencedClasses = array();

        if (is_null($classes)) {
            $classes = array_keys($sequences);
        }

        foreach ($classes as $class) {
            if ($sequences[$class] === -1) {
                $unsequencedClasses[] = $class;
            }
        }

        return $unsequencedClasses;
    }

    /**
     * Load fixtures from files contained in iterator.
     *
     * @param \Iterator $iterator Iterator over files from which fixtures should be loaded.
     *
     * @return FixtureInterface[] $fixtures Array of loaded fixture object instances.
     */
    private function loadFromIterator(\Iterator $iterator)
    {
        $includedFiles = [];

        foreach ($iterator as $file) {
            if (($fileName = $file->getBasename($this->fileExtension)) == $file->getBasename()) {
                continue;
            }

            $sourceFile = realpath($file->getPathName());
            require_once $sourceFile;
            $includedFiles[] = $sourceFile;
        }

        $fixtures = [];
        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $reflClass = new \ReflectionClass($className);
            $sourceFile = $reflClass->getFileName();

            if (in_array($sourceFile, $includedFiles) && !$this->isTransient($className)) {
                $fixture = new $className();
                $fixtures[] = $fixture;
                $this->addFixture($fixture);
            }
        }

        return $fixtures;
    }
}
