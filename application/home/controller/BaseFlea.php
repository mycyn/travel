<?php

/**
 * 买家
 */

namespace app\home\controller;
use think\Lang;

class BaseFlea extends BaseMall {


    public function _initialize() {
        parent::_initialize();
        if (config('flea_isuse') != 1 ){
            header('location: '.HOME_SITE_URL);die;
        }
    }


}

?>
