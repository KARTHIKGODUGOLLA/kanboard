<?php

namespace KanboardTests\units\Core\Plugin;

use KanboardTests\units\Base;
use Kanboard\Core\Plugin\SchemaHandler;

require_once __DIR__.'/../../../fixtures/plugin_schema.php';

class SchemaHandlerTest extends Base
{
    public function testMigrateSchemaRunsAllPendingVersions()
    {
        $p = new SchemaHandler($this->container);
        $p->migrateSchema('SchemaHandlerTest');

        $this->assertSame(2, $p->getSchemaVersion('SchemaHandlerTest'));
        $this->assertEquals(array(1, 2), $this->container['db']->getConnection()
            ->query('SELECT version FROM plugin_migration_test ORDER BY version')
            ->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function testMigrateSchemaRunsOnlyNewVersions()
    {
        $p = new SchemaHandler($this->container);
        $this->assertTrue($p->setSchemaVersion('SchemaHandlerTest', 1));
        \Kanboard\Plugin\SchemaHandlerTest\Schema\version_1($this->container['db']->getConnection());

        $p->migrateSchema('SchemaHandlerTest');
        $p->migrateSchema('SchemaHandlerTest');

        $this->assertSame(2, $p->getSchemaVersion('SchemaHandlerTest'));
        $this->assertEquals(array(1, 2), $this->container['db']->getConnection()
            ->query('SELECT version FROM plugin_migration_test ORDER BY version')
            ->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function testCurrentSchemaDoesNotCommitAnExistingTransaction()
    {
        $p = new SchemaHandler($this->container);
        $this->assertTrue($p->setSchemaVersion('SchemaHandlerTest', 2));
        $connection = $this->container['db']->getConnection();
        $connection->beginTransaction();

        try {
            $p->migrateSchema('SchemaHandlerTest');
            $this->assertTrue($connection->inTransaction());
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }
    }

    public function testNewerSchemaIsNotDowngraded()
    {
        $p = new SchemaHandler($this->container);
        $this->assertTrue($p->setSchemaVersion('SchemaHandlerTest', 3));
        $connection = $this->container['db']->getConnection();
        $connection->beginTransaction();

        try {
            $p->migrateSchema('SchemaHandlerTest');
            $this->assertTrue($connection->inTransaction());
            $this->assertSame(3, $p->getSchemaVersion('SchemaHandlerTest'));
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }
    }

    public function testGetSchemaVersion()
    {
        $p = new SchemaHandler($this->container);
        $this->assertEquals(0, $p->getSchemaVersion('not_found'));

        $this->assertTrue($p->setSchemaVersion('plugin1', 1));
        $this->assertEquals(1, $p->getSchemaVersion('plugin1'));

        $this->assertTrue($p->setSchemaVersion('plugin2', 33));
        $this->assertEquals(33, $p->getSchemaVersion('plugin2'));

        $this->assertTrue($p->setSchemaVersion('plugin1', 2));
        $this->assertEquals(2, $p->getSchemaVersion('plugin1'));
    }
}
