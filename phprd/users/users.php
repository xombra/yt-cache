<?php

/**
 * @author dr4g0n
 * @copyright 2008
 */

require_once "phprd/error-log/error.php";

class user_sessions {
	
	private $storage="UNDEFINED";	// file or mysql implemented
	private $db_handle=NULL;
	private $db_table=NULL;
    private $error=NULL;
	
	public function __construct($storage_engine="mysql",$mysql_server="localhost",$mysql_username="root",$mysql_password="",$mysql_db="phprd",$mysql_table="users") {
        $this->error=ErrorHandler::singleton();
        if (empty($storage_engine) || empty($mysql_server) || empty($mysql_username) || empty($mysql_db) || empty($mysql_table))
            $this->error->add_error_die("Not enough parameters for connection to DB");
        switch ($storage_engine) {
			case "mysql":
				$this->db_handle=new mysql;
				$r=$this->db_handle->connect($mysql_server,$mysql_username,$mysql_password,$mysql_db);
                if (!$r) {
					$this->error->add_error_die("Server <strong>{$mysql_server}</strong> connect failure");
				} else {
				$query='CREATE TABLE IF NOT EXISTS `users`(`id` serial, `username` varchar(32) default "" not null, `password` varchar(32) default "" not null, `email` varchar(64) default "" not null, `group` varchar(32) default "" not null)';
				$r=$this->db_handle->sql($query);
				$this->storage=$storage_engine;
				$this->db_table=$mysql_table;
                }
			break;
			case "file":
				$this->storage=$storage_engine;
			break;
			default:
			break;
			}
	}
	
	public function login_http($realm="Restricted") {
		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    	$username = mysql_escape_string(trim($_SERVER['PHP_AUTH_USER']));
    	$password = mysql_escape_string(trim($_SERVER['PHP_AUTH_PW']));
    	$r=$this->login_user($username,$password);
    	if (!$r) {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
    		header("HTTP/1.0 401 Unauthorized");
    		die();
			} else return true;
		} else {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
    		header("HTTP/1.0 401 Unauthorized");
    		die();
		}
	}
	
	public function login_user($username,$password,$userdb="users.db") {
			switch ($this->storage) {
			case "file":
				$r=$this->login_file($username,$password,$userdb);
				if ($r)	{
					if (empty($_SESSION['online']))
						$_SESSION['online']=time();
					return true;
				} else return false;
			break;
			case "mysql":
				$r=$this->login_mysql($username,$password);
				if ($r)	{
					if (empty($_SESSION['online']))
						$_SESSION['online']=time();
					return true;
				} else return false;
				break;
			default:
			return false;
		}
	}
	
	private function login_mysql ($s_username,$s_password) {
		if (empty($s_username) || empty($s_password)) return false;
		$finduser=array("username"=>$s_username,"password"=>md5($s_password));
		$res=$this->db_handle->get_row($this->db_table,$finduser);
		if (!$res)
			return false;
		$_SESSION['s_username']=$s_username;
		$_SESSION['s_password']=$s_password;
		return true;
	}
	
	private function login_file($username,$password,$userdb) {
        // file can be user password or seralized array of username and password
		if (empty($username) || empty($password) || empty($userdb))
			$this->error->add_error_die("One of the vars are empty!");
		if (!file_exists($userdb)) {
            $this->error->add_error_die("Login file does not exist!");			
            }
		$fh=fopen($userdb,"r");			//open db in read-only mode
		while (!feof($fh))
			$users.=fread($fh,999999);	//read all accounts-warning, very big userdb can eat alot of mem
            if (strlen($users)<1)
                $this->error->add_error_die("Users file empty!");
		fclose($fh);
		$users=explode("\r\n",$users);	//parse all users
		foreach ($users as $user) {
			if (!empty($user)) {
				$user_array=unserialize($user);
                if (empty($user_array)) {
                    $user_a=explode(" ",$user);	//parse all users
                    $user_array["username"]=$user_a[0];
                    $user_array["password"]=$user_a[1];
                }
				if ($username==$user_array["username"] && (md5($password)==$user_array["password"]) || $password==$user_array["password"]) return true;
			}
		}
		return false;
	}

	public function create_user($username,$password,$email,$group) {
			switch ($this->storage) {
			case "file":
				return $this->create_user_file($username,$password,$email,$group);
			break;
			case "mysql":
				if (!$this->db_handle)
					$this->error->add_error_die("No DB handle!");
				$username=mysql_real_escape_string($username);
				$password=md5($password);
				$find=array("username"=>$username);
				$row=$this->db_handle->get_row("users",$find);
				if (!empty($row))
					return false;	// user with same username already exists!
				$row=array("username"=>$username,"password"=>$password,"email"=>$email,"group"=>$group);
				$this->db_handle->insert_row("users",$row);
				if ($this->db_handle->rows_affected<1)
					return false;
				else
					return true;
				break;
			default:
			return false;
		}
	}

	private function create_user_file($username,$password,$email,$group) {
		$fh=fopen($userdb,"a");
		$user=array("username"=>$username,"password"=>md5($password),"email"=>$email,"group"=>$group);
		fseek($fh,0,SEEK_END);
		$users=fwrite($fh,serialize($user)."\r\n");
		fclose($fh);
		return true;
	}

	public function check_session($db,$table,$passwordmethod='md5') {
		switch ($this->storage) {
			case "file":
				return $this->check_session_file($db,$table,$passwordmethod='md5');
			break;
			case "mysql":
				return $this->check_session_mysql($db,$table,$passwordmethod='md5');
			break;
			default:
				$this->error->add_error_die("Uindentified login method!");
		}
		}

	private function check_session_mysql (mysql &$db,$table="users",$passwordmethod='md5') {
		if (!$db) $this->error->add_error_die("No DB set!");
		session_start();
		$s_username=$_SESSION['s_username'];
		$s_password=$_SESSION['s_password'];
		if (empty($s_username) || empty($s_password)) return false;
		if (empty($_SESSION['online'])) $_SESSION['online']=time();
		switch ($passwordmethod) {
			case "md5":
			$finduser=array("username"=>$s_username,"password"=>md5($s_password));
			break;
			case "sha1":
			$finduser=array("username"=>$s_username,"password"=>sha1($s_password));
			break;
			default:	//plaintext
			$finduser=array("username"=>$s_username,"password"=>$s_password);
		}
		$res=$db->get_row($table,$finduser);
		if (!$res) return false;
		return true;
	}

	private function check_session_file($db,$table="users",$passwordmethod='md5') {
		if (!empty($db)) $this->error->add_error_die("No DB set!");
		session_start();
		$s_username=$_SESSION['s_username'];
		$s_password=$_SESSION['s_password'];
		if (empty($s_username) || empty($s_password)) return false;
		if (empty($_SESSION['online'])) $_SESSION['online']=time();
		return $this->login_file($s_username,$s_password,$db);
	}

	public function logout () {
		$_SESSION['s_username']="";
		$_SESSION['s_password']="";
	}
	
	public function get_userinfo () {
		$online=(int) $_SESSION['online'];
		$return_array=array("online_time"=>time()-$online);
		return $return_array;
	}
	
}

?>