<?php

/**
 * OCS Inventory NG Plugin for GLPI
 * Fixtures for OCS computer inventory arrays used in unit tests.
 */

namespace GlpiPlugin\Ocsinventoryng\Tests\Fixtures;

/**
 * Factory class providing realistic OCS inventory data arrays for testing.
 *
 * Each method returns an associative array that mimics what the OCS server
 * returns when querying a computer, matching the structure consumed by the
 * plugin's import/sync routines.
 */
class OcsComputerFixtures
{
    /**
     * Returns a realistic desktop computer inventory array.
     *
     * @param int $ocsid OCS internal computer ID
     * @return array
     */
    public static function desktop(int $ocsid = 1): array
    {
        return [
            'META' => [
                'ID'        => $ocsid,
                'NAME'      => 'DESKTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT),
                'DEVICEID'  => 'DESKTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT) . '-2024-01-15-10-00-00',
                'CHECKSUM'  => 131071,
                'LASTDATE'  => '2024-01-15 10:00:00',
                'LASTCOME'  => '2024-01-15 10:00:00',
                'USERAGENT' => 'OCS-NG_unified_unix_agent_v2.10.0',
                'IPSRC'     => '192.168.' . $ocsid . '.10',
            ],
            'HARDWARE' => [
                'NAME'           => 'DESKTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT),
                'OSNAME'         => 'Windows 10 Pro',
                'OSVERSION'      => '10.0.19045',
                'PROCESSORT'     => 'Intel(R) Core(TM) i7-10700 CPU @ 2.90GHz',
                'PROCESSORS'     => 2900,
                'PROCESSORN'     => 8,
                'MEMORY'         => 16384,
                'USERID'         => 'jdoe',
                'WORKGROUP'      => 'INFOTEL.LOCAL',
                'DEVICEID'       => 'DESKTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT) . '-2024-01-15-10-00-00',
                'DEFAULTGATEWAY' => '192.168.' . $ocsid . '.1',
                'IPADDR'         => '192.168.' . $ocsid . '.10',
                'IPSRC'          => '192.168.' . $ocsid . '.10',
                'LASTDATE'       => '2024-01-15 10:00:00',
                'LASTCOME'       => '2024-01-15 10:00:00',
                'USERAGENT'      => 'OCS-NG_unified_unix_agent_v2.10.0',
                'UUID'           => '4C4C4544-' . strtoupper(bin2hex(pack('n', $ocsid))) . '-3510-8047-B8C04F' . str_pad(dechex($ocsid), 6, '0', STR_PAD_LEFT),
            ],
            'BIOS' => [
                'SSN'           => 'SN' . str_pad($ocsid, 8, '0', STR_PAD_LEFT),
                'TYPE'          => 'Desktop',
                'SMANUFACTURER' => 'Dell Inc.',
                'SMODEL'        => 'OptiPlex 7080',
                'ASSETTAG'      => 'ASSET-' . str_pad($ocsid, 5, '0', STR_PAD_LEFT),
                'MMANUFACTURER' => 'Dell Inc.',
                'MMODEL'        => '0VDT73',
                'MSN'           => 'MSN' . str_pad($ocsid, 7, '0', STR_PAD_LEFT),
            ],
            'NETWORKS' => [
                [
                    'IPADDRESS'   => '192.168.' . $ocsid . '.10',
                    'MACADDR'     => sprintf('00:1A:2B:%02X:%02X:%02X', $ocsid, 0x3C, 0x4D),
                    'DESCRIPTION' => 'Intel(R) Ethernet Connection (11) I219-LM',
                    'IPSUBNET'    => '255.255.255.0',
                    'IPGATEWAY'   => '192.168.' . $ocsid . '.1',
                    'STATUS'      => 'Up',
                    'VIRTUALDEV'  => 0,
                ],
                [
                    'IPADDRESS'   => '172.16.' . $ocsid . '.10',
                    'MACADDR'     => sprintf('00:1A:2B:%02X:%02X:%02X', $ocsid, 0x5E, 0x6F),
                    'DESCRIPTION' => 'Intel(R) Wi-Fi 6 AX201 160MHz',
                    'IPSUBNET'    => '255.255.0.0',
                    'IPGATEWAY'   => '172.16.' . $ocsid . '.1',
                    'STATUS'      => 'Up',
                    'VIRTUALDEV'  => 0,
                ],
            ],
            'SOFTWARES' => [
                [
                    'NAME'      => 'Microsoft Office 2021',
                    'VERSION'   => '16.0.14332.20238',
                    'PUBLISHER' => 'Microsoft Corporation',
                    'FOLDER'    => 'C:\\Program Files\\Microsoft Office',
                    'COMMENTS'  => '',
                    'FILENAME'  => '',
                    'FILESIZE'  => 0,
                ],
                [
                    'NAME'      => 'Google Chrome',
                    'VERSION'   => '120.0.6099.130',
                    'PUBLISHER' => 'Google LLC',
                    'FOLDER'    => 'C:\\Program Files\\Google\\Chrome\\Application',
                    'COMMENTS'  => '',
                    'FILENAME'  => '',
                    'FILESIZE'  => 0,
                ],
                [
                    'NAME'      => '7-Zip 23.01 (x64)',
                    'VERSION'   => '23.01',
                    'PUBLISHER' => 'Igor Pavlov',
                    'FOLDER'    => 'C:\\Program Files\\7-Zip',
                    'COMMENTS'  => '',
                    'FILENAME'  => '',
                    'FILESIZE'  => 0,
                ],
            ],
        ];
    }

