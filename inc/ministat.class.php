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

/**
 * Class PluginOcsinventoryngMiniStat
 */
class PluginOcsinventoryngMiniStat {

   /**
    * @var int
    */
   public $Min = 0;
   /**
    * @var int
    */
   public $Max = 0;
   /**
    * @var int
    */
   public $Tot = 0;
   /**
    * @var int
    */
   public $Nb = 0;

   /**
    *
    */
   function Reset() {
      $this->Min = $this->Max = $this->Tot = $this->Nb = 0;
   }

   /**
    * @return int
    */
   function GetMinimum() {
      return $this->Min;
   }

   /**
    * @return int
    */
   function GetMaximum() {
      return $this->Max;
   }

   /**
    * @return int
    */
   function GetTotal() {
      return $this->Tot;
   }

   /**
    * @return int
    */
   function GetCount() {
      return $this->Nb;
   }

   /**
    * @return float|int
    */
   function GetAverage() {
      return $this->Nb > 0 ? $this->Tot / $this->Nb : 0;
   }

   /**
    * @param $Value
    */
   function AddValue($Value) {

      if ($this->Nb > 0) {
         if ($Value < $this->Min) {
            $this->Min = $Value;
         }
         if ($Value > $this->Max) {
            $this->Max = $Value;
         }
         $this->Tot += $Value;
         $this->Nb++;
      } else {
         $this->Min = $this->Max = $this->Tot = $Value;
         $this->Nb  = 1;
      }
   }

}
