<?php

/**
 * In-memory test double for GlpiPlugin\Ocsinventoryng\OcsClient.
 *
 * Allows unit tests to load computer data up-front and assert on the side-effects
 * (setChecksum calls, removeDeletedComputers calls) without hitting a real OCS server.
 */

namespace GlpiPlugin\Ocsinventoryng\Tests\Fixtures;

use GlpiPlugin\Ocsinventoryng\OcsClient;

/**
 * Fake (in-memory) implementation of OcsClient for use in PHPUnit tests.
 *
 * Load test data with addComputer() before exercising the code under test,
 * then inspect recorded side-effects via getSetChecksumCalls().
 */
class FakeOcsClient extends OcsClient
{
    /**
     * Computers stored in memory, keyed by OCS hardware ID.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $computers = [];

    /**
     * Each entry records one setChecksum() invocation.
     * Shape: ['checksum' => int, 'ocsid' => int]
     *
     * @var list<array{checksum: int, ocsid: int}>
     */
    private array $setChecksumCalls = [];

    /**
     * Deleted-computer entries returned by getDeletedComputers().
     *
     * @var array<mixed>
     */
    private array $deletedComputers = [];

    // -------------------------------------------------------------------------
    // Test-helper API
    // -------------------------------------------------------------------------

    /**
     * Load a computer into the fake store.
     *
     * @param int                  $ocsid OCS hardware ID.
     * @param array<string, mixed> $data  Computer data (HARDWARE, BIOS, …).
     */
    public function addComputer(int $ocsid, array $data): void
    {
        $this->computers[$ocsid] = $data;
    }

    /**
     * Return all recorded setChecksum() calls for assertion.
     *
     * @return list<array{checksum: int, ocsid: int}>
     */
    public function getSetChecksumCalls(): array
    {
        return $this->setChecksumCalls;
    }

    /**
     * Override the deleted-computers list returned by getDeletedComputers().
     *
     * @param array<mixed> $deleted
     */
    public function setDeletedComputers(array $deleted): void
    {
        $this->deletedComputers = $deleted;
    }

    // -------------------------------------------------------------------------
    // OcsClient concrete implementations used by tests
    // -------------------------------------------------------------------------

    /**
     * Always reports a successful connection.
     */
    public function checkConnection(): bool
    {
        return true;
    }

    /**
     * Return a single computer by OCS ID, or null when not found.
     *
     * Overrides the concrete helper in OcsClient so the fake does not need to
     * satisfy the full getComputers() contract just to support getComputer().
     *
     * @param int                  $ocsid   OCS hardware ID.
     * @param array<string, mixed> $options Ignored in the fake.
     *
     * @return array<string, mixed>|null
     */
    public function getComputer(int $ocsid, array $options = []): ?array
    {
        return $this->computers[$ocsid] ?? null;
    }

    /**
     * Return all stored computers.
     *
     * Satisfies the abstract getComputers($options, $id = 0) contract while also
     * exposing a clean typed signature for direct test calls.
     *
     * @param array<string, mixed> $options Ignored in the fake.
     * @param int                  $id      Ignored in the fake.
     *
     * @return array<mixed>
     */
    public function getComputers($options = [], $id = 0): array
    {
        $computers = array_values($this->computers);

        return [
            'TOTAL_COUNT' => count($computers),
            'COMPUTERS'   => $computers,
        ];
    }

    /**
     * Record the call and return true to signal success.
     *
     * @param int $checksum New checksum bitmask.
     * @param int $ocsid    OCS hardware ID.
     */
    public function setChecksum($checksum, $ocsid): bool
    {
        $this->setChecksumCalls[] = [
            'checksum' => $checksum,
            'ocsid'    => $ocsid,
        ];

        return true;
    }

    /**
     * Return the configured deleted-computers list.
     *
     * @return array<mixed>
     */
    public function getDeletedComputers(): array
    {
        return $this->deletedComputers;
    }

    /**
     * Always returns true; records no state (use getSetChecksumCalls() for that).
     *
     * @param mixed $del    Deleted entry (ID or device ID).
     * @param mixed $equiv  Equivalent entry after merge, if any.
     */
    public function removeDeletedComputers($del, $equiv = null): bool
    {
        return true;
    }

    // -------------------------------------------------------------------------
    // Remaining abstract stubs — not exercised in unit tests
    // -------------------------------------------------------------------------

    /** @param string $field @param mixed $value @return array<mixed> */
    public function searchComputers($field, $value): array
    {
        return [];
    }

    /** @param array<string, mixed> $options @return array<mixed> */
    public function getSnmp($options): array
    {
        return ['TOTAL_COUNT' => 0, 'SNMP' => []];
    }

    /** @param string $key @return array<string, mixed> */
    public function getConfig($key): array
    {
        return ['IVALUE' => 0, 'TVALUE' => ''];
    }

    /** @param string $key @param int $ivalue @param string $tvalue */
    public function setConfig($key, $ivalue, $tvalue): void
    {
    }

    /** @param int $id @return int */
    public function getChecksum($id): int
    {
        return 0;
    }

    /** @param array<string, mixed> $cfg_ocs @param mixed $max_date @return array<mixed> */
    public function getComputersToUpdate($cfg_ocs, $max_date): array
    {
        return [];
    }

    /** @return array<mixed> */
    public function getOCSComputers(): array
    {
        return [];
    }

    /** @return int */
    public function getTotalDeletedComputers(): int
    {
        return count($this->deletedComputers);
    }

    /** @param int $nb_days @return array<mixed> */
    public function getOldAgents($nb_days): array
    {
        return [];
    }

    /** @param string $table @return array<mixed> */
    public function getAccountInfoColumns($table): array
    {
        return [];
    }

    /** @param int $ssn @param int $id */
    public function updateBios($ssn, $id): void
    {
    }

    /** @param int $tag @param int $id */
    public function updateTag($tag, $id): void
    {
    }
}
