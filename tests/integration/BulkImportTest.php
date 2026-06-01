<?php

/**
 * Integration and bulk-unit tests for OcsProcess import statistics and bulk processing.
 *
 * Tests 1, 2, 3, and 5 are pure unit tests that only exercise the static
 * manageImportStatistics() method and do not touch the database.
 *
 * Test 4 requires a full GLPI environment and is skipped when GLPI_ROOT is absent.
 */

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use GlpiPlugin\Ocsinventoryng\OcsProcess;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BulkImportTest extends TestCase
{
    // Guard: skip every test in this class if OcsProcess cannot be autoloaded.
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(OcsProcess::class)) {
            self::markTestSkipped(
                'OcsProcess class is not available. '
                . 'Set GLPI_ROOT to a valid GLPI installation and re-run.'
            );
        }
    }

    // =========================================================================
    // Test 1 — 100 consecutive COMPUTER_IMPORTED calls
    // =========================================================================

    #[Test]
    public function testManageStatisticsFor100Imports(): void
    {
        $statistics = [];

        for ($i = 0; $i < 100; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_IMPORTED);
        }

        $this->assertSame(100, $statistics['imported_machines_number']);
        $this->assertSame(0, $statistics['synchronized_machines_number']);
        $this->assertSame(0, $statistics['linked_machines_number']);
        $this->assertSame(0, $statistics['notupdated_machines_number']);
        $this->assertSame(0, $statistics['failed_rules_machines_number']);
        $this->assertSame(0, $statistics['not_unique_machines_number']);
        $this->assertSame(0, $statistics['link_refused_machines_number']);
    }

    // =========================================================================
    // Test 2 — mixed batch: 50 imported + 30 synchronized + 20 failed
    // =========================================================================

    #[Test]
    public function testManageStatisticsForMixedBatch(): void
    {
        $statistics = [];

        for ($i = 0; $i < 50; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_IMPORTED);
        }

        for ($i = 0; $i < 30; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_SYNCHRONIZED);
        }

        for ($i = 0; $i < 20; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_FAILED_IMPORT);
        }

        $this->assertSame(50, $statistics['imported_machines_number']);
        $this->assertSame(30, $statistics['synchronized_machines_number']);
        $this->assertSame(20, $statistics['failed_rules_machines_number']);
        $this->assertSame(0, $statistics['linked_machines_number']);
        $this->assertSame(0, $statistics['notupdated_machines_number']);
        $this->assertSame(0, $statistics['not_unique_machines_number']);
        $this->assertSame(0, $statistics['link_refused_machines_number']);
    }

    // =========================================================================
    // Test 3 — SNMP batch: 25 imported + 15 synchronized + 10 failed
    // =========================================================================

    #[Test]
    public function testManageStatisticsForSnmpBatch(): void
    {
        $statistics = [];

        // Pass snmp=true so the statistics array is initialised with SNMP keys.
        for ($i = 0; $i < 25; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::SNMP_IMPORTED, true);
        }

        for ($i = 0; $i < 15; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::SNMP_SYNCHRONIZED, true);
        }

        for ($i = 0; $i < 10; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::SNMP_FAILED_IMPORT, true);
        }

        $this->assertSame(25, $statistics['imported_snmp_number']);
        $this->assertSame(15, $statistics['synchronized_snmp_number']);
        $this->assertSame(10, $statistics['failed_imported_snmp_number']);
        $this->assertSame(0, $statistics['linked_snmp_number']);
        $this->assertSame(0, $statistics['notupdated_snmp_number']);
    }

    // =========================================================================
    // Test 4 — processComputer returns status codes in the valid 0-6 range
    //           (integration test: requires GLPI_ROOT)
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function testBulkProcessReturnsCodes(): void
    {
        if (!defined('GLPI_ROOT') || !is_dir(GLPI_ROOT)) {
            $this->markTestSkipped('GLPI_ROOT is not defined or does not point to a valid directory.');
        }

        $valid_statuses = range(0, 6);
        $collected_statuses = [];

        // Attempt to process 5 computers with different OCS IDs.
        // In a real integration environment each call returns a status code.
        for ($ocsid = 1; $ocsid <= 5; $ocsid++) {
            $process_params = [
                'ocsid'                               => $ocsid,
                'plugin_ocsinventoryng_ocsservers_id' => 1,
                'lock'                                => false,
                'defaultentity'                       => 0,
                'defaultrecursive'                    => 0,
                'disable_unicity_check'               => false,
                'computers_id'                        => false,
                'force'                               => 0,
                'cron'                                => 1,
            ];

            $result = OcsProcess::processComputer($process_params);

            // processComputer() always returns an array with a 'status' key.
            $this->assertIsArray($result, "processComputer must return an array for ocsid $ocsid");
            $this->assertArrayHasKey('status', $result, "Result must contain a 'status' key for ocsid $ocsid");

            $collected_statuses[] = $result['status'];
        }

        // Every returned status must be a recognised computer-level code (0–6).
        foreach ($collected_statuses as $index => $status) {
            $this->assertContains(
                $status,
                $valid_statuses,
                "Status $status at position $index is not in the valid range 0-6"
            );
        }
    }

    // =========================================================================
    // Test 5 — sum of all counters equals the total number of processed items
    // =========================================================================

    #[Test]
    #[Group('bulk')]
    public function testStatisticsSumEqualsProcessedCount(): void
    {
        // Build a random-but-deterministic mix of 200 COMPUTER_* actions.
        $actions = [
            OcsProcess::COMPUTER_IMPORTED,
            OcsProcess::COMPUTER_SYNCHRONIZED,
            OcsProcess::COMPUTER_LINKED,
            OcsProcess::COMPUTER_FAILED_IMPORT,
            OcsProcess::COMPUTER_NOTUPDATED,
            OcsProcess::COMPUTER_NOT_UNIQUE,
            OcsProcess::COMPUTER_LINK_REFUSED,
        ];

        $total_calls = 200;
        $statistics  = [];

        // Use a seeded sequence so the test is deterministic across runs.
        mt_srand(42);
        for ($i = 0; $i < $total_calls; $i++) {
            $action = $actions[mt_rand(0, count($actions) - 1)];
            OcsProcess::manageImportStatistics($statistics, $action);
        }

        $counter_sum = array_sum($statistics);

        $this->assertSame(
            $total_calls,
            $counter_sum,
            "The sum of all counters ($counter_sum) must equal the number of processed items ($total_calls)."
        );
    }
}
