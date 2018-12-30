<?php

namespace app\home\controller;

use think\Lang;

class Sellercallcenter extends BaseSeller {

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/sellercallcenter.lang.php');
    }

    public function index() {
        $store_model = model('store');
        $store_info = $store_model->getStoreInfo(array('store_id' => session('store_id')));
        $this->assign('storeinfo', $store_info);

        $seller_model = model('seller');
        $seller_list = $seller_model->getSellerList(array('store_id' => $store_info['store_id']), '', 'seller_id asc'); //账号列表
        $this->setSellerCurMenu('Sellercallcenter');
        $this->setSellerCurItem('index');
        $this->assign('seller_list', $seller_list);
        return $this->fetch($this->template_dir . 'index');
    }

    /**
     * 保存
     */
    public function save() {
        if (request()->isPost()) {
            $update = array();
            $i = 0;
            if (is_array($_POST['pre']) && !empty($_POST['pre'])) {
                foreach ($_POST['pre'] as $val) {
                    if (empty($val['name']) || empty($val['type']) || empty($val['num']))
                        continue;
                    $update['store_presales'][$i]['name'] = $val['name'];
                    $update['store_presales'][$i]['type'] = intval($val['type']);
                    $update['store_presales'][$i]['num'] = $val['num'];
                    $i++;
                }
                $update['store_presales'] = @serialize($update['store_presales']);
            }
            else {
                $update['store_presales'] = serialize(null);
            }

            $i = 0;
            if (is_array($_POST['after']) && !empty($_POST['after'])) {
                foreach ($_POST['after'] as $val) {
                    if (empty($val['name']) || empty($val['type']) || empty($val['num']))
                        continue;
                    $update['store_aftersales'][$i]['name'] = $val['name'];
                    $update['store_aftersales'][$i]['type'] = intval($val['type']);
                    $update['store_aftersales'][$i]['num'] = $val['num'];
                    $i++;
                }
                $update['store_aftersales'] = @serialize($update['store_aftersales']);
            }
            else {
                $update['store_aftersales'] = serialize(null);
            }

            $update['store_workingtime'] = $_POST['working_time'];
            $where = array();
            $where['store_id'] = session('store_id');
            model('store')->editStore($update, $where);
            ds_json_encode(10000,lang('ds_common_save_succ'));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => lang('ds_member_path_store_callcenter'),
                'url' => url('Sellercallcenter/index')
            ),
        );
        return $menu_array;
    }

}
