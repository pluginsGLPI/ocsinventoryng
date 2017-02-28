<?php

include('../../../inc/includes.php');

$plug = new Plugin;

if( $plug->getFromDBbyDir('ocsinventoryng') && in_array( $plug->fields['state'], [Plugin::NOTUPDATED, Plugin::NOTINSTALLED] ) ) {
   $plug->install( $plug->getID() );
}

