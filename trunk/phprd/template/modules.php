<?php

/**
 * @author dr4g0n
 * @copyright 2008
 */

class modules {
    
    private $registered_modules=array();
    private $modules_data;
    
    public function add_hook($class,$function,$hook_to,$module_name,$module_version,$module_description) {
        $this->registered_modules[$class][$function]=$hook_to;
        $this->modules_data[$module_name]["name"]=$module_name;
        $this->modules_data[$module_name]["version"]=$module_version;
        $this->modules_data[$module_name]["class"]=$class;
        $this->modules_data[$module_name]["function"]=$function;
        $this->modules_data[$module_name]["description"]=$module_description;
        $this->modules_data[$module_name]["class"]=$class;
        $this->modules_data[$module_name]["callback"]=$hook_to;
    }
    
    public function dump_modules() {
        $hook_tree="Callbacks:<br>";
        foreach($this->registered_modules as $registered_class_name=>$registered_class_array)
        foreach($registered_class_array as $registered_function=>$callback) {
            $info=$this->find_module($registered_class_name,$registered_function,$callback);
            $hook_tree.="<strong>".$info["name"]."</strong> <em>v".$info["version"]."</em> Description: <strong>".$info["description"]."</strong> Calls [".$callback." => ".$registered_class_name."::".$registered_function."]<BR>";
        }
    return $hook_tree;
    }
    
    public function find_module($class="",$function="",$callback="") {
        foreach ($this->modules_data as $module_data) {
            if ($module_data["class"]==$class && $module_data["function"]==$function && $module_data["callback"]==$callback)
                return $module_data;
        }
    }
    
    public function pass_hook(&$param) {
        $backtrace=debug_backtrace();
        var_dump($backtrace);
        echo "<br><br>";
        $caller_filename=basename($backtrace[0]["file"]);
            $caller_class=$backtrace[1]["class"];
            $caller_function=$backtrace[1]["function"];
            if ($caller_class)
                $caller_function=$caller_class."::".$caller_function;
            $caller_parameters=$backtrace[1]["args"];
        if (!$caller_function)
            $caller_function=$caller_filename;
        //       die($caller_function);
        foreach($this->registered_modules as $registered_class_name=>$registered_class_array)
        foreach($registered_class_array as $registered_function=>$callback) {
            if ($callback==$caller_function) {
                $instanced_hook_class=new $registered_class_name;   //instance callback class
                $instanced_hook_class->$registered_function($caller_parameters,$param); //call callback function
                $instanced_hook_class->free;    //free function
            }
        }
    }
}

?>