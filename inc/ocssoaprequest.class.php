<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2022 by the ocsinventoryng Development Team.

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
 * Class PluginOcsinventoryngOcsSoapRequest
 */
class PluginOcsinventoryngOcsSoapRequest {
   /**
    * @var mixed
    */
   private $params;

   /**
    * @param mixed $params
    */
   public function __construct($params) {
      $this->params = $params;
   }

   /**
    * @return string
    */
   public function toXml() {
      return $this->_toXml('REQUEST', $this->params);
   }

   /**
    * @param $tagName
    * @param $value
    *
    * @return string
    */
   private function _toXml($tagName, $value) {
      $xml = '';

      if (is_array($value)) {
         if ($this->isIndexed($value)) {
            foreach ($value as $val) {
               $xml .= $this->_toXml($tagName, $val);
            }
         } else {
            $xml .= "<$tagName>";
            foreach ($value as $key => $val) {
               $xml .= $this->_toXml($key, $val);
            }
            $xml .= "</$tagName>";
         }
      } else {
         $xml .= "<$tagName>$value</$tagName>";
      }

      return $xml;
   }

   /**
    * @param $array
    *
    * @return bool
    */
   private function isIndexed($array) {
      return (bool)count(array_filter(array_keys($array), 'is_numeric'));
   }
}
