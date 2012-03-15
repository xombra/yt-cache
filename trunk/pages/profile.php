<?php

class profile extends base {

    public function submenu() {
        $submenu=array();
        $submenu[0]["index"]["link"]="submenu=profile";
        $submenu[0]["index"]["name"]="Profile";
        $this->page_manager->submenu($submenu);
    }

    public function profile_show() {
    $id=(int)(int)$_SESSION['SESS_USER_ID'];
    $find=array(
    "id"=>$id,
    );
    $urow=$this->db->get_row("users",$find);
$form_block=<<<FORMBLOCK
       <div class="block" id="block-forms">
           <div class="content">
            <h2 class="title">User Profile - id {$id}</h2>
            <div class="inner">
              <form action="#" method="get" class="form">
                <div class="group">
                  <label class="label">Username</label>
                  <input type="text" class="text_field" value="{$urow["username"]}"/>
                  <span class="description">Ex: a simple text</span>
                </div>
                <div class="group">
                  <div class="fieldWithErrors">
                    <label class="label" for="post_title">Password</label>
                    <span class="error">can't be blank</span>
                  </div>
                  <input type="text" class="text_field" value="{$urow["password"]}"/>
                  <span class="description">Ex: a simple text</span>
                </div>
                <div class="group">
                  <div class="fieldWithErrors">
                    <label class="label" for="post_title">Access right</label>
                    <span class="error">can't be blank</span>
                  </div>
                  <input type="text" class="text_field" value="{$urow["ulevel"]}"/>
                  <span class="description">Ex: a simple text</span>
                </div>
                <div class="group">
                  <label class="label">Text area</label>
                  <textarea class="text_area" rows="10" cols="80"></textarea>
                  <span class="description">Write here a long text</span>
                </div>
                <div class="group navform wat-cf">
                  <button class="button" type="submit">
                    <img src="images/icons/tick.png" alt="Save" /> Save
                  </button>
                  <span class="text_button_padding">or</span>
                  <a class="text_button_padding link_button" href="#header">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>
FORMBLOCK;
$this->content.=$form_block;
    }
    
    public function run() {
        $this->get_request();
        foreach ($this->requests as $request=>$value) {
            switch($request) {
                default:
            }
        }

        $submenu=$_REQUEST["submenu"];
        switch($submenu) {
            case "profile":
            $this->profile_show();
            break;
            default:
            header("Location: index.php?page=profile&submenu=profile");
        }

        $this->submenu();
        $this->page_manager->finish_script($this->content);

    }
}

?>