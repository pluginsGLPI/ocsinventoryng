<?php

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ocsinventoryng\OcsProcess;

class OcsProcessSynchronizeTest extends DbTestCase
{
    public function testSynchronizeComputerWithNonExistentLinkReturnsFailedImport(): void
    {
        $this->login('glpi', 'glpi');

        $result = OcsProcess::synchronizeComputer([
            'ID'                                  => PHP_INT_MAX,
            'plugin_ocsinventoryng_ocsservers_id' => 0,
            'cfg_ocs'                             => [],
            'force'                               => 0,
            'cron'                                => 0,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertSame(OcsProcess::COMPUTER_FAILED_IMPORT, $result['status']);
    }
}
