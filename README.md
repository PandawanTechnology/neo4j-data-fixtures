# Pandawan Technology Neo4j data fixtures
This library will allow you to load fixtures data into Neo4j graph database.
It has been inspired by [Doctrine Data Fixtures Extension](https://github.com/doctrine/data-fixtures).

## Simple usage
All you have to do is to implement the `FixtureInterface` provided by this library:
```php
<?php

namespace MyNamespace\Fixtures;

use GraphAware\Common\Connection\ConnectionInterface;
use PandawanTechnology\Neo4jDataFixtures\FixtureInterface;

class UserFixture implements FixtureInterface
{
    public function load(ConnectionInterface $connection)
    {
        $connection->session->run("CREATE (a:Person {name:'Arthur', title:'King'})");
    }
} 
```

Now you have to register this fixture into the loader:
```php
<?php

namespace MyNamespace\Fixtures;

use PandawanTechnology\Neo4jDataFixtures\Loader;

$loader = new Loader();
$loader->addFixture(new UserFixture());
```

You can also load fixtures from a directory :
```php
$loader->loadFromDirectory(__DIR__);
```

Or specify a file:
```php
$loader->loadFromFile('./UserFixture.php');
```

Finally, you can get your fixtures:
```php
$fixtures = $loader->getFixtures();
```

You can now run the fixtures loading :
```php
use PandawanTechnology\Neo4jDataFixtures\Executor;

$executor = new Executor($connection);
$executor->execute($loader->getFixtures());
```

## Add dependencies between you fixtures
You can add dependencies within your fixtures using the `DependentFixtureInterface`:
```php
<?php

namespace MyNamespace\Fixtures;

use GraphAware\Common\Connection\ConnectionInterface;
use PandawanTechnology\Neo4jDataFixtures\DependentFixtureInterface;
use PandawanTechnology\Neo4jDataFixtures\FixtureInterface;

class UserFixture implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return ['MyNamespace\Fixtures\OrganizationFixture'];
    }

    public function load(ConnectionInterface $connection)
    {
        $connection->session->run("CREATE (a:Person {name:'Arthur', title:'King'})");
    }
}
 
class OrganizationFixture implements FixtureInterface
{
    public function load(ConnectionInterface $connection)
    {
        // ...
    }
}
```
