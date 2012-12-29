<?PHP

include_once(CONFIG . "database.config.php");

class sql {

	var $link;
	var $number_of_query = 0;
	var $query = array();
	var $error = array();
	var $data = array();
	var $rows_affected;
	var $last_id;
	var $called_from;
	var $validate = array();
	var $res;
	var $_history_type = HISTORY_TYPE; // default 4 made by system
	var $_history_user = null; // default 4 made by system
	var $_type_query = '';
	var $_table_to_history = '';

	//to be surcharged

	function get_table_to_history() {
		if (HISTORY_ACTIVE)
		{
			$this->_table_to_history = history::get_table_with_history();
		}
	}

	function sql_connect($var1, $var2, $var3) {
		
	}

	function sql_select_db($var1) {
		
	}

	function sql_close() {
		
	}

	function sql_real_escape_string($var1) {
		
	}

	function sql_affected_rows() {
		
	}

	function sql_num_rows($var1) {
		
	}

	function sql_num_fields($res) {
		
	}

	function sql_field_name($result, $i) {
		
	}

	function sql_free_result($result) {

	}

	function _insert_id() {
		
	}

	function _error() {
		
	}

	//function mutualised

	function sql_query($sql, $table = "", $type = "") {

		$this->res = "";

		$this->called_from = debug_backtrace();
		$startmtime = microtime(true);

		if (!$res = $this->_query($sql))
		{
			//error
			die("<br />SQL : $sql<br /><b>" . $this->_error() . "</b>" .
				"<br />FILE : " . $this->called_from[0]['file'] . " LINE : " . $this->called_from[0]['line']);
		}


		$this->res = $res;

		$totaltime = round(microtime(true) - $startmtime, 5);

		$this->query[$this->number_of_query]['query'] = $sql;
		$this->query[$this->number_of_query]['time'] = $totaltime;
		$this->query[$this->number_of_query]['file'] = $this->called_from[0]['file'];
		$this->query[$this->number_of_query]['line'] = $this->called_from[0]['line'];
		$this->rows_affected = $this->sql_affected_rows();
		$this->query[$this->number_of_query]['rows'] = $this->rows_affected;
		$this->query[$this->number_of_query]['last_id'] = $this->_insert_id();

		/* $sql_bis = "insert into gliale_audit_query 
		  SET
		  `date`=now(),
		  `query`='".$this->sql_real_escape_string($sql)."',
		  `time_execution`='".$totaltime."',
		  `affected_rows`='".$this->rows_affected."',
		  `user` ='".@$_SITE['IdUser']."'";

		  $this->_query($sql_bis); */

		$this->number_of_query++;

		return $res;
	}

	function sql_error() {
		return $this->error;
	}

