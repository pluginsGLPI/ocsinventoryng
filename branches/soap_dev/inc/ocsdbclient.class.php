<?php

class PluginOcsinventoryngOcsDbClient extends PluginOcsinventoryngOcsClient {
	/**
	 * @var DBmysql
	 */
	private $db;
	
	public function __construct($id, $dbhost, $dbuser ,$dbpassword, $dbdefault){
		parent::__construct($id);
		
		$this->db = new PluginOcsinventoryngDBocs($dbhost, $dbuser ,$dbpassword, $dbdefault);
	}
	
	public function getDB(){
		return $this->db;
	}
	



	/**********************/
	/* PRIVATE  FUNCTIONS */
	/**********************/
	private function parseArguments($conditions,$sort=null){
		$params="";
		if ($conditions === 1 ) {
			$params = " WHERE '1' ";
		} else {
			foreach ($conditions as $key => $value){
				if (count($value) >0) {
					$comparateur = ($key) ? " != " : " = " ;
					foreach ($value as $id => $equals) {
						if (!empty($params)){
							$params .= " AND `$id` $comparateur '".$this->db->escape($equals)."' ";
						}
						else{
							$params = " WHERE `$id` $comparateur '".$this->db->escape($equals)."' ";
						}
					}
				}
			}

		}
		if (!empty($sort))
			$params .= "ORDER BY '".$this->db->escape($sort)."'";
		return $params;	
	}

	private function getComputerSections($ids,$checksum, $wanted) {
		if(!isset($checksum)){
			$checksum=self::CHECKSUM_HARDWARE;
		}
		if (!isset($wanted)){
			$wanted = self::WANTED_ACCOUNTINFO;
		}
		$OCS_MAP = self::getOcsMap();
		$tables= array();
		foreach ($OCS_MAP as $table => $check) {
			if($table == "accountinfo"){
				if (self::WANTED_ACCOUNTINFO & $wanted){
					$tables[]=$table;	
				}
			} 
			elseif ($table == "dico_soft"){
				if (self::WANTED_DICO_SOFT & $wanted){
					$tables[]=$table;	
				}
			}  
			else{
				if ($check & $checksum){
					$tables[]=$table;		
				}
			}

		}

		//$query = "SELECT * FROM ".implode(',',$tables)." WHERE accountinfo.HARDWARE_ID=hardware.ID AND ID IN (".implode(',',$ids).")";

		$request = $this->db->queryOrDie($query);
		while ($computer = $this->db->fetch_assoc($request)) {
			$computers[]=$computer;


		}


		return $computers;


	}




	/**********************/
	/* PUBLIC  FUNCTIONS  */
	/**********************/


	/**
	 * @see PluginOcsinventoryngOcsClient::checkConnection()
	 */
	public function checkConnection(){
		return $this->db->connected;
	}

