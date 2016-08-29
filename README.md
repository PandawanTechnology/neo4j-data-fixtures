# Pandawan Technology Neo4j data fixtures
This library will allow you to load fixtures data into Neo4j graph database.
It has been inspired by [Doctrine Data Fixtures Extension](https://github.com/doctrine/data-fixtures).

## Simple usage
All you have to do is to extend the `AbstractNeo4jFixture` class provided by this library:
```php
<?php

namespace MyNamespace\Fixtures;

use GraphAware\Common\Connection\ConnectionInterface;
use PandawanTechnology\Neo4jDataFixtures\AbstractNeo4jFixture;

class UserFixture extends AbstractNeo4jFixture
{
    public function load(ConnectionInterface $connection)
    {
        $connection->getSession()->run("CREATE (a:Person {name:'Arthur', title:'King'})");
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

If you want to purge your database each time you load your fixtures, you will have to use the `Purger` class:
```php
use PandawanTechnology\Neo4jDataFixtures\Executor;
use PandawanTechnology\Neo4jDataFixtures\Purger;

$purger = new Purger($connection);
$executor = new Executor($connection, $purger);
$executor->execute($loader->getFixtures());
```
The `Executor::execute()` method will take a second argument to ignore database deletion. Default behavior is to delete data, as long as a `Purger` instance is provided.

## Add dependencies between your fixtures files
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
        $connection->getSession()->run("CREATE (a:Person {name:'Arthur', title:'King'})");
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

## Share objects between your fixtures files
If you use `DependentFixtureInterface`, there is good chance that you will need to share objects across your fixtures files. To do so, you can simply use the `setReference`/`addReference` methods to assign ... a reference (!) — the latest will ensure uniqueness — and fetch it back using the `getReference` method:
```php
<?php

namespace MyNamespace\Fixtures;

use GraphAware\Common\Connection\ConnectionInterface;
use PandawanTechnology\Neo4jDataFixtures\DependentFixtureInterface;
use PandawanTechnology\Neo4jDataFixtures\FixtureInterface;
 
class OrganizationFixture implements FixtureInterface
{
    public function load(ConnectionInterface $connection)
    {
        if (!$organizationStmt = $connection->getSession()->run("CREATE (s:Organization {name:'Pandawan Technology'}) RETURN id(o)")) {
            continue;
        }
        
        $this->addReference('organization-pandawan-technology', $organizationStmt->getRecord()->value('id(o)'));
    }
}

class UserFixture implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return ['MyNamespace\Fixtures\OrganizationFixture'];
    }

    public function load(ConnectionInterface $connection)
    {
        $session = $connection->getSession();

        if (!$userStmt = $session->run("CREATE (a:Person {name:'Arthur', title:'King'}) RETURN id(a)")) {
            return;
        }

        $session->rund('MATCH (u:User), (o:Organization) WHERE id(u) = {user_id} AND id(o) = {organization_id} CREATE (u)-[r:BELONGS_TO]->(o) RETURN r', [
            'organisation_id' => $this->getReference('organization-pandawan-technology'),
            'user_id' => $userStmt->getRecord()->value('id(a)')
        ]);
    }
}
```
