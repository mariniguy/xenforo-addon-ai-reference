<?php

namespace Demo\Portal;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    // -------------------------------------------------------------------------
    // Install steps
    // -------------------------------------------------------------------------

    public function installStep1(): void
    {
        $this->schemaManager()->createTable('xf_demo_portal_featured_thread', function (Create $table) {
            $table->addColumn('thread_id', 'int')->unsigned();
            $table->addColumn('featured_date', 'int')->unsigned();
            $table->addColumn('featured_user_id', 'int')->unsigned();
            $table->addPrimaryKey('thread_id');
        });
    }

    public function installStep2(): void
    {
        $this->schemaManager()->alterTable('xf_forum', function (Alter $table) {
            $table->addColumn('demo_portal_auto_feature', 'tinyint')->unsigned()->setDefault(0);
        });
    }

    // -------------------------------------------------------------------------
    // Uninstall steps
    // -------------------------------------------------------------------------

    public function uninstallStep1(): void
    {
        $this->schemaManager()->dropTable('xf_demo_portal_featured_thread');
    }

    public function uninstallStep2(): void
    {
        $this->schemaManager()->alterTable('xf_forum', function (Alter $table) {
            $table->dropColumns(['demo_portal_auto_feature']);
        });
    }
}