	/**
	 * @see PluginOcsinventoryngOcsClient::searchComputers()
	 */
	public function searchComputers($field, $value){
		
		if($field== "id"){
			$options = array("FILTER"=>array('IDS' => array($value) ));
		}
		elseif ($field == "tag") {
			$options = array("FILTER"=>array('TAGS' => array($value) ));
		}
		$res = $this->getComputers($options);
		return $res;
	}



/**
	 * Returns a list of computers
	 *
	 * @param array $options Possible options :
	 * 		array(
	 * 			'OFFSET' => int,
	 * 			'MAX_RECORDS' => int,
	 * 			'FILTER' => array(						// filter the computers to return
	 * 				'IDS' => array(int),				// list of computer ids to select
	 * 				'EXCLUDE_IDS' => array(int),		// list of computer ids to exclude
	 * 				'TAGS' => array(string),			// list of computer tags to select
	 * 				'EXCLUDE_TAGS' => array(string),	// list of computer tags to exclude
	 * 				'CHECKSUM' => int					// filter which sections have been modified (see CHECKSUM_* constants)
	 * 			),
	 * 			'DISPLAY' => array(		// select which sections of the computers to return
	 * 				'CHECKSUM' => int,	// inventory sections to return (see CHECKSUM_* constants)
	 * 				'WANTED' => int		// special sections to return (see WANTED_* constants)
	 * 			)
	 * 		)
	 * 
	 * @return array List of computers :
	 * 		array (
	 * 			array (
	 * 				'META' => array(
	 * 					'ID' => ...
	 * 					'CHECKSUM' => ...
	 * 					'DEVICEID' => ...
	 * 					'LASTCOME' => ...
	 * 					'LASTDATE' => ...
	 * 					'NAME' => ...
	 * 					'TAG' => ...
	 * 				),
	 * 				'SECTION1' => array(...),
	 * 				'SECTION2' => array(...),
	 * 				...
	 * 			),
	 * 			...
	 * 		)
	 */

public function getComputers($options){
	if(isset($options['OFFSET'])){
		$offset="OFFSET  ".$options['OFFSET'];
	}
	else{
		$offset="";
	}
	if (isset($options['MAX_RECORDS'])) {
		$max_records= "LIMIT  ".$options['MAX_RECORDS'];
	}
	else{
		$max_records= "";
	}
	if (isset($options['ORDER'])) {
		$order = $options['ORDER'];
	}
	else{
		$order = " LASTDATE ";
	}
	if (isset($options['FILTER'])) {
		$filters=$options['FILTER'];

		if (isset($filters['IDS'])) {
			$ids = $filters['IDS'];
			$where_ids =" AND hardware.ID IN ";
			$where_ids = join(',', $ids);
		}
		else{
			$ids= array();
		}
		if (isset($filters['EXCLUDE_IDS'])) {
			$exclude_ids=$filters['EXCLUDE_IDS'];
			$where_exclude_ids =" AND hardware.ID NOT IN ";
			$where_exclude_ids = join(',', $exclude_ids);
		}
		else{
			$exclude_ids= array();
		}
		if (isset($filters['TAGS'])) {
			$tags=$filters['TAGS'];
			$where_tags =" AND accountinfo.TAG IN ";
			$where_tags = join(',', $this->db->escape($tags));
		}
		else{
			$tags= array();
		}
		if (isset($filters['EXCLUDE_TAGS'])) {
			$exclude_tags=$filters['EXCLUDE_TAGS'];
			$where_exclude_tags =" AND accountinfo.TAG NOT IN ";
			$where_exclude_tags = join(',', $this->db->escape($exclude_tags));
		}
		else{
			$exclude_tags= array();
		}
		if (isset($filters['CHECKSUM'])) {
			$checksum=$filters['CHECKSUM'];
			$where_checksum =" AND ('.$checksum.' & hardware.CHECKSUM)' ";
		}
		else{
			$checksum= array();
		}
	}
	else{
		$where_condition="";
	}

	$query = "SELECT DISTINCT hardware.ID FROM hardware, accountinfo
	WHERE hardware.DEVICEID NOT LIKE '\\_%'
	AND hardware.ID = accountinfo.HARDWARE_ID
	$where_condition
	$max_records  $offset 
	ORDER BY $order
	";
	$request = $this->db->queryOrDie($query);
	while ($hardwareid = $this->db->fetch_assoc($request)) {
		$hardwareids[]=$hardwareid['ID'];
	}


	
	if (isset($options['DISPLAY']['CHECKSUM'])) {
		$checksum = $options['DISPLAY']['CHECKSUM'];
	}
	if (isset($options['DISPLAY']['WANTED'])) {
		$wanted = $options['DISPLAY']['WANTED'];
	}

	$res = $this->getComputerSections($hardwareids,$checksum,$wanted);
	return $res;
}



