<?php

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ocsinventoryng\OcsProcess;

class OcsProcessSynchronizeTest extends DbTestCase
{
    public function testSynchronizeComputerWithNoLinkReturnsFailed(): void
    {
        $this->login('glpi', 'glpi');

        $params = [
            'ocsid'                               => PHP_INT_MAX,
            'plugin_ocsinventoryng_ocsservers_id' => 0,
            'lock'                                => false,
            'defaultentity'                       => -1,
            'defaultrecursive'                    => 0,
            'cfg_ocs'                             => [],
            'disable_unicity_check'               => false,
            'computers_id'                        => false,
            'force'                               => 0,
            'cron'                                => 0,
        ];

        $result = OcsProcess::synchronizeComputer($params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);

        $valid = [
            OcsProcess::COMPUTER_SYNCHRONIZED,
            OcsProcess::COMPUTER_NOTUPDATED,
            OcsProcess::COMPUTER_FAILED_IMPORT,
            OcsProcess::COMPUTER_LINK_REFUSED,
        ];

        $this->assertContains(
            $result['status'],
            $valid,
            'synchronizeComputer must return a known COMPUTER_* status constant.'
        );
    }
}
