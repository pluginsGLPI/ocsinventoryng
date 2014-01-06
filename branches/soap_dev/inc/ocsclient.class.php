<?php

abstract class PluginOcsinventoryngOcsClient {
    const CHECKSUM_NONE					= 0x00000;
	const CHECKSUM_HARDWARE				= 0x00001;
    const CHECKSUM_BIOS					= 0x00002;
    const CHECKSUM_MEMORY_SLOTS			= 0x00004;
    const CHECKSUM_SYSTEM_SLOTS			= 0x00008;
    const CHECKSUM_REGISTRY				= 0x00010;
    const CHECKSUM_SYSTEM_CONTROLLERS	= 0x00020;
    const CHECKSUM_MONITORS				= 0x00040;
    const CHECKSUM_SYSTEM_PORTS			= 0x00080;
    const CHECKSUM_STORAGE_PERIPHERALS	= 0x00100;
    const CHECKSUM_lOGICAL_DRIVES		= 0x00200;
    const CHECKSUM_INPUT_DEVICES		= 0x00400;
    const CHECKSUM_MODEMS				= 0x00800;
    const CHECKSUM_NETWORK_ADAPTERS		= 0x01000;
    const CHECKSUM_PRINTERS				= 0x02000;
    const CHECKSUM_SOUND_ADAPTERS		= 0x04000;
    const CHECKSUM_VIDEO_ADAPTERS		= 0x08000;
    const CHECKSUM_SOFTWARE				= 0x10000;
    const CHECKSUM_ALL					= 0x1FFFF;

    const WANTED_NONE					= 0x00000;
    const WANTED_ACCOUNTINFO			= 0x00001;
    const WANTED_DICO_SOFT				= 0x00002;
    const WANTED_ALL					= 0x00003;
	
    /**
     * Returns a list of computers
     * 
     * @param array $options Possible options :
     * 	checksum: filter computers for modified sections (see CHECKSUM_* constants)
     * 	wanted: filter computers for modified sections (see WANTED_* constants)
     * @return array List of computers :
     * 	array (
     * 		array (
     * 			'ID' => ...
     * 			'CHECKSUM' => ...
     * 			'DEVICEID' => ...
     * 			'LASTCOME' => ...
     * 			'LASTDATE' => ...
     * 			'NAME' => ...
     * 			'TAG' => ...
     * 		),
     * 		...
     * 	)
     */
	abstract public function getComputers($options);
	
	abstract public function getOcsConfig($key);
	abstract public function setOcsConfig($key, $ivalue, $tvalue);
	abstract public function getDicoSoftElement($word);
	abstract public function getHistory($offset, $count);
	abstract public function clearHistory($offset, $count);
	abstract public function resetChecksum($checksum, $ids);
}

?>