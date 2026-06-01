<?php

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ocsinventoryng\OcsProcess;

class BulkImportTest extends DbTestCase
{
    public function testGetAvailableStatisticsReturnsArray(): void
    {
        $stats = OcsProcess::getAvailableStatistics();

        $this->assertIsArray($stats);
        $this->assertNotEmpty($stats);
    }

    public function testManageImportStatisticsAccumulatesResults(): void
    {
        $result = [];

        for ($i = 0; $i < 10; $i++) {
            OcsProcess::manageImportStatistics($result, OcsProcess::COMPUTER_IMPORTED);
        }
        for ($i = 0; $i < 5; $i++) {
            OcsProcess::manageImportStatistics($result, OcsProcess::COMPUTER_SYNCHRONIZED);
        }
        for ($i = 0; $i < 3; $i++) {
            OcsProcess::manageImportStatistics($result, OcsProcess::COMPUTER_FAILED_IMPORT);
        }

        $this->assertSame(10, $result['imported_machines_number'] ?? 0);
        $this->assertSame(5, $result['synchronized_machines_number'] ?? 0);
        $this->assertSame(3, $result['failed_rules_machines_number'] ?? 0);
    }

    public function testBulkImportWithInvalidServerReturnsFailedStatusForEachAttempt(): void
    {
        $this->login('glpi', 'glpi');

        $stats = [];
        $count = 5;

        for ($i = 0; $i < $count; $i++) {
            $result = OcsProcess::importComputer([
                'ocsid'                               => PHP_INT_MAX - $i,
                'plugin_ocsinventoryng_ocsservers_id' => 0,
                'lock'                                => false,
                'defaultentity'                       => -1,
                'defaultrecursive'                    => 0,
                'cfg_ocs'                             => [],
                'disable_unicity_check'               => false,
                'computers_id'                        => false,
                'cron'                                => 0,
            ]);

            if (isset($result['status'])) {
                OcsProcess::manageImportStatistics($stats, $result['status']);
            }
        }

        $total = array_sum($stats);
        $this->assertSame($count, $total, 'Every import attempt must produce exactly one counted status.');
        $this->assertSame($count, $stats['failed_rules_machines_number'] ?? 0);
    }
}
