<?php

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use Alert;
use CronTask;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ocsinventoryng\Notimportedcomputer;

class NotimportedcomputerTest extends DbTestCase
{
    public function testCronSendAlertsReturnsAnInteger(): void
    {
        global $CFG_GLPI;

        $this->login();
        $CFG_GLPI['use_notifications']     = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $notimported = $this->createItem(Notimportedcomputer::class, [
            'entities_id'                         => $this->getTestRootEntity(true),
            'ocsid'                               => 1,
            'plugin_ocsinventoryng_ocsservers_id' => 0,
            'ocs_deviceid'                        => 'device-' . $this->getUniqueString(),
            'useragent'                           => '',
            'tag'                                 => '',
            'serial'                              => '',
            'name'                                => 'Not imported ' . $this->getUniqueString(),
            'ipaddr'                              => '',
            'domain'                              => '',
            'reason'                              => 0,
        ]);

        $task = new CronTask();
        $this->assertTrue($task->getFromDBbyName(Notimportedcomputer::class, 'SendAlerts'));

        // A null return was counted as an error by CronTask::end() and disabled the task
        $this->assertSame(1, Notimportedcomputer::cronSendAlerts($task));
        $this->assertSame(1, countElementsInTable(Alert::getTable(), [
            'itemtype' => Notimportedcomputer::class,
            'items_id' => $notimported->getID(),
            'type'     => Alert::END,
        ]));

        // Already alerted: nothing left to do
        $this->assertSame(0, Notimportedcomputer::cronSendAlerts($task));

        $CFG_GLPI['notifications_mailing'] = 0;
        $this->assertSame(0, Notimportedcomputer::cronSendAlerts($task));
    }
}
