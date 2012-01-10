<?php

class logout extends base {
    public function run() {
        $this->page_manager->session();
        $this->page_manager->finish_script($content);
    }
}

?>