    /**
     * Returns a realistic laptop computer inventory array.
     *
     * @param int $ocsid OCS internal computer ID
     * @return array
     */
    public static function laptop(int $ocsid = 2): array
    {
        return [
            'META' => [
                'ID'        => $ocsid,
                'NAME'      => 'LAPTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT),
                'DEVICEID'  => 'LAPTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT) . '-2024-01-15-10-00-00',
                'CHECKSUM'  => 131071,
                'LASTDATE'  => '2024-01-15 10:00:00',
                'LASTCOME'  => '2024-01-15 10:00:00',
                'USERAGENT' => 'OCS-NG_unified_unix_agent_v2.10.0',
                'IPSRC'     => '10.0.' . $ocsid . '.50',
            ],
            'HARDWARE' => [
                'NAME'           => 'LAPTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT),
                'OSNAME'         => 'Windows 11 Pro',
                'OSVERSION'      => '10.0.22621',
                'PROCESSORT'     => 'Intel(R) Core(TM) i5-1235U CPU @ 1.30GHz',
                'PROCESSORS'     => 1300,
                'PROCESSORN'     => 10,
                'MEMORY'         => 8192,
                'USERID'         => 'msmith',
                'WORKGROUP'      => 'INFOTEL.LOCAL',
                'DEVICEID'       => 'LAPTOP-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT) . '-2024-01-15-10-00-00',
                'DEFAULTGATEWAY' => '10.0.' . $ocsid . '.1',
                'IPADDR'         => '10.0.' . $ocsid . '.50',
                'IPSRC'          => '10.0.' . $ocsid . '.50',
                'LASTDATE'       => '2024-01-15 10:00:00',
                'LASTCOME'       => '2024-01-15 10:00:00',
                'USERAGENT'      => 'OCS-NG_unified_unix_agent_v2.10.0',
                'UUID'           => 'A1B2C3D4-' . strtoupper(bin2hex(pack('n', $ocsid))) . '-4F5A-9B8C-E7F6' . str_pad(dechex($ocsid * 256), 8, '0', STR_PAD_LEFT),
            ],
            'BIOS' => [
                'SSN'           => 'LT' . str_pad($ocsid, 8, '0', STR_PAD_LEFT),
                'TYPE'          => 'Notebook',
                'SMANUFACTURER' => 'Lenovo',
                'SMODEL'        => 'ThinkPad T14s Gen 3',
                'ASSETTAG'      => 'LT-ASSET-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT),
                'MMANUFACTURER' => 'Lenovo',
                'MMODEL'        => '21BR0007FR',
                'MSN'           => 'LTM' . str_pad($ocsid, 7, '0', STR_PAD_LEFT),
            ],
            'NETWORKS' => [
                [
                    'IPADDRESS'   => '10.0.' . $ocsid . '.50',
                    'MACADDR'     => sprintf('00:AA:BB:%02X:%02X:%02X', $ocsid, 0x11, 0x22),
                    'DESCRIPTION' => 'Intel(R) Ethernet Connection I219-V',
                    'IPSUBNET'    => '255.255.0.0',
                    'IPGATEWAY'   => '10.0.' . $ocsid . '.1',
                    'STATUS'      => 'Up',
                    'VIRTUALDEV'  => 0,
                ],
                [
                    'IPADDRESS'   => '10.1.' . $ocsid . '.50',
                    'MACADDR'     => sprintf('00:AA:BB:%02X:%02X:%02X', $ocsid, 0x33, 0x44),
                    'DESCRIPTION' => 'Intel(R) Wi-Fi 6E AX211 160MHz',
                    'IPSUBNET'    => '255.255.0.0',
                    'IPGATEWAY'   => '10.1.' . $ocsid . '.1',
                    'STATUS'      => 'Up',
                    'VIRTUALDEV'  => 0,
                ],
                [
                    'IPADDRESS'   => '169.254.1.' . $ocsid,
                    'MACADDR'     => sprintf('00:50:56:%02X:%02X:%02X', $ocsid, 0xC0, 0x01),
                    'DESCRIPTION' => 'VirtualBox Host-Only Ethernet Adapter',
                    'IPSUBNET'    => '255.255.0.0',
                    'IPGATEWAY'   => '',
                    'STATUS'      => 'Up',
                    'VIRTUALDEV'  => 1,
                ],
            ],
            'SOFTWARES' => [
                [
                    'NAME'      => 'Microsoft Visual Studio Code',
                    'VERSION'   => '1.85.1',
                    'PUBLISHER' => 'Microsoft Corporation',
                    'FOLDER'    => 'C:\\Users\\msmith\\AppData\\Local\\Programs\\Microsoft VS Code',
                    'COMMENTS'  => '',
                    'FILENAME'  => '',
                    'FILESIZE'  => 0,
                ],
                [
                    'NAME'      => 'Mozilla Firefox',
                    'VERSION'   => '121.0',
                    'PUBLISHER' => 'Mozilla',
                    'FOLDER'    => 'C:\\Program Files\\Mozilla Firefox',
                    'COMMENTS'  => '',
                    'FILENAME'  => '',
                    'FILESIZE'  => 0,
                ],
                [
                    'NAME'      => 'Notepad++',
                    'VERSION'   => '8.6',
                    'PUBLISHER' => 'Notepad++ Team',
                    'FOLDER'    => 'C:\\Program Files\\Notepad++',
                    'COMMENTS'  => '',
                    'FILENAME'  => '',
                    'FILESIZE'  => 0,
                ],
                [
                    'NAME'      => 'Git',
                    'VERSION'   => '2.43.0',
                    'PUBLISHER' => 'The Git Development Community',
                    'FOLDER'    => 'C:\\Program Files\\Git',
                    'COMMENTS'  => '',
                    'FILENAME'  => '',
                    'FILESIZE'  => 0,
                ],
            ],
        ];
    }

