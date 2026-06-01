<?php

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ocsinventoryng\OcsProcess;

class OcsProcessImportTest extends DbTestCase
{
    public function testOcsProcessStatusConstantsAreDefined(): void
    {
        $this->assertSame(0, OcsProcess::COMPUTER_IMPORTED);
        $this->assertSame(1, OcsProcess::COMPUTER_SYNCHRONIZED);
        $this->assertSame(2, OcsProcess::COMPUTER_LINKED);
        $this->assertSame(3, OcsProcess::COMPUTER_FAILED_IMPORT);
        $this->assertSame(4, OcsProcess::COMPUTER_NOTUPDATED);
        $this->assertSame(5, OcsProcess::COMPUTER_NOT_UNIQUE);
        $this->assertSame(6, OcsProcess::COMPUTER_LINK_REFUSED);
    }

    public function testGetAvailableStatisticsReturnsSevenNamedCounters(): void
    {
        $this->login('glpi', 'glpi');

        $stats = OcsProcess::getAvailableStatistics();

        $this->assertCount(7, $stats);

        $expected = [
            'imported_machines_number',
            'synchronized_machines_number',
            'linked_machines_number',
            'notupdated_machines_number',
            'failed_rules_machines_number',
            'not_unique_machines_number',
            'link_refused_machines_number',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $stats);
        }
    }

    public function testManageImportStatisticsInitializesAllCountersWhenCalledWithNoAction(): void
    {
        $this->login('glpi', 'glpi');

        $stats = [];
        OcsProcess::manageImportStatistics($stats);

        $this->assertCount(7, $stats);
    }

    public function testManageImportStatisticsWithImportedStatusIncrementsImportedCounter(): void
    {
        $this->login('glpi', 'glpi');

        $stats = [];
        OcsProcess::manageImportStatistics($stats, OcsProcess::COMPUTER_IMPORTED);

        $this->assertSame(1, $stats['imported_machines_number']);
    }

    public function testManageImportStatisticsWithLinkedStatusIncrementsLinkedCounter(): void
    {
        $this->login('glpi', 'glpi');

        $stats = [];
        OcsProcess::manageImportStatistics($stats, OcsProcess::COMPUTER_LINKED);

        $this->assertSame(1, $stats['linked_machines_number']);
    }
}
