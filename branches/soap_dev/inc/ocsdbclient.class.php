<?php

class PluginOcsinventoryngOcsDbClient extends PluginOcsinventoryngOcsClient {
	/**
	 * @var DBmysql
	 */
	private $db;
	
	public function __construct($id, $dbhost, $dbuser ,$dbpassword, $dbdefault) {
		parent::__construct($id);
		
		$this->db = new PluginOcsinventoryngDBocs($dbhost, $dbuser ,$dbpassword, $dbdefault);
	}
	
	public function getDB(){
		return $this->db;
	}
	
	/**
	 * @see PluginOcsinventoryngOcsClient::checkConnection()
	 */
	public function checkConnection() {
		return $this->db->connected;
	}

	
	public function getComputers($conditions=array(),$sort=NULL){
		$query = "SELECT * FROM `hardware` " ;
		$params = "";
		foreach ($conditions as $key => $value) {
			if (count($value) >0) {
				$comparateur = ($key) ? " != " : " = " ;
				foreach ($value as $id => $equals) {
					if (!empty($params)){
						$params .= " AND $id $comparateur $equals ";
					}
					else{
						$params .= " WHERE $id $comparateur $equals ";
					}
				}
			}
		}	
		if (!empty($sort))
			$params .= "ORDER BY $sort";
		$query .= $params;
		$computers = $this->db->queryOrDie($query);
		while ($computer = $this->db->fetch_assoc($computers)) {
			$res[]=$computer;
		}
		if (count($res) == 1) {
			$res = $res[0];
		}
		return $res;
	}






	public function getAccountInfo($id)
	{
		$query = "SELECT * FROM `accountinfo` WHERE HARDWARE_ID = $id";
		$accountinfo = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($accountinfo);
		return $res;

	}





	public function getConfig($key)
	{
		$query = "SELECT IVALUE, TVALUE FROM `config` WHERE NAME = \"$key\"";
		$config = $this->db->queryOrDie($query);
		$res = $this->db->fetch_assoc($config);
		return $res;
	}

	public function setConfig($key, $ivalue, $tvalue)
	{
		$query = "UPDATE `config` SET IVALUE = \"$ivalue\", TVALUE = \"$tvalue\" WHERE NAME = \"$key\"";
		$this->db->query($query);
	}




	public function getCategorie($table, $condition=1, $sort)
	{	

		$query = "SELECT * FROM $table WHERE ";
		$params = "" ;
		if ($condition == 1 ) {
			$query .= " 1 ";
		} else {
			foreach ($condition as $key => $value) {
				if (count($value) > 0) {
					$comparateur = ($key) ? " != " : " = " ;
					foreach ($value as $id => $equals) {
						if (!empty($params)){
							$params .= " AND $id $comparateur $equals ";
						}
						else{
							$params .= " $id $comparateur $equals ";
						}
					}
				}
			}
		}

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




	public function getUnique($columns, $table, $conditions, $sort)
	{
	
		
	}




	public function setChecksum($checksum, $id)
	{
		$query = "UPDATE `hardware` SET CHECKSUM = $checksum WHERE ID = $id";
		$checksum = $this->db->queryOrDie($query);
		$res = $checksum;
		return $res;
	}







	public function delEquiv($deleted, $equivclean = null)
	{
	#DELETE FROM DELETED_EQUIV WHERE DELETED = $deleted AND $equiv_clean
			$query = "DELETE FROM `deleted_equiv` WHERE DELETED = \"$deleted\" ";
			if (!empty($equivclean))
				$query .= $equivclean;
			$delete = $this->db->queryOrDie($query);
			$res = $delete;
			return $res;
	}




	public function getAccountInfoColumns()
	{
	#SHOW COLUMNS FROM ACCOUNTINFO
		$query = "SHOW COLUMNS FROM `accountinfo`";
		$columns = $this->db->queryOrDie($query);
		while ($column = $this->db->fetch_assoc($columns)) {
			$res[]=$column['Field'];
		}
		$res;
		return $res;
	}



}

	?>