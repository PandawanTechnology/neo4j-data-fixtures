<?php

namespace PandawanTechnology\Neo4jDataFixtures\Exception;

use GraphAware\Neo4j\Client\Exception\Neo4jException;

class CircularReferenceException extends Neo4jException
{
}