	/**
	 * @see PluginOcsinventoryngOcsClient::getAccountInfo()
	 */
	public function getAccountInfo($id){
		$query = "SELECT * FROM `accountinfo` WHERE `HARDWARE_ID` = '".$id."'";
		$accountinfo = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($accountinfo);
		return $res;
	}




	/**
	 * @see PluginOcsinventoryngOcsClient::getConfig()
	 */
	public function getConfig($key){
		$query = "SELECT `IVALUE`, `TVALUE` FROM `config` WHERE `NAME` = '".$this->db->escape($key)."'";
		$config = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($config);
		return $res;
	}

	/**
	 * @see PluginOcsinventoryngOcsClient::setConfig()
	 */
	public function setConfig($key, $ivalue, $tvalue){
		$query = "UPDATE `config` SET `IVALUE` = '".$ivalue."', `TVALUE` = '".$this->db->escape($tvalue)."' WHERE `NAME` = '".$this->db->escape($key)."'";
		$this->db->query($query);
	}



	/**
	 * @see PluginOcsinventoryngOcsClient::getCategorie()
	 */
	public function getCategorie($table, $condition=array(), $sort=null){	
		$query = "SELECT * FROM `".$table."` ";
		$params = $this->parseArguments($condition,$sort);
		$query .= $params;
		$categorie = $this->db->queryOrDie($query);
		while ($cat = $this->db->fetch_assoc($categorie)) {
			$res[]=$cat;
		}
		if (isset($res)) {
			if (count($res) == 1) {
				$res = $res[0];
			}
		}
		
		return $res;
	}

	public function getUnique($columns, $table, $conditions, $sort){

		


	}

	/**
	 * @see PluginOcsinventoryngOcsClient::setChecksum()
	 */
	public function setChecksum($checksum, $id){
		$query = "UPDATE `hardware` SET `CHECKSUM` = '".$checksum."' WHERE `ID` = '".$id."'";
		$checksum = $this->db->queryOrDie($query);
	}
	
	/**
	 * @see PluginOcsinventoryngOcsClient::getChecksum()
	 */
	public function getChecksum($id) {
		$query = "SELECT `CHECKSUM` FROM `hardware` WHERE `ID` = '".$id."'";
		$checksum = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($checksum);
		return $res["CHECKSUM"];
	}



	/**
	 * @see PluginOcsinventoryngOcsClient::getDeletedComputers()
	 */
	public function getDeletedComputers(){
		$query = "SELECT `DATE`,`DELETED`,`EQUIVALENT` FROM `deleted_equiv` ORDER BY `DATE`,`DELETED` ";
		$deleted = $this->db->queryOrDie($query);
		while ($del =  $this->db->fetch_assoc($deleted)) {
			$computers[]=$del;
		}
		if(isset($computers)){
			foreach ($computers as $computer) {
				$res[$computer['DELETED']] = $computer['EQUIVALENT'];
			}
		}
		else
		{
			$res = array();

		}
		return $res;
	}




	public function removeDeletedComputers($deleted, $equivclean = null){
		$query = "DELETE FROM `deleted_equiv` WHERE `DELETED` = '".$this->db->escape($deleted)."'' ";
		
		if (empty($equivclean)) {
			$equiv_clean=" (`EQUIVALENT` = '".$this->db->escape($equiv_clean)."' OR `EQUIVALENT` IS NULL ) ";
			
		}
		else {
			$equiv_clean=" `EQUIVALENT` = '".$this->db->escape($equiv_clean)."'";
		}
		$query .= $equiv_clean;
		$delete = $this->db->queryOrDie($query);
		$res = $delete;
		return $res;
	}



	/**
	 * @see PluginOcsinventoryngOcsClient::getAccountInfoColumns()
	 */
	public function getAccountInfoColumns(){
		$query = "SHOW COLUMNS FROM `accountinfo`";
		$columns = $this->db->queryOrDie($query);
		while ($column = $this->db->fetch_assoc($columns)) {
			$res[]=$column['Field'];
		}
		return $res;
	}

}
?>



