	function sql_save($data = null, $validate = true, $fieldList = array()) {
		unset($this->error);
		$this->error = array();

		$table = array_keys($data);

		$table = $table[0];
		$keys = array_keys($data[$table]);

		$_TABLE = unserialize(file_get_contents(TMP . "database" . DS . $table . ".table.txt"));

		if (!class_exists($table, false))
		{
			include_once APP_DIR . DS . "model" . DS . $table . ".php";
		}

		$validation = Singleton::getInstance("validation");

		$table2 = str_replace("-", "", $table);

		$my_table = Singleton::getInstance($table2);
		$validate = $my_table->get_validate();


		foreach ($keys as $field)
		{
			if (!empty($validate[$field]))
			{
				foreach ($validate[$field] as $rule => $param)
				{
					if (!empty($rule))
					{
						$elem['table'] = $table;
						$elem['field'] = $field;
						$elem['value'] = $data[$table][$field];

						if (in_array("id", $keys, true))
						{
							$elem['id'] = "AND id != " . $data[$table]['id'];
						}

						if (!empty($param[0]))
						{
							$msg_error = $param[0];
						}
						else
						{
							$msg_error = NULL;
						}
						unset($param[0]);

						if (!empty($param))
						{
							if (is_array($param))
							{
								$nb_var = count($param);

								switch ($nb_var)
								{
									case 0: $return = $validation->$rule($elem);
										break;
									case 1: $return = $validation->$rule($elem, $param[1]);
										break;
									case 2: $return = $validation->$rule($elem, $param[1], $param[2]);
										break;
									case 3: $return = $validation->$rule($elem, $param[1], $param[2], $param[3]);
										break;
								}
							}
							else
							{
								$return = $validation->$rule($elem, $param);
							}
						}
						else
						{
							$return = $validation->$rule($elem);
						}

						if ($return === false)
						{
							//$this->error[$table][$field][] = __($param['message']);
							$this->error[$table][$field] = $msg_error;
						}
					}
				}
			}
		}

		$nb = count($keys);

		for ($i = 0; $i < $nb; $i++)
		{
			if (!in_array($keys[$i], $_TABLE['field']))
			{
				unset($data[$table][$keys[$i]]);
				unset($keys[$i]);
			}
			else
			{
				$data[$table][$keys[$i]] = $this->sql_real_escape_string($data[$table][$keys[$i]]);
			}
		}

		if (count($this->error) == 0)
		{
			if (HISTORY_ACTIVE) //traitement specifique 
			{

				if (strstr($this->_table_to_history, $table))
				{

					if (in_array("id", $keys, true))
					{
						$sql = "SELECT * FROM `" . $table . "` WHERE id ='" . $data[$table]['id'] . "'";
						$res = $this->sql_query($sql);

						if ($this->sql_num_rows($res) === 1)
						{
							$before_update = $this->sql_to_array($res);

							//history::insert($table, $data[$table]['id'], $param, $this->_history_type);
						}
					}
				}
			}

			if (in_array("id", $keys, true))
			{

				$id = $data[$table]['id'];
				unset($data[$table]['id']);

				$str = array();
				foreach ($keys as $key)
				{
					if ($key === 'id')
						continue;

					$str[] = "`".$key . "` = '" . $data[$table][$key] . "'";
				}

				$sql = "UPDATE `" . $table . "` SET " . implode(",", $str) . " WHERE id= " . $this->sql_real_escape_string($id) . "";
				$this->sql_query($sql, $table, "UPDATE");


				if ($this->query[$this->number_of_query - 1]['rows'] === 0)
				{
					$this->query[$this->number_of_query - 1]['last_id'] = $id;
				}


				if ($this->query[$this->number_of_query - 1]['rows'] == 0)
				{
					//$sql = "INSERT INTO `".$table."` SET ".implode(",", $str)."";
					//$sql = "INSERT INTO `".$table."` (".implode(",", $keys).") VALUES (".$this->sql_real_escape_string($id).",'".implode("','", $data[$table])."') --";
					$sql = "INSERT IGNORE INTO `" . $table . "` SET id=" . $this->sql_real_escape_string($id) . " , " . implode(",", $str) . ""; //not supported by sybase A amÃ©liorer
					$this->sql_query($sql, $table, "INSERT");
				}
			}
			else
			{
				$sql = "INSERT IGNORE INTO `" . $table . "` (`" . implode("`,`", $keys) . "`) VALUES ('" . implode("','", $data[$table]) . "') --";
				$this->sql_query($sql, $table, "INSERT");
			}

			$this->last_id = $this->query[$this->number_of_query - 1]['last_id'];

			if ($this->last_id == 0)
			{
				$sql = "SELECT id FROM `" . $table . "` WHERE 1 ";

				foreach ($data[$table] as $key => $value)
				{
					if ($key === "column_default")
						continue;
					$sql .= " AND `" . $key . "` = '" . $value . "' ";
				}


				$res = $this->sql_query($sql, $table, "SELECT");
				$tab = $this->sql_to_array($res);


				if (!empty($tab[0]['id']))
				{
					$this->last_id = $tab[0]['id'];
				}
			}

			if (HISTORY_ACTIVE) //traitement specifique 
			{
				if (strstr($this->_table_to_history, $table))
				{

					if (!empty($before_update))
					{
						$param = history::compare($before_update[0], $data[$table]);
						$id_table = $id;
						$type_query = 'UPDATE';
					}
					else
					{
						$param = history::compare(array(), $data[$table]);
						$id_table = $this->last_id;
						$type_query = 'INSERT';
					}


					history::insert($table, $id_table, $param, $this->_history_type, $this->_history_user, $type_query);
					$this->_history_type = HISTORY_TYPE;
					$this->_history_user = null;


					$this->last_id = $id_table;
				}
			}

			//return $this->query[$this->number_of_query-1]['last_id'];
			return $this->sql_insert_id();
		}
		else
		{
			return false;
		}
	}

	function get_count_query() {

		return $this->number_of_query;
	}

	function get_validate() {
		return $this->validate;
	}

	function set_history_type($type) {
		$this->_history_type = $type;
	}

	function set_history_user($id_user_main) {
		$this->_history_user = $id_user_main;
	}

	function sql_delete($data = null) {
		unset($this->error);

		$this->error = array();

		//TODO implement verification of child table before delete

		foreach ($data as $table => $field)
		{
			if (file_exists(TMP . "/database/" . $table . ".table.txt"))
			{



				if (!empty($field['id']))
				{

					if (HISTORY_ACTIVE) //traitement specifique 
					{
						if (strstr($this->_table_to_history, $table))
						{


							$sql = "SELECT * FROM `" . $table . "` WHERE id ='" . $data[$table]['id'] . "'";
							$res = $this->sql_query($sql);

							if ($this->sql_num_rows($res) === 1)
							{
								$before_update = $this->sql_to_array($res);
							}
							else
							{
								return false;
							}

							$param = history::compare($before_update[0], array());
							$id_table = $data[$table]['id'];

							history::insert($table, $id_table, $param, $this->_history_type, $this->_history_user, 'DELETE');
							$this->_history_type = HISTORY_TYPE;
							$this->_history_user = null;
						}
					}

					$sql = "UPDATE " . $table . " SET id_history_etat = 3 WHERE id =" . $field['id'];
					$this->sql_query($sql, $table, "UPDATE");
				}
			}
		}
	}

}

