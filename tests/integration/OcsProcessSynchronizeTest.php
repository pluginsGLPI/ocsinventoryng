<?php

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ocsinventoryng\OcsProcess;

class OcsProcessSynchronizeTest extends DbTestCase
{
    public function testSynchronizeStatusConstantsAreIntegers(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertIsInt(OcsProcess::COMPUTER_SYNCHRONIZED);
        $this->assertIsInt(OcsProcess::COMPUTER_NOTUPDATED);
        $this->assertIsInt(OcsProcess::COMPUTER_FAILED_IMPORT);
        $this->assertIsInt(OcsProcess::COMPUTER_LINK_REFUSED);
    }

    public function testManageImportStatisticsWithSynchronizedStatusIncrementsCorrectCounter(): void
    {
        $this->login('glpi', 'glpi');

        $stats = [];
        OcsProcess::manageImportStatistics($stats, OcsProcess::COMPUTER_SYNCHRONIZED);

        $this->assertSame(1, $stats['synchronized_machines_number']);
        $this->assertSame(0, $stats['imported_machines_number']);
    }

    public function testManageImportStatisticsWithNotUpdatedStatusIncrementsCorrectCounter(): void
    {
        $this->login('glpi', 'glpi');

        $stats = [];
        OcsProcess::manageImportStatistics($stats, OcsProcess::COMPUTER_NOTUPDATED);

        $this->assertSame(1, $stats['notupdated_machines_number']);
        $this->assertSame(0, $stats['synchronized_machines_number']);
    }
}
