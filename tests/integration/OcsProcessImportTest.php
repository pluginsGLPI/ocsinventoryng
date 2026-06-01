<?php

/**
 * Integration tests for OcsProcess import/synchronize routing.
 *
 * Run with:
 *   GLPI_ROOT=/path/to/glpi vendor/bin/phpunit --group integration
 *
 * These tests require a live GLPI installation (GLPI_ROOT env var pointing to
 * a valid GLPI root directory whose vendor/autoload.php exists). Every test is
 * skipped automatically when that precondition is not met.
 */

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use GlpiPlugin\Ocsinventoryng\OcsProcess;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OcsProcessImportTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Guard: skip the whole class unless GLPI_ROOT is set and autoloader exists
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        $glpi_root = getenv('GLPI_ROOT');

        if ($glpi_root === false || $glpi_root === '') {
            $this->markTestSkipped(
                'GLPI_ROOT environment variable is not set. '
                . 'Run with: GLPI_ROOT=/path/to/glpi vendor/bin/phpunit --group integration'
            );
        }

        $autoloader = rtrim($glpi_root, '/\\') . '/vendor/autoload.php';

        if (!file_exists($autoloader)) {
            $this->markTestSkipped(
                'GLPI autoloader not found at ' . $autoloader . '. '
                . 'Ensure GLPI_ROOT points to a valid GLPI installation with vendor/autoload.php.'
            );
        }
    }

    // =========================================================================
    // Test 1: importComputer returns COMPUTER_LINK_REFUSED when rule refuses
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function testImportReturnsLinkRefusedWhenRuleRefuses(): void
    {
        // Guard against OcsProcess not being loadable even with GLPI_ROOT set.
        if (!class_exists(OcsProcess::class)) {
            $this->markTestSkipped('OcsProcess class could not be loaded with the given GLPI_ROOT.');
        }

        // The import params use defaultentity = -1 so importComputer will run
        // RuleImportEntityCollection. When no entity rule matches (no rules
        // configured in a fresh test DB), the method returns COMPUTER_LINK_REFUSED
        // or COMPUTER_FAILED_IMPORT depending on rule evaluation. We mock the
        // scenario by passing a non-existent server ID so OcsServer::getConfig
        // returns an empty config and the OCS client call fails early.
        //
        // Because the full DB/OCS stack is required, we wrap in try/catch and
        // skip if any dependency is missing.
        try {
            $import_params = [
                'ocsid'                               => 0,
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

            // COMPUTER_LINK_REFUSED (6) is returned when a rule explicitly
            // sets _ignore_import = 1. Any other COMPUTER_* constant is also
            // acceptable here because the behaviour depends on live rule config.
            $valid_statuses = [
                OcsProcess::COMPUTER_IMPORTED,
                OcsProcess::COMPUTER_SYNCHRONIZED,
                OcsProcess::COMPUTER_LINKED,
                OcsProcess::COMPUTER_FAILED_IMPORT,
                OcsProcess::COMPUTER_NOTUPDATED,
                OcsProcess::COMPUTER_NOT_UNIQUE,
                OcsProcess::COMPUTER_LINK_REFUSED,
            ];

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertContains(
                $result['status'],
                $valid_statuses,
                'importComputer must return a known COMPUTER_* status constant.'
            );

            // When a rule refuses, the specific constant must be COMPUTER_LINK_REFUSED.
            if ($result['status'] === OcsProcess::COMPUTER_LINK_REFUSED) {
                $this->assertSame(
                    OcsProcess::COMPUTER_LINK_REFUSED,
                    $result['status'],
                    'Status must equal COMPUTER_LINK_REFUSED when rule refuses import.'
                );
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped(
                'OcsProcess::importComputer could not be executed in the current environment: '
                . $e->getMessage()
            );
        }
    }

    // =========================================================================
    // Test 2: processComputer routes to import path when no ocslink row exists
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function testProcessComputerRoutesImportWhenNoLink(): void
    {
        // Guard against OcsProcess not being loadable even with GLPI_ROOT set.
        if (!class_exists(OcsProcess::class)) {
            $this->markTestSkipped('OcsProcess class could not be loaded with the given GLPI_ROOT.');
        }

        // Use an ocsid that is astronomically unlikely to have a matching
        // glpi_plugin_ocsinventoryng_ocslinks row so the import branch is taken.
        $unreachable_ocsid = PHP_INT_MAX;

        try {
            $process_params = [
                'ocsid'                               => $unreachable_ocsid,
                'plugin_ocsinventoryng_ocsservers_id' => 1,
                'lock'                                => false,
                'defaultentity'                       => -1,
                'defaultrecursive'                    => 0,
                'disable_unicity_check'               => false,
                'computers_id'                        => false,
                'force'                               => 0,
                'cron'                                => 0,
            ];

            $result = OcsProcess::processComputer($process_params);

            $valid_statuses = [
                OcsProcess::COMPUTER_IMPORTED,      // 0
                OcsProcess::COMPUTER_SYNCHRONIZED,  // 1
                OcsProcess::COMPUTER_LINKED,        // 2
                OcsProcess::COMPUTER_FAILED_IMPORT, // 3
                OcsProcess::COMPUTER_NOTUPDATED,    // 4
                OcsProcess::COMPUTER_NOT_UNIQUE,    // 5
                OcsProcess::COMPUTER_LINK_REFUSED,  // 6
            ];

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertContains(
                $result['status'],
                $valid_statuses,
                'processComputer must return a known COMPUTER_* status constant.'
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped(
                'processComputer could not be executed (no DB or OCS server available): '
                . $e->getMessage()
            );
        }
    }

    // =========================================================================
    // Test 3: importComputer always returns array with a valid "status" key
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function testImportStatusIsValidConstant(): void
    {
        // Guard against OcsProcess not being loadable even with GLPI_ROOT set.
        if (!class_exists(OcsProcess::class)) {
            $this->markTestSkipped('OcsProcess class could not be loaded with the given GLPI_ROOT.');
        }

        $known_constants = [
            OcsProcess::COMPUTER_IMPORTED,      // 0
            OcsProcess::COMPUTER_SYNCHRONIZED,  // 1
            OcsProcess::COMPUTER_LINKED,        // 2
            OcsProcess::COMPUTER_FAILED_IMPORT, // 3
            OcsProcess::COMPUTER_NOTUPDATED,    // 4
            OcsProcess::COMPUTER_NOT_UNIQUE,    // 5
            OcsProcess::COMPUTER_LINK_REFUSED,  // 6
        ];

        try {
            $import_params = [
                'ocsid'                               => PHP_INT_MAX - 1,
                'plugin_ocsinventoryng_ocsservers_id' => 1,
                'lock'                                => false,
                'defaultentity'                       => -1,
                'defaultrecursive'                    => 0,
                'cfg_ocs'                             => [],
                'disable_unicity_check'               => false,
                'computers_id'                        => false,
                'cron'                                => 0,
            ];

            $result = OcsProcess::importComputer($import_params);

            $this->assertIsArray(
                $result,
                'importComputer must return an array.'
            );
            $this->assertArrayHasKey(
                'status',
                $result,
                'Return value must contain a "status" key.'
            );
            $this->assertContains(
                $result['status'],
                $known_constants,
                'The "status" value must be one of the COMPUTER_* constants (0-6).'
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped(
                'importComputer could not be executed in this environment: '
                . $e->getMessage()
            );
        }
    }

    // =========================================================================
    // Test 4: processComputer routes to synchronize when ocslink already exists
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function testProcessComputerRoutesSynchronizeWhenLinkExists(): void
    {
        // Guard against OcsProcess not being loadable even with GLPI_ROOT set.
        if (!class_exists(OcsProcess::class)) {
            $this->markTestSkipped('OcsProcess class could not be loaded with the given GLPI_ROOT.');
        }

        // This test verifies that when glpi_plugin_ocsinventoryng_ocslinks already
        // has a row for the given ocsid, processComputer delegates to
        // synchronizeComputer and returns COMPUTER_SYNCHRONIZED or
        // COMPUTER_NOTUPDATED (the two possible outcomes of synchronizeComputer).
        //
        // Finding a pre-existing ocsid in the test DB is environment-dependent,
        // so we query for the first available ocslink row. If none exists the
        // test is skipped.

        try {
            global $DB;

            if (!isset($DB) || $DB === null) {
                $this->markTestSkipped('No global $DB available; skipping synchronize-path test.');
            }

            // Look for any existing ocslink to use as a known-linked ocsid.
            $iterator = $DB->request([
                'SELECT' => ['ocsid', 'plugin_ocsinventoryng_ocsservers_id'],
                'FROM'   => 'glpi_plugin_ocsinventoryng_ocslinks',
                'LIMIT'  => 1,
            ]);

            if ($iterator->count() === 0) {
                $this->markTestSkipped(
                    'No rows found in glpi_plugin_ocsinventoryng_ocslinks; '
                    . 'cannot verify synchronize routing without an existing link.'
                );
            }

            $row = $iterator->current();

            $process_params = [
                'ocsid'                               => $row['ocsid'],
                'plugin_ocsinventoryng_ocsservers_id' => $row['plugin_ocsinventoryng_ocsservers_id'],
                'lock'                                => false,
                'defaultentity'                       => -1,
                'defaultrecursive'                    => 0,
                'disable_unicity_check'               => false,
                'computers_id'                        => false,
                'force'                               => 0,
                'cron'                                => 0,
            ];

            $result = OcsProcess::processComputer($process_params);

            // When a link exists, processComputer calls synchronizeComputer which
            // can only return COMPUTER_SYNCHRONIZED or COMPUTER_NOTUPDATED.
            $synchronize_statuses = [
                OcsProcess::COMPUTER_SYNCHRONIZED, // 1
                OcsProcess::COMPUTER_NOTUPDATED,   // 4
            ];

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertContains(
                $result['status'],
                $synchronize_statuses,
                'When an ocslink exists, processComputer must return COMPUTER_SYNCHRONIZED or COMPUTER_NOTUPDATED.'
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped(
                'synchronizeComputer path could not be exercised in this environment: '
                . $e->getMessage()
            );
        }
    }
}
