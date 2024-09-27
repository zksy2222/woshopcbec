<?php

namespace app\admin\controller;
use app\admin\controller\Common;

class Addons extends Common{
    
    public function lst() {
//        hook('testhook', ['id' => 1]);
        $this->redirect('addons/execute/test-action-link');
    }
}