    /**
     * Returns a minimal inventory array containing only META, HARDWARE, and BIOS.
     * Useful for testing import scenarios where network and software sections are absent.
     *
     * @param int $ocsid OCS internal computer ID
     * @return array
     */
    public static function minimal(int $ocsid = 3): array
    {
        return [
            'META' => [
                'ID'        => $ocsid,
                'NAME'      => 'MINIMAL-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT),
                'DEVICEID'  => 'MINIMAL-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT) . '-2024-01-15-10-00-00',
                'CHECKSUM'  => 1,
                'LASTDATE'  => '2024-01-15 10:00:00',
                'LASTCOME'  => '2024-01-15 10:00:00',
                'USERAGENT' => 'OCS-NG_unified_unix_agent_v2.10.0',
                'IPSRC'     => '192.168.0.' . $ocsid,
            ],
            'HARDWARE' => [
                'NAME'           => 'MINIMAL-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT),
                'OSNAME'         => 'Windows 10 Pro',
                'OSVERSION'      => '10.0.19045',
                'PROCESSORT'     => 'Intel(R) Core(TM) i3-10100 CPU @ 3.60GHz',
                'PROCESSORS'     => 3600,
                'PROCESSORN'     => 4,
                'MEMORY'         => 4096,
                'USERID'         => 'local',
                'WORKGROUP'      => 'WORKGROUP',
                'DEVICEID'       => 'MINIMAL-' . str_pad($ocsid, 4, '0', STR_PAD_LEFT) . '-2024-01-15-10-00-00',
                'DEFAULTGATEWAY' => '192.168.0.1',
                'IPADDR'         => '192.168.0.' . $ocsid,
                'IPSRC'          => '192.168.0.' . $ocsid,
                'LASTDATE'       => '2024-01-15 10:00:00',
                'LASTCOME'       => '2024-01-15 10:00:00',
                'USERAGENT'      => 'OCS-NG_unified_unix_agent_v2.10.0',
                'UUID'           => 'FFFFFFFF-' . strtoupper(bin2hex(pack('N', $ocsid))) . '-4AAA-BBBB-CCCCCCCCCCCC',
            ],
            'BIOS' => [
                'SSN'           => 'MIN' . str_pad($ocsid, 8, '0', STR_PAD_LEFT),
                'TYPE'          => 'Desktop',
                'SMANUFACTURER' => 'HP',
                'SMODEL'        => 'ProDesk 400 G7',
                'ASSETTAG'      => '',
                'MMANUFACTURER' => 'HP',
                'MMODEL'        => '8823',
                'MSN'           => 'MNM' . str_pad($ocsid, 7, '0', STR_PAD_LEFT),
            ],
        ];
    }

