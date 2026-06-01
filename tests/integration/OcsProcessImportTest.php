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

    public function testImportComputerReturnsArrayWithStatusKey(): void
    {
        $this->login('glpi', 'glpi');

        $import_params = [
            'ocsid'                               => PHP_INT_MAX,
            'plugin_ocsinventoryng_ocsservers_id' => 0,
            'lock'                                => false,
            'defaultentity'                       => -1,
            'defaultrecursive'                    => 0,
            'cfg_ocs'                             => [],
            'disable_unicity_check'               => false,
            'computers_id'                        => false,
            'cron'                                => 0,
        ];

        $result = OcsProcess::importComputer($import_params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);

        $valid_statuses = [
            OcsProcess::COMPUTER_IMPORTED,
            OcsProcess::COMPUTER_SYNCHRONIZED,
            OcsProcess::COMPUTER_LINKED,
            OcsProcess::COMPUTER_FAILED_IMPORT,
            OcsProcess::COMPUTER_NOTUPDATED,
            OcsProcess::COMPUTER_NOT_UNIQUE,
            OcsProcess::COMPUTER_LINK_REFUSED,
        ];

        $this->assertContains(
            $result['status'],
            $valid_statuses,
            'importComputer must return a known COMPUTER_* status constant.'
        );
    }

    public function testProcessComputerReturnsArrayWithStatusKey(): void
    {
        $this->login('glpi', 'glpi');

        $process_params = [
            'ocsid'                               => PHP_INT_MAX,
            'plugin_ocsinventoryng_ocsservers_id' => 0,
            'lock'                                => false,
            'defaultentity'                       => -1,
            'defaultrecursive'                    => 0,
            'disable_unicity_check'               => false,
            'computers_id'                        => false,
            'force'                               => 0,
            'cron'                                => 0,
        ];

        $result = OcsProcess::processComputer($process_params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
}
