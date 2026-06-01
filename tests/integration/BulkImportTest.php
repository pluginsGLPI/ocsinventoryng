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

    public function testAllStatusConstantsProduceExactlyOneCounterEntry(): void
    {
        $this->login('glpi', 'glpi');

        $statuses = [
            OcsProcess::COMPUTER_IMPORTED,
            OcsProcess::COMPUTER_SYNCHRONIZED,
            OcsProcess::COMPUTER_LINKED,
            OcsProcess::COMPUTER_FAILED_IMPORT,
            OcsProcess::COMPUTER_NOTUPDATED,
            OcsProcess::COMPUTER_NOT_UNIQUE,
            OcsProcess::COMPUTER_LINK_REFUSED,
        ];

        foreach ($statuses as $status) {
            $stats = [];
            OcsProcess::manageImportStatistics($stats, $status);
            $total = array_sum($stats);
            $this->assertSame(1, $total, "Status $status must increment exactly one counter.");
        }
    }
}
