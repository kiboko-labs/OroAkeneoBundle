<?php

namespace Oro\Bundle\AkeneoBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAkeneoMigration implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('akeneo_disable_extension_check', 'boolean', ['notnull' => false, 'default' => true]);
        $table->addColumn('akeneo_media_order_code', 'text', ['notnull' => false]);
    }
}