    /**
     * Returns a desktop computer inventory array populated with a large number of
     * software entries. Used to test performance and pagination in software import.
     *
     * @param int $ocsid  OCS internal computer ID
     * @param int $count  Number of software entries to generate
     * @return array
     */
    public static function withManySoftwares(int $ocsid = 4, int $count = 200): array
    {
        $computer = static::desktop($ocsid);

        // Vendor and product pools used to build realistic-looking software names
        $publishers = [
            'Microsoft Corporation',
            'Adobe Inc.',
            'Oracle Corporation',
            'Google LLC',
            'Mozilla Foundation',
            'Cisco Systems',
            'VMware Inc.',
            'Synaptics Incorporated',
            'Intel Corporation',
            'NVIDIA Corporation',
        ];

        $productTemplates = [
            'Application Suite %d',
            'Security Agent %d',
            'Runtime Environment %d.0',
            'Driver Package %d',
            'Utility Tool %d',
            'Framework SDK %d',
            'Management Console %d',
            'Monitoring Service %d',
            'Backup Client %d',
            'Remote Access %d',
        ];

        $softwares = [];
        for ($i = 1; $i <= $count; $i++) {
            $publisherIndex = ($i - 1) % count($publishers);
            $productIndex   = ($i - 1) % count($productTemplates);
            $majorVersion   = intdiv($i, 10) + 1;
            $minorVersion   = $i % 10;

            $softwares[] = [
                'NAME'      => sprintf($productTemplates[$productIndex], $i),
                'VERSION'   => $majorVersion . '.' . $minorVersion . '.0.0',
                'PUBLISHER' => $publishers[$publisherIndex],
                'FOLDER'    => 'C:\\Program Files\\Vendor' . $publisherIndex . '\\Product' . $i,
                'COMMENTS'  => '',
                'FILENAME'  => '',
                'FILESIZE'  => 0,
            ];
        }

        $computer['SOFTWARES'] = $softwares;

        return $computer;
    }

    /**
     * Returns an array of $count computer inventory arrays with sequential IDs.
     * Alternates between desktop and laptop types for variety.
     *
     * @param int $count   Number of computers to generate
     * @param int $startId First OCS ID to use (incremented for each computer)
     * @return array  Array of computer inventory arrays indexed from 0
     */
    public static function collection(int $count = 10, int $startId = 1): array
    {
        $computers = [];

        for ($i = 0; $i < $count; $i++) {
            $ocsid = $startId + $i;

            // Alternate between desktop and laptop, with every 10th being minimal
            if ($ocsid % 10 === 0) {
                $computers[] = static::minimal($ocsid);
            } elseif ($ocsid % 2 === 0) {
                $computers[] = static::laptop($ocsid);
            } else {
                $computers[] = static::desktop($ocsid);
            }
        }

        return $computers;
    }
}
