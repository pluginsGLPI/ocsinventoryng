<?php

/**
 * Integration tests for OcsProcess::synchronizeComputer().
 *
 * Run with:
 *   GLPI_ROOT=/path/to/glpi vendor/bin/phpunit --group integration --testsuite integration
 *
 * These tests require a real database with existing ocslinks rows.
 * They are structural/smoke tests that verify the interface contract of
 * synchronizeComputer() without asserting any specific business outcomes,
 * because outcomes depend on OCS server connectivity and DB state.
 */

namespace GlpiPlugin\Ocsinventoryng\Tests\Integration;

use GlpiPlugin\Ocsinventoryng\OcsProcess;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OcsProcessSynchronizeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Class-level availability guard
    // -------------------------------------------------------------------------

    public static function setUpBeforeClass(): void
    {
        if (getenv('GLPI_ROOT') === false || getenv('GLPI_ROOT') === '') {
            self::markTestSkipped(
                'Integration tests require GLPI_ROOT to be set to a valid GLPI installation path.'
            );
        }

        if (!class_exists(OcsProcess::class)) {
            self::markTestSkipped(
                'OcsProcess class is not available. '
                . 'Ensure GLPI_ROOT points to a valid GLPI installation with the plugin loaded.'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns a minimal sync_params array suitable for synchronizeComputer().
     *
     * Prerequisites:
     *   - A row must exist in glpi_plugin_ocsinventoryng_ocslinks with id=1
     *     and plugin_ocsinventoryng_ocsservers_id=1.
     *   - The corresponding OCS server must be reachable (or the method must
     *     gracefully return COMPUTER_NOTUPDATED when it is not).
     *
     * @param int $force 1 to force synchronization, 0 to let the method decide.
     * @return array<string, mixed>
     */
    private function buildMinimalSyncParams(int $force = 0): array
    {
        return [
            'ID'                                  => 1,
            'plugin_ocsinventoryng_ocsservers_id' => 1,
            'force'                               => $force,
            'cfg_ocs'                             => [],
            'cron'                                => 0,
        ];
    }

    // =========================================================================
    // Tests
    // =========================================================================

    /**
     * Prerequisites:
     *   - GLPI_ROOT is set and OcsProcess is loadable.
     *   - A valid ocslinks row with id=1 exists in the database.
     *
     * Verifies that synchronizeComputer() returns an array containing a
     * "status" key, satisfying the minimum interface contract documented by
     * the method's @return annotation.
     */
    #[Test]
    #[Group('integration')]
    public function testSynchronizeReturnsKnownStatus(): void
    {
        $sync_params = $this->buildMinimalSyncParams();

        $result = OcsProcess::synchronizeComputer($sync_params);

        $this->assertIsArray($result, 'synchronizeComputer() must return an array');
        $this->assertArrayHasKey('status', $result, 'Return value must contain a "status" key');
    }

    /**
     * Prerequisites:
     *   - GLPI_ROOT is set and OcsProcess is loadable.
     *   - A valid ocslinks row with id=1 exists and was already synchronized,
     *     so without force=1 the method would return COMPUTER_NOTUPDATED.
     *
     * Verifies that passing force=1 in sync_params causes synchronizeComputer()
     * to return COMPUTER_SYNCHRONIZED (1) rather than COMPUTER_NOTUPDATED (4).
     * This ensures the force flag short-circuits the "nothing to do" early-exit
     * branch inside synchronizeComputer().
     */
    #[Test]
    #[Group('integration')]
    public function testSynchronizeWithForceFlagAlwaysRuns(): void
    {
        $sync_params = $this->buildMinimalSyncParams(force: 1);

        $result = OcsProcess::synchronizeComputer($sync_params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertNotSame(
            OcsProcess::COMPUTER_NOTUPDATED,
            $result['status'],
            'With force=1 the result must not be COMPUTER_NOTUPDATED; '
            . 'the force flag must bypass the up-to-date check.'
        );
        $this->assertSame(
            OcsProcess::COMPUTER_SYNCHRONIZED,
            $result['status'],
            'With force=1 on an already-known computer, status must be COMPUTER_SYNCHRONIZED.'
        );
    }

    /**
     * Prerequisites:
     *   - GLPI_ROOT is set and OcsProcess is loadable.
     *   - A valid ocslinks row with id=1 exists in the database.
     *
     * Verifies the return type contract independently: result must be an array
     * and must carry a "status" key.  This is a duplicate of the first test
     * expressed as an explicit contract assertion so that a regression in return
     * type (e.g. returning a scalar on early-exit paths) is caught separately.
     */
    #[Test]
    #[Group('integration')]
    public function testSynchronizeReturnedArrayHasStatusKey(): void
    {
        $sync_params = $this->buildMinimalSyncParams();

        $result = OcsProcess::synchronizeComputer($sync_params);

        $this->assertIsArray($result, 'Return value must be an array, not a scalar or null');
        $this->assertArrayHasKey(
            'status',
            $result,
            'The "status" key must always be present regardless of sync outcome'
        );
    }

    /**
     * Prerequisites:
     *   - GLPI_ROOT is set and OcsProcess is loadable.
     *   - A valid ocslinks row with id=1 exists in the database.
     *
     * Verifies that the "status" value returned by synchronizeComputer() is one
     * of the two expected constants for a computer that already exists in GLPI:
     *   - COMPUTER_SYNCHRONIZED (1) — the computer was updated
     *   - COMPUTER_NOTUPDATED   (4) — nothing changed; update skipped
     *
     * Other constants (IMPORTED, LINKED, FAILED, etc.) are not valid outcomes
     * for a computer that already has an ocslink row.
     */
    #[Test]
    #[Group('integration')]
    public function testSynchronizeStatusIsValidConstant(): void
    {
        $sync_params = $this->buildMinimalSyncParams();

        $result = OcsProcess::synchronizeComputer($sync_params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);

        $valid_statuses = [
            OcsProcess::COMPUTER_SYNCHRONIZED, // 1
            OcsProcess::COMPUTER_NOTUPDATED,   // 4
        ];

        $this->assertContains(
            $result['status'],
            $valid_statuses,
            sprintf(
                'Status %d is not a valid outcome for an already-linked computer. '
                . 'Expected one of: %s',
                $result['status'],
                implode(', ', $valid_statuses)
            )
        );
    }
}
