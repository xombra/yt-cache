<?php

/**
 * @author 
 * @copyright 2008
 */

require_once "phprd/error-log/log.php";

class ErrorHandler {

	private static $instance;
    private $error_array=array();
	private $log;

 /**
 * Construct error class
 *  
 */

	public function __construct() {
		$this->log=new Logging or die("No log component");
	    //$this->singleton();
    }

 /**
 * Singleton method
 *  
 */

    public static function singleton()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

 /**
 * Destruct error class
 *  
 */

	public function __destruct() {
		$this->log->free;
	}

 /**
 * Add error message to error queue
 * 
 * @error error message
 * @return nothing 
 *   
 */

	public function add_error ($error) {
		$this->error_array[]=$error;
		$this->log->addtolog(SEVERITY_ERROR,$error);
	}

 /**
 * Add error, print it and halt execution
 * 
 * @error error message
 * @return nothing 
 *   
 */

	public function add_error_die($error) {
		$this->add_error($error);
		$this->print_die();
	}

 /**
 * Print errors already in queue and halt execution
 * 
 * @return nothing 
 *   
 */

	public function print_die() {
		$bt=debug_backtrace();
        unset($fullbt);
        foreach ($bt as $sbt) {
            $fullbt.="File <strong>".$sbt["file"]."</strong> line <strong>".$sbt["line"]."</strong> function <strong>".$sbt["function"]."</strong> Class <strong>".$sbt["class"]."</strong><br>";
            //var_dump($sbt);die;
        }
        if (is_array($this->error_array) && !empty($this->error_array))
		foreach ($this->error_array as $id=>$errors) {
			print "<b>Error</b> id <font color='red'><b>$id</b></font> message <font color='red'>$errors</font> <br>";
		}
        print "<br>Full backtrace:<br>".$fullbt;
		die('<br><b><font color="Red">Program terminated!</font></b>');
	}

 /**
 * Print error in javascript and goes back to previous page
 * 
 * @message error message
 * @return nothing 
 *   
 */    

public function js_die($message) {
$output=<<<JS
<script type="text/javascript">
function jsdie(message) {
alert(message);
history.back();
}
jsdie("{$message}");
</script>
JS;
die($output);
}

 /**
 * Returns array with error list
 * 
 * @return error array 
 *   
 */

	public function get_errors() {
		return $this->$error_array;
	}


}

?>