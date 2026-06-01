<?php

/**
 * Unit tests for pure static methods of OcsProcess that do not need a database.
 *
 * Run with:
 *   vendor/bin/phpunit tests/units/OcsProcessTest.php
 *
 * If GLPI_ROOT is not set, the OcsProcess class cannot be loaded (it extends
 * CommonDBTM which requires the GLPI autoloader). In that case every test is
 * skipped via a class-level check in setUpBeforeClass().
 */

namespace GlpiPlugin\Ocsinventoryng\Tests\Units;

use GlpiPlugin\Ocsinventoryng\OcsProcess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OcsProcessTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Class-level availability guard
    // -------------------------------------------------------------------------

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(OcsProcess::class)) {
            // The class is unavailable without the GLPI autoloader.
            // All tests in this class will be skipped.
            self::markTestSkipped(
                'OcsProcess class is not available. '
                . 'Set GLPI_ROOT to a valid GLPI installation and re-run.'
            );
        }
    }

    // =========================================================================
    // Tests for getAvailableStatistics()
    // =========================================================================

    #[Test]
    public function getAvailableStatisticsDefaultReturnsSevenKeys(): void
    {
        $stats = OcsProcess::getAvailableStatistics();

        $this->assertIsArray($stats);
        $this->assertCount(7, $stats);

        $expected_keys = [
            'imported_machines_number',
            'synchronized_machines_number',
            'linked_machines_number',
            'notupdated_machines_number',
            'failed_rules_machines_number',
            'not_unique_machines_number',
            'link_refused_machines_number',
        ];

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $stats, "Missing key: $key");
        }
    }

    #[Test]
    public function getAvailableStatisticsSnmpReturnsFiveSnmpKeys(): void
    {
        $stats = OcsProcess::getAvailableStatistics(snmp: true);

        $this->assertIsArray($stats);
        $this->assertCount(5, $stats);

        $expected_keys = [
            'imported_snmp_number',
            'synchronized_snmp_number',
            'linked_snmp_number',
            'notupdated_snmp_number',
            'failed_imported_snmp_number',
        ];

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $stats, "Missing SNMP key: $key");
        }
    }

    #[Test]
    public function getAvailableStatisticsIpdiscoverReturnsFourIpdiscoverKeys(): void
    {
        $stats = OcsProcess::getAvailableStatistics(ipdiscover: true);

        $this->assertIsArray($stats);
        $this->assertCount(4, $stats);

        $expected_keys = [
            'imported_ipdiscover_number',
            'synchronized_ipdiscover_number',
            'notupdated_ipdiscover_number',
            'failed_imported_ipdiscover_number',
        ];

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $stats, "Missing ipdiscover key: $key");
        }
    }

    // =========================================================================
    // Tests for manageImportStatistics()
    // =========================================================================

    #[Test]
    public function manageImportStatisticsEmptyArrayInitializesAllSevenCountersToZero(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics);

        $this->assertCount(7, $statistics);

        $expected_keys = [
            'imported_machines_number',
            'synchronized_machines_number',
            'linked_machines_number',
            'notupdated_machines_number',
            'failed_rules_machines_number',
            'not_unique_machines_number',
            'link_refused_machines_number',
        ];

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $statistics, "Counter not initialized: $key");
            $this->assertSame(0, $statistics[$key], "Counter $key should be 0");
        }
    }

    #[Test]
    public function manageImportStatisticsComputerImportedIncrementsImportedCounter(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_IMPORTED);

        $this->assertSame(1, $statistics['imported_machines_number']);
    }

    #[Test]
    public function manageImportStatisticsComputerSynchronizedIncrementsSynchronizedCounter(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_SYNCHRONIZED);

        $this->assertSame(1, $statistics['synchronized_machines_number']);
    }

    #[Test]
    public function manageImportStatisticsComputerLinkedIncrementsLinkedCounter(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_LINKED);

        $this->assertSame(1, $statistics['linked_machines_number']);
    }

    #[Test]
    public function manageImportStatisticsComputerFailedImportIncrementsFailedRulesCounter(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_FAILED_IMPORT);

        $this->assertSame(1, $statistics['failed_rules_machines_number']);
    }

    #[Test]
    public function manageImportStatisticsComputerNotUpdatedIncrementsNotUpdatedCounter(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_NOTUPDATED);

        $this->assertSame(1, $statistics['notupdated_machines_number']);
    }

    #[Test]
    public function manageImportStatisticsComputerNotUniqueIncrementsNotUniqueCounter(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_NOT_UNIQUE);

        $this->assertSame(1, $statistics['not_unique_machines_number']);
    }

    #[Test]
    public function manageImportStatisticsComputerLinkRefusedIncrementsLinkRefusedCounter(): void
    {
        $statistics = [];
        OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_LINK_REFUSED);

        $this->assertSame(1, $statistics['link_refused_machines_number']);
    }

    #[Test]
    public function manageImportStatisticsCalledFiftyTimesWithImportedCounterEqualsFifty(): void
    {
        $statistics = [];

        for ($i = 0; $i < 50; $i++) {
            OcsProcess::manageImportStatistics($statistics, OcsProcess::COMPUTER_IMPORTED);
        }

        $this->assertSame(50, $statistics['imported_machines_number']);
        // Other counters must remain at 0.
        $this->assertSame(0, $statistics['synchronized_machines_number']);
        $this->assertSame(0, $statistics['linked_machines_number']);
        $this->assertSame(0, $statistics['failed_rules_machines_number']);
        $this->assertSame(0, $statistics['notupdated_machines_number']);
        $this->assertSame(0, $statistics['not_unique_machines_number']);
        $this->assertSame(0, $statistics['link_refused_machines_number']);
    }

    // =========================================================================
    // Tests for encodeOcsDataInUtf8()
    // =========================================================================

    /**
     * Data provider for valid UTF-8 passthrough cases.
     *
     * @return array<string, array{string}>
     */
    public static function validUtf8Provider(): array
    {
        return [
            'ascii string'          => ['Hello World'],
            'accented characters'   => ['Héllo Wörld'],
            'cyrillic'              => ['Привет'],
            'emoji'                 => ['Test 🎉'],
            'chinese'               => ['你好世界'],
            'empty string'          => [''],
        ];
    }

    #[Test]
    #[DataProvider('validUtf8Provider')]
    public function encodeOcsDataInUtf8WithValidUtf8ReturnsSameString(string $value): void
    {
        // The first argument ($is_ocsdb_utf8) is not used inside the method body;
        // the method only checks mb_check_encoding on $value.
        $result = OcsProcess::encodeOcsDataInUtf8(true, $value);

        $this->assertSame($value, $result);
    }

    #[Test]
    public function encodeOcsDataInUtf8WithNullReturnsNull(): void
    {
        // Passing null: the condition `if ($value && ...)` is falsy, so null is
        // returned as-is without encoding.
        $result = OcsProcess::encodeOcsDataInUtf8(true, null);

        $this->assertNull($result);
    }

    #[Test]
    public function encodeOcsDataInUtf8WithEmptyStringReturnsEmptyString(): void
    {
        // Empty string is falsy, so the method returns it directly.
        $result = OcsProcess::encodeOcsDataInUtf8(true, '');

        $this->assertSame('', $result);
    }
}
