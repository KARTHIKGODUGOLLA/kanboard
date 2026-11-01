<?php

namespace Kanboard\Plugin\SchemaHandlerTest\Schema;

use PDO;

const VERSION = 2;

function version_1(PDO $pdo)
{
    $pdo->exec('CREATE TABLE plugin_migration_test (version INTEGER)');
    $pdo->exec('INSERT INTO plugin_migration_test (version) VALUES (1)');
}

function version_2(PDO $pdo)
{
    $pdo->exec('INSERT INTO plugin_migration_test (version) VALUES (2)');
}
