<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

 https://github.com/pluginsGLPI/ocsinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocsinventoryng.

 ocsinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocsinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngService
 */
class PluginOcsinventoryngDevice extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    * Import the devices for a computer
    *
    * @param integer $devicetype device type
    * @param array   $ocsComputer
    * @param array   $params (computers_id, entities_id, cfg_ocs, params_devices)
    */
   static function updateDevices($devicetype, $ocsComputer, $params) {

      $computers_id = $params['computers_id'];
      $entities_id  = $params['entities_id'];
      $cfg_ocs      = $params['cfg_ocs'];
      $ocs_db_utf8  = $params['cfg_ocs']['ocs_db_utf8'];
      $force        = $params['force'];

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_devices'] == 1 || $cfg_ocs['history_devices'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_devices'] == 1 || $cfg_ocs['history_devices'] == 2)) {
         $install_history = 1;
      }

      switch ($devicetype) {

         case "Item_DeviceFirmware":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }

            $CompDevice = new $devicetype();
            $tab     = $CompDevice->find(['items_id'    => $computers_id,
                                               'itemtype'    => 'Computer',
                                               'entities_id' => $entities_id,
                                               'is_dynamic'  => 1]);
            //Bios
            $bios["designation"]             = $ocsComputer["BVERSION"];
            $bios["entities_id"]             = $entities_id;
            $bios["comment"]                 = $ocsComputer["BDATE"] . " - " . $ocsComputer["ASSETTAG"];
            $bios["manufacturers_id"]        = Dropdown::importExternal('Manufacturer',
                                                                        PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8,
                                                                                                                            $ocsComputer["SMANUFACTURER"]));
            $bios["devicefirmwaremodels_id"] = Dropdown::importExternal('DeviceFirmwareModel',
                                                                        PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8,
                                                                                                                            $ocsComputer["SMODEL"]));
            $bios["devicefirmwaretypes_id"]  = Dropdown::importExternal('DeviceFirmwareType',
                                                                        PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8,
                                                                                                                            $ocsComputer["TYPE"]));

            $DeviceBios = new DeviceFirmware();
            $bios_id    = $DeviceBios->import($bios);

            if ($bios_id) {
               $found = false;
               foreach ($tab as $id => $curr) {
                  if ($curr['devicefirmwares_id'] == $bios_id) {
                     unset($tab[$id]);
                     $found = true;
                     break;
                  }
               }
               if ($found) {
                  $CompDevice->update(['id'                 => $CompDevice->getID(),
                                       'items_id'           => $computers_id,
                                       'itemtype'           => 'Computer',
                                       'entities_id'        => $entities_id,
                                       'devicefirmwares_id' => $bios_id,
                                       'is_dynamic'         => 1], $install_history);
               } else {
                  $CompDevice->add(['items_id'           => $computers_id,
                                    'itemtype'           => 'Computer',
                                    'devicefirmwares_id' => $bios_id,
                                    'is_dynamic'         => 1,
                                    'entities_id'        => $entities_id], [], $install_history);
               }
            }

            break;

         case "Item_DeviceMemory":
            //MEMORIES

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }

            $KnownDevice = new $devicetype();
            $tab     = $KnownDevice->find(['items_id'    => $computers_id,
                                               'itemtype'    => 'Computer',
                                               'entities_id' => $entities_id,
                                               'is_dynamic'  => 1]);

            $CompDevice = new $devicetype();
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if (isset($line2["CAPACITY"]) && $line2["CAPACITY"] != "No") {
                  $ram["designation"] = "";
                  if ($line2["TYPE"] != "Empty Slot" && $line2["TYPE"] != "Unknown") {
                     $ram["designation"] = $line2["TYPE"];
                  }
                  if ($line2["DESCRIPTION"]) {
                     if (!empty($ram["designation"])) {
                        $ram["designation"] .= " - ";
                     }
                     $ram["designation"] .= $line2["DESCRIPTION"];
                  }
                  if (!is_numeric($line2["CAPACITY"])) {
                     $line2["CAPACITY"] = 0;
                  }
                  if (is_numeric($line2["CAPACITY"])) {
                     $ram["size_default"] = $line2["CAPACITY"];
                  }
                  $ram["entities_id"] = $entities_id;

                  if ($line2["SPEED"] != "Unknown" && is_numeric($line2["SPEED"])) {
                     $ram["frequence"] = $line2["SPEED"];
                  }
                  $ram["devicememorytypes_id"] = Dropdown::importExternal('DeviceMemoryType',
                                                                          $line2["TYPE"]);

                  $DeviceMemory = new DeviceMemory();
                  $ram_id       = $DeviceMemory->import($ram);
                  if ($ram_id) {
                     $found = false;
                     foreach ($tab as $id => $curr) {
                        if ($curr['devicememories_id'] == $ram_id) {
                           unset($tab[$id]);
                           $found = true;
                           break;
                        }
                     }
                     if ($found) {
                        $CompDevice->update(['id'                => $CompDevice->getID(),
                                             'items_id'          => $computers_id,
                                             'itemtype'          => 'Computer',
                                             'entities_id'       => $entities_id,
                                             'devicememories_id' => $ram_id,
                                             'size'              => $line2["CAPACITY"],
                                             'is_dynamic'        => 1], $install_history);
                     } else {
                        $CompDevice->add(['items_id'          => $computers_id,
                                          'itemtype'          => 'Computer',
                                          'devicememories_id' => $ram_id,
                                          'size'              => $line2["CAPACITY"],
                                          'is_dynamic'        => 1,
                                          'entities_id'       => $entities_id], [], $install_history);
                     }
                  }
               }
            }
            break;

         case "Item_DeviceHardDrive":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }

            $CompDevice = new $devicetype();
            //Disque Dur
            $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                               'itemtype'    => 'Computer',
                                               'entities_id' => $entities_id,
                                               'is_dynamic'  => 1]);
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if (!empty($line2["DISKSIZE"]) && preg_match("/disk|spare\sdrive/i", $line2["TYPE"])) {
                  if ($line2["NAME"]) {
                     $dd["designation"] = $line2["NAME"];
                  } else {
                     if ($line2["MODEL"]) {
                        $dd["designation"] = $line2["MODEL"];
                     } else {
                        $dd["designation"] = "Unknown";
                     }
                  }
                  if (!is_numeric($line2["DISKSIZE"])) {
                     $line2["DISKSIZE"] = 0;
                  }
                  $dd["entities_id"]      = $entities_id;
                  $dd["capacity_default"] = $line2["DISKSIZE"];
                  $DeviceHardDrive        = new DeviceHardDrive();
                  $dd_id                  = $DeviceHardDrive->import($dd);
                  if ($dd_id) {
                     $found = false;
                     foreach ($tab as $id => $curr) {
                        if ($curr['deviceharddrives_id'] == $dd_id) {
                           unset($tab[$id]);
                           $found = true;
                           break;
                        }
                     }
                     if ($found) {
                        $CompDevice->update(['id'                  => $CompDevice->getID(),
                                             'items_id'            => $computers_id,
                                             'itemtype'            => 'Computer',
                                             'entities_id'         => $entities_id,
                                             'deviceharddrives_id' => $dd_id,
                                             'serial'              => $line2["SERIALNUMBER"],
                                             'capacity'            => $line2["DISKSIZE"],
                                             'is_dynamic'          => 1], $install_history);
                     } else {
                        $CompDevice->add(['items_id'            => $computers_id,
                                          'itemtype'            => 'Computer',
                                          'deviceharddrives_id' => $dd_id,
                                          'serial'              => $line2["SERIALNUMBER"],
                                          'capacity'            => $line2["DISKSIZE"],
                                          'is_dynamic'          => 1,
                                          'entities_id'         => $entities_id], [], $install_history);
                     }
                  }
               }
            }
            break;

         case "Item_DeviceDrive":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }

            $CompDevice = new $devicetype();
            $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                         'itemtype'    => 'Computer',
                                         'entities_id' => $entities_id,
                                         'is_dynamic'  => 1]);
            //lecteurs
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if (empty($line2["DISKSIZE"]) || !preg_match("/disk/i", $line2["TYPE"])) {
                  if ($line2["NAME"]) {
                     $stor["designation"] = $line2["NAME"];
                  } else {
                     if ($line2["MODEL"]) {
                        $stor["designation"] = $line2["MODEL"];
                     } else {
                        $stor["designation"] = "Unknown";
                     }
                  }
                  $stor["entities_id"] = $entities_id;
                  $DeviceDrive         = new DeviceDrive();
                  $stor_id             = $DeviceDrive->import($stor);
                  if ($stor_id) {
                     $found = false;
                     foreach ($tab as $id => $curr) {
                        if ($curr['devicedrives_id'] == $stor_id) {
                           unset($tab[$id]);
                           $found = true;
                           break;
                        }
                     }
                     if ($found) {
                        $CompDevice->update(['id'              => $CompDevice->getID(),
                                             'items_id'        => $computers_id,
                                             'itemtype'        => 'Computer',
                                             'entities_id'     => $entities_id,
                                             'devicedrives_id' => $stor_id,
                                             'is_dynamic'      => 1], $install_history);
                     } else {
                        $CompDevice->add(['items_id'        => $computers_id,
                                          'itemtype'        => 'Computer',
                                          'devicedrives_id' => $stor_id,
                                          'is_dynamic'      => 1,
                                          'entities_id'     => $entities_id], [], $install_history);
                     }
                  }
               }
            }

            break;

         case "Item_DevicePci":
            if (isset($ocsComputer['MODEMS'])) {

               if ($force) {
                  self::resetDevices($computers_id, $devicetype, $uninstall_history);
               }

               $CompDevice = new $devicetype();
               $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                            'itemtype'    => 'Computer',
                                            'entities_id' => $entities_id,
                                            'is_dynamic'  => 1]);
               //Modems
               foreach ($ocsComputer['MODEMS'] as $line2) {
                  $line2              = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                  $mdm["designation"] = $line2["NAME"];
                  $mdm["entities_id"] = $entities_id;
                  if (!empty($line2["DESCRIPTION"])) {
                     $mdm["comment"] = $line2["TYPE"] . "\r\n" . $line2["DESCRIPTION"];
                  }
                  $DevicePci = new DevicePci();
                  $mdm_id    = $DevicePci->import($mdm);
                  if ($mdm_id) {
                     $found = false;
                     foreach ($tab as $id => $curr) {
                        if ($curr['devicepcis_id'] == $mdm_id) {
                           unset($tab[$id]);
                           $found = true;
                           break;
                        }
                     }
                     if ($found) {
                        $CompDevice->update(['id'            => $CompDevice->getID(),
                                             'items_id'      => $computers_id,
                                             'itemtype'      => 'Computer',
                                             'entities_id'   => $entities_id,
                                             'devicepcis_id' => $mdm_id,
                                             'is_dynamic'    => 1], $install_history);
                     } else {
                        $CompDevice->add(['items_id'      => $computers_id,
                                          'itemtype'      => 'Computer',
                                          'devicepcis_id' => $mdm_id,
                                          'is_dynamic'    => 1,
                                          'entities_id'   => $entities_id], [], $install_history);
                     }
                  }
               }
            }
            //Ports
            if (isset($ocsComputer['PORTS'])) {

               if ($force) {
                  self::resetDevices($computers_id, $devicetype, $uninstall_history);
               }
               $CompDevice = new $devicetype();
               $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                            'itemtype'    => 'Computer',
                                            'entities_id' => $entities_id,
                                            'is_dynamic'  => 1]);

               foreach ($ocsComputer['PORTS'] as $line2) {
                  $line2               = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                  $port["designation"] = "";
                  if ($line2["TYPE"] != "Other") {
                     $port["designation"] .= $line2["TYPE"];
                  }
                  if ($line2["NAME"] != "Not Specified") {
                     $port["designation"] .= " " . $line2["NAME"];
                  } else if ($line2["CAPTION"] != "None") {
                     $port["designation"] .= " " . $line2["CAPTION"];
                  }
                  $port["entities_id"] = $entities_id;
                  if (!empty($port["designation"])) {
                     if (!empty($line2["DESCRIPTION"]) && $line2["DESCRIPTION"] != "None") {
                        $port["comment"] = $line2["DESCRIPTION"];
                     }
                     $DevicePci = new DevicePci();
                     $port_id   = $DevicePci->import($port);
                     if ($port_id) {
                        $found = false;
                        foreach ($tab as $id => $curr) {
                           if ($curr['devicepcis_id'] == $port_id) {
                              unset($tab[$id]);
                              $found = true;
                              break;
                           }
                        }
                        if ($found) {
                           $CompDevice->update(['id'            => $CompDevice->getID(),
                                                'items_id'      => $computers_id,
                                                'itemtype'      => 'Computer',
                                                'entities_id'   => $entities_id,
                                                'devicepcis_id' => $port_id,
                                                'is_dynamic'    => 1], $install_history);
                        } else {
                           $CompDevice->add(['items_id'      => $computers_id,
                                             'itemtype'      => 'Computer',
                                             'devicepcis_id' => $port_id,
                                             'is_dynamic'    => 1,
                                             'entities_id'   => $entities_id], [], $install_history);
                        }
                     }
                  }
               }
            }
            //Slots
            if (isset($ocsComputer['SLOTS'])) {

               if ($force) {
                  self::resetDevices($computers_id, $devicetype, $uninstall_history);
               }

               $CompDevice = new $devicetype();
               $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                            'itemtype'    => 'Computer',
                                            'entities_id' => $entities_id,
                                            'is_dynamic'  => 1]);
               foreach ($ocsComputer['SLOTS'] as $line2) {
                  $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                  if ($line2['NAME']) {
                     if (!$ocs_db_utf8 && !Toolbox::seems_utf8($line2["NAME"])) {
                        $line2["NAME"] = Toolbox::encodeInUtf8($line2["NAME"]);
                     }
                     $pci["entities_id"] = $entities_id;
                     $pci["designation"] = $line2["NAME"];
                     if (!empty($line2["DESCRIPTION"])) {
                        $pci["comment"] = $line2["DESCRIPTION"];
                     }
                     $DevicePci = new DevicePci();
                     $pci_id    = $DevicePci->import($pci);
                     if ($pci_id) {
                        $found = false;
                        foreach ($tab as $id => $curr) {
                           if ($curr['devicepcis_id'] == $pci_id) {
                              unset($tab[$id]);
                              $found = true;
                              break;
                           }
                        }
                        if ($found) {
                           $CompDevice->update(['id'            => $CompDevice->getID(),
                                                'items_id'      => $computers_id,
                                                'itemtype'      => 'Computer',
                                                'entities_id'   => $entities_id,
                                                'devicepcis_id' => $pci_id,
                                                'is_dynamic'    => 1], $install_history);
                        } else {
                           $CompDevice->add(['items_id'      => $computers_id,
                                             'itemtype'      => 'Computer',
                                             'devicepcis_id' => $pci_id,
                                             'is_dynamic'    => 1,
                                             'entities_id'   => $entities_id], [], $install_history);
                        }
                     }
                  }
               }
            }
            break;
         case "Item_DeviceProcessor":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }
            $KnownDevice = new $devicetype();
            $tab    = $KnownDevice->find(['items_id'    => $computers_id,
                                               'itemtype'    => 'Computer',
                                               'entities_id' => $entities_id,
                                               'is_dynamic'  => 1]);
            $CompDevice  = new $devicetype();
            //Processeurs:
            foreach ($ocsComputer as $line2) {
               $line2                    = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               $processor                = [];
               $processor["designation"] = $line2["TYPE"];
               if (!is_numeric($line2["SPEED"])) {
                  $line2["SPEED"] = 0;
               }
               $processor["manufacturers_id"]  = Dropdown::importExternal('Manufacturer',
                                                                          PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8, $line2["MANUFACTURER"]));
               $processor["frequency_default"] = $line2["SPEED"];
               $processor["nbcores_default"]   = $line2["CORES"];
               $processor["nbthreads_default"] = $line2["LOGICAL_CPUS"];
               $processor["frequence"]         = $line2["CURRENT_SPEED"];
               $processor["entities_id"]       = $entities_id;
               $DeviceProcessor                = new DeviceProcessor();
               $proc_id                        = $DeviceProcessor->import($processor);
               if ($proc_id) {
                  $found = false;
                  foreach ($tab as $id => $curr) {
                     if ($curr['deviceprocessors_id'] == $proc_id &&
                         +$curr['nbcores'] == $processor["nbcores_default"]) {
                        unset($tab[$id]);
                        $found = true;
                        break;
                     }
                  }
                  if ($found) {
                     $CompDevice->update(['id'                  => $CompDevice->getID(),
                                          'items_id'            => $computers_id,
                                          'itemtype'            => 'Computer',
                                          'entities_id'         => $entities_id,
                                          'deviceprocessors_id' => $proc_id,
                                          'frequency'           => $line2["SPEED"],
                                          'is_dynamic'          => 1], $install_history);
                  } else {
                     $CompDevice->add(['items_id'            => $computers_id,
                                       'itemtype'            => 'Computer',
                                       'deviceprocessors_id' => $proc_id,
                                       'is_dynamic'          => 1,
                                       'frequency'           => $line2["SPEED"],
                                       'entities_id'         => $entities_id], [], $install_history);
                  }
               }
            }
            break;

         case "Item_DeviceNetworkCard":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }
            //Carte reseau
            PluginOcsinventoryngNetworkPort::importNetwork($params['cfg_ocs'], $ocsComputer,
                                                           $computers_id, $entities_id);
            break;

         case "Item_DeviceGraphicCard":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }

            $CompDevice = new $devicetype();
            $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                         'itemtype'    => 'Computer',
                                         'entities_id' => $entities_id,
                                         'is_dynamic'  => 1]);
            //carte graphique
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if ($line2['NAME']) {
                  $video["designation"] = $line2["NAME"];
                  $video["entities_id"] = $entities_id;
                  if (!is_numeric($line2["MEMORY"])) {
                     $line2["MEMORY"] = 0;
                  }
                  $video["memory_default"] = $line2["MEMORY"];
                  $DeviceGraphicCard       = new DeviceGraphicCard();
                  $video_id                = $DeviceGraphicCard->import($video);
                  if ($video_id) {
                     $found = false;
                     foreach ($tab as $id => $curr) {
                        if ($curr['devicegraphiccards_id'] == $video_id) {
                           unset($tab[$id]);
                           $found = true;
                           break;
                        }
                     }
                     if ($found) {
                        $CompDevice->update(['id'                    => $CompDevice->getID(),
                                             'items_id'              => $computers_id,
                                             'itemtype'              => 'Computer',
                                             'entities_id'           => $entities_id,
                                             'devicegraphiccards_id' => $video_id,
                                             'memory'                => $line2["MEMORY"],
                                             'is_dynamic'            => 1], $install_history);
                     } else {
                        $CompDevice->add(['items_id'              => $computers_id,
                                          'itemtype'              => 'Computer',
                                          'devicegraphiccards_id' => $video_id,
                                          'is_dynamic'            => 1,
                                          'memory'                => $line2["MEMORY"],
                                          'entities_id'           => $entities_id], [], $install_history);
                     }
                  }
               }
            }
            break;

         case "Item_DeviceSoundCard":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }

            $CompDevice = new $devicetype();
            $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                         'itemtype'    => 'Computer',
                                         'entities_id' => $entities_id,
                                         'is_dynamic'  => 1]);
            //carte son
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if ($line2['NAME']) {
                  if (!$ocs_db_utf8 && !Toolbox::seems_utf8($line2["NAME"])) {
                     $line2["NAME"] = Toolbox::encodeInUtf8($line2["NAME"]);
                  }
                  $snd["entities_id"] = $entities_id;
                  $snd["designation"] = $line2["NAME"];
                  if (!empty($line2["DESCRIPTION"])) {
                     $snd["comment"] = $line2["DESCRIPTION"];
                  }
                  $DeviceSoundCard = new DeviceSoundCard();
                  $snd_id          = $DeviceSoundCard->import($snd);
                  if ($snd_id) {
                     $found = false;
                     foreach ($tab as $id => $curr) {
                        if ($curr['devicesoundcards_id'] == $snd_id) {
                           unset($tab[$id]);
                           $found = true;
                           break;
                        }
                     }
                     if ($found) {
                        $CompDevice->update(['id'                  => $CompDevice->getID(),
                                             'items_id'            => $computers_id,
                                             'itemtype'            => 'Computer',
                                             'entities_id'         => $entities_id,
                                             'devicesoundcards_id' => $snd_id,
                                             'is_dynamic'          => 1], $install_history);
                     } else {
                        $CompDevice->add(['items_id'            => $computers_id,
                                          'itemtype'            => 'Computer',
                                          'devicesoundcards_id' => $snd_id,
                                          'is_dynamic'          => 1,
                                          'entities_id'         => $entities_id], [], $install_history);
                     }
                  }
               }
            }
            break;
         case "Item_DeviceMotherboard":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }

            $CompDevice = new $devicetype();
            $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                         'itemtype'    => 'Computer',
                                         'entities_id' => $entities_id,
                                         'is_dynamic'  => 1]);
            //Motherboard
            $mb["designation"] = $ocsComputer["MMODEL"];

            $mb["entities_id"]      = $entities_id;
            $mb["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
                                                               PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8, $ocsComputer["MMANUFACTURER"]));

            $DeviceMB              = new DeviceMotherboard();
            $devicemotherboards_id = $DeviceMB->import($mb);
            $serial                = $ocsComputer["MSN"];
            if ($devicemotherboards_id) {
               $found = false;
               foreach ($tab as $id => $curr) {
                  if ($curr['devicemotherboards_id'] == $devicemotherboards_id) {
                     unset($tab[$id]);
                     $found = true;
                     break;
                  }
               }
               if ($found) {
                  $CompDevice->update(['id'                    => $CompDevice->getID(),
                                       'items_id'              => $computers_id,
                                       'itemtype'              => 'Computer',
                                       'entities_id'           => $entities_id,
                                       'devicemotherboards_id' => $devicemotherboards_id,
                                       'serial'                => $serial,
                                       'is_dynamic'            => 1], $install_history);
               } else {
                  $CompDevice->add(['items_id'              => $computers_id,
                                    'itemtype'              => 'Computer',
                                    'devicemotherboards_id' => $devicemotherboards_id,
                                    'is_dynamic'            => 1,
                                    'serial'                => $serial,
                                    'entities_id'           => $entities_id], [], $install_history);
               }
            }

            break;
         case "Item_DeviceControl":

            if ($force) {
               self::resetDevices($computers_id, $devicetype, $uninstall_history);
            }
            //controllers
            $CompDevice = new $devicetype();
            $tab    = $CompDevice->find(['items_id'    => $computers_id,
                                         'itemtype'    => 'Computer',
                                         'entities_id' => $entities_id,
                                         'is_dynamic'  => 1]);

            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if ($line2['NAME']) {
                  if (!$ocs_db_utf8 && !Toolbox::seems_utf8($line2["NAME"])) {
                     $line2["NAME"] = Toolbox::encodeInUtf8($line2["NAME"]);
                  }
                  $ctrl["entities_id"] = $entities_id;
                  $ctrl["designation"] = $line2["NAME"];
                  //TODO : OCS TYPE = IDE Controller
                  // GLPI : interface = IDE
                  //$ctrl["interfacetypes_id"] = $line2["TYPE"];
                  $ctrl["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
                                                                       PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8,
                                                                                                                           $line2["MANUFACTURER"]));
                  if (!empty($line2["DESCRIPTION"])) {
                     $ctrl["comment"] = $line2["DESCRIPTION"];
                  }
                  $DeviceControl = new DeviceControl();
                  $ctrl_id       = $DeviceControl->import($ctrl);
                  if ($ctrl_id) {
                     $found = false;
                     foreach ($tab as $id => $curr) {
                        if ($curr['devicecontrols_id'] == $ctrl_id) {
                           unset($tab[$id]);
                           $found = true;
                           break;
                        }
                     }
                     if ($found) {
                        $CompDevice->update(['id'                => $CompDevice->getID(),
                                             'items_id'          => $computers_id,
                                             'itemtype'          => 'Computer',
                                             'entities_id'       => $entities_id,
                                             'devicecontrols_id' => $ctrl_id,
                                             'is_dynamic'        => 1], $install_history);
                     } else {
                        $CompDevice->add(['items_id'          => $computers_id,
                                          'itemtype'          => 'Computer',
                                          'entities_id'       => $entities_id,
                                          'devicecontrols_id' => $ctrl_id,
                                          'is_dynamic'        => 1], [], $install_history);
                     }
                  }

               }
            }
            break;
      }
      unset($ocsComputer);
   }

   /**
    * Delete old devices settings
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $itemtype integer : device type identifier.
    *
    * @param $history
    *
    * @return void .
    */
   static function resetDevices($glpi_computers_id, $itemtype, $history) {

      $item = new $itemtype();
      $item->deleteByCriteria(['items_id'   => $glpi_computers_id,
                               'itemtype'   => 'Computer',
                               'is_dynamic' => 1], 1, $history);

   }
}
