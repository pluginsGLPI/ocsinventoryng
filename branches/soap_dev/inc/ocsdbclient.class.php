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
	private function parseConditions($conditions){
		
		if ($conditions === 1 ) {
			$params = " WHERE 1 ";
		} else {
			foreach ($conditions as $key => $value){
				if (count($value) >0) {
					$comparateur = ($key) ? " != " : " = " ;
					foreach ($value as $id => $equals) {
						if (!empty($params)){
							$params .= " AND $id $comparateur '".$this->db->escape($equals)."' ";
						}
						else{
							$params = " WHERE $id $comparateur '".$this->db->escape($equals)."' ";
						}
					}
				}
			}

		}
		return $params;	
	}

	private function getTag($id){
		$query = "SELECT TAG FROM `accountinfo` WHERE HARDWARE_ID = $id";
		$tag = $this->db->queryOrDie($query);
		$tag =  $this->db->fetch_assoc($tag);
		return $tag['TAG'];
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
		return $this->getComputers(array(array($field=>$value)));



	}

	/**
	 * @see PluginOcsinventoryngOcsClient::getComputers()
	 */
	public function getComputers($conditions=array(),$sort=NULL){
		$query = "SELECT * FROM `hardware` " ;

		$params = $this->parseConditions($conditions);
		if (!empty($sort))
			$params .= "ORDER BY $sort";
		$query .= $params;

		$computers = $this->db->queryOrDie($query);
		while ($computer = $this->db->fetch_assoc($computers)) {
			$computer['TAG']= $this->getTag($computer['ID']);
			$res[]=$computer;
		}
		if (count($res) == 1) {
			$res = $res[0];
		}
		return $res;
	}


	/**
	 * @see PluginOcsinventoryngOcsClient::getAccountInfo()
	 */
	public function getAccountInfo($id){
		$query = "SELECT * FROM `accountinfo` WHERE HARDWARE_ID = $id";
		$accountinfo = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($accountinfo);
		return $res;
	}




	/**
	 * @see PluginOcsinventoryngOcsClient::getConfig()
	 */
	public function getConfig($key){
		$query = "SELECT IVALUE, TVALUE FROM `config` WHERE NAME = \"$key\"";
		$config = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($config);
		return $res;
	}

	/**
	 * @see PluginOcsinventoryngOcsClient::setConfig()
	 */
	public function setConfig($key, $ivalue, $tvalue){
		$query = "UPDATE `config` SET IVALUE = \"$ivalue\", TVALUE = \"$tvalue\" WHERE NAME = \"$key\"";
		$this->db->query($query);
	}



	/**
	 * @see PluginOcsinventoryngOcsClient::getCategorie()
	 */
	public function getCategorie($table, $condition=1, $sort){	
		$query = "SELECT * FROM $table ";
		$params = "" ;
		$params = $this->parseConditions($condition);
		if (!empty($sort))
			$params .= " ORDER BY $sort";
		$query .= $params;
		$categorie = $this->db->queryOrDie($query);
		while ($cat = $this->db->fetch_assoc($categorie)) {
			$res[]=$cat;
		}
		if (count($res) == 1) {
			$res = $res[0];
		}
		return $res;
	}

	public function getUnique($columns, $table, $conditions, $sort){

		


	}

	/**
	 * @see PluginOcsinventoryngOcsClient::setChecksum()
	 */
	public function setChecksum($checksum, $id){
		$query = "UPDATE `hardware` SET CHECKSUM = $checksum WHERE ID = $id";
		$checksum = $this->db->queryOrDie($query);
		$res = $checksum;
		return $res;
	}
	
	/**
	 * @see PluginOcsinventoryngOcsClient::getChecksum()
	 */
	public function getChecksum($id) {
		$query = "SELECT CHECKSUM FROM `hardware` WHERE ID = $id";
		$checksum = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($checksum);
		return $res["CHECKSUM"];
	}



	/**
	 * @see PluginOcsinventoryngOcsClient::getDeletedComputers()
	 */
	public function getDeletedComputers(){
		$query = "SELECT DATE,DELETED,EQUIVALENT FROM `deleted_equiv` ORDER BY DATE,DELETED ";
		$deleted = $this->db->queryOrDie($query);
		while ($del =  $this->db->fetch_assoc($deleted)) {
			$computers[]=$del;
		}
		foreach ($computers as $computer) {
			$res[$computer['DELETED']] = $computer['EQUIVALENT'];
		}
		return $res;
	}




	public function removeDeletedComputers($deleted, $equivclean = null){
		$query = "DELETE FROM `deleted_equiv` WHERE DELETED = \"$deleted\" ";
		 if (empty($equivclean)) {
                  $equiv_clean=" (`EQUIVALENT` = '$equiv'
                                  OR `EQUIVALENT` IS NULL ) ";
       }
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
		$res;
		return $res;
	}



	//Not sure to be used as is 
/*	public function getComputerSections(){//($ids, $checksum = self::CHECKSUM_ALL, $wanted = self::WANTED_ALL) {



		$DATA_MAP= array(	
							'hardware' => 1,	
							'bios' =>  2,
							'memories' => 4,
							'slots' => 8,
							'registry' => 16,
							'controllers' => 32,
							'monitors' => 64,
							'ports' => 128,
							'storages' => 256,
							'drives' => 512,
							'inputs' => 1024,
							'modems' => 2048,
							'networks' => 4096,
							'printers' => 8192,
							'sounds' => 16384,
							'videos' => 32768,
							'softwares' => 65536,
							'virtualmachines' => 131072,
							'cpus' => 262144,
							'sim' => 524288,
							'accountinfo' => 0,
							'dico_soft' => 0, 
						);

		foreach ($DATA_MAP as $table => $check ) {
			if($table == "accountinfo"){

			} elseif ($table == "dico_soft"){
				
			} elseif ($table == "hardware"){		
			
			} else{



			}

		}




	}
*/



}
?>