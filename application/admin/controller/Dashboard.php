<?php

namespace app\admin\controller;

use think\Lang;

class Dashboard extends AdminControl {

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/dashboard.lang.php');
    }

    function index() {
        $this->welcome();
    }

    /*
     * 检查是否为最新版本
     */

    function version() {
        //当前版本
        $curent_version = file_get_contents(APP_PATH . 'version.php');
        //获取最新版本信息
        $vaules = array(
            'domain'=>$_SERVER['HTTP_HOST'], 
            'version'=>$curent_version, 
        );
        $service_url = "http://service.csdeshang.com/index.php/Home/Version/checkDsmall.html?".http_build_query($vaules);
        $service_info = @file_get_contents($service_url);
        $version_message = json_decode($service_info);
        $this->assign('version_message', $version_message);
    }

    function welcome() {
        $this->version();
        /**
         * 管理员信息
         */
        $admin_model = model('admin');
        $tmp = $this->getAdminInfo();
        $condition['admin_id'] = $tmp['admin_id'];
        $admin_info = $admin_model->infoAdmin($condition);
        $admin_info['admin_login_time'] = date('Y-m-d H:i:s', ($admin_info['admin_login_time'] == '' ? time() : $admin_info['admin_login_time']));
        /**
         * 系统信息
         */
        $setup_date = config('setup_date');
        $statistics['os'] = PHP_OS;
        $statistics['web_server'] = $_SERVER['SERVER_SOFTWARE'];
        $statistics['php_version'] = PHP_VERSION;
        $statistics['sql_version'] = $this->_mysql_version();
        //$statistics['shop_version'] = $version;
        $statistics['setup_date'] = substr($setup_date, 0, 10);

        $statistics['domain'] = $_SERVER['HTTP_HOST'];
        $statistics['ip'] = GetHostByName($_SERVER['SERVER_NAME']);
        $statistics['zlib'] = function_exists('gzclose') ? 'YES' : 'NO'; //zlib
        $statistics['safe_mode'] = (boolean) ini_get('safe_mode') ? 'YES' : 'NO'; //safe_mode = Off
        $statistics['timezone'] = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        $statistics['curl'] = function_exists('curl_init') ? 'YES' : 'NO';
        $statistics['fileupload'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknown';
        $statistics['max_ex_time'] = @ini_get("max_execution_time") . 's'; //脚本最大执行时间
        $statistics['set_time_limit'] = function_exists("set_time_limit") ? true : false;
        $statistics['memory_limit'] = ini_get('memory_limit');
        $statistics['version'] = file_get_contents(APP_PATH . 'version.php');
        if (function_exists("gd_info")) {
            $gd = gd_info();
            $statistics['gdinfo'] = $gd['GD Version'];
        } else {
            $statistics['gdinfo'] = "未知";
        }

        $this->assign('statistics', $statistics);
        $this->assign('admin_info', $admin_info);
        $this->setAdminCurItem('welcome');
        echo $this->fetch('welcome');
        exit;
    }

    private function _mysql_version() {
        $version = db()->query("select version() as ver");
        return $version[0]['ver'];
    }

    function aboutus() {
        $this->setAdminCurItem('aboutus');
        return $this->fetch();
    }

    /**
     * 统计
     */
    public function statistics() {
        $statistics = array();
        // 本周开始时间点
        $tmp_time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (date('w') == 0 ? 7 : date('w') - 1) * 24 * 60 * 60;
        /**
         * 会员
         */
        $member_model = model('member');
        // 会员总数
        $statistics['member'] = $member_model->getMemberCount(array());
        // 新增会员数
        $statistics['week_add_member'] = $member_model->getMemberCount(array('member_addtime' => array('egt', $tmp_time)));
        // 预存款提现
        $statistics['cashlist'] = model('predeposit')->getPdcashCount(array('pdc_payment_state' => 0));

        /**
         * 店铺
         */
        $store_model = model('store');
        // 店铺总数
        $statistics['store'] = model('store')->getStoreCount(array());
        // 店铺申请数
        $statistics['store_joinin'] = model('storejoinin')->getStorejoininCount(array('joinin_state' => array('in', array(10, 11))));
        //经营类目申请
        $statistics['store_bind_class_applay'] = model('storebindclass')->getStorebindclassCount(array('storebindclass_state' => 0));
        //店铺续签申请
        $statistics['store_reopen_applay'] = model('storereopen')->getStorereopenCount(array('storereopen_state' => 1));
        // 即将到期
        $statistics['store_expire'] = $store_model->getStoreCount(array('store_state' => 1, 'store_endtime' => array('between', array(TIMESTAMP, TIMESTAMP + 864000))));
        // 已经到期
        $statistics['store_expired'] = $store_model->getStoreCount(array('store_state' => 1, 'store_endtime' => array('between', array(1, TIMESTAMP))));

        /**
         * 商品
         */
        $goods_model = model('goods');
        // 商品总数
        $statistics['goods'] = $goods_model->getGoodsCommonCount(array());
        // 新增商品数
        $statistics['week_add_product'] = $goods_model->getGoodsCommonCount(array('goods_addtime' => array('egt', $tmp_time)));
        // 等待审核
        $statistics['product_verify'] = $goods_model->getGoodsCommonWaitVerifyCount(array());
        // 举报
        $statistics['inform_list'] = model('inform')->getInformCount(array('inform_state' => 1));
        // 品牌申请
        $statistics['brand_apply'] = model('brand')->getBrandCount(array('brand_apply' => '0'));

        /**
         * 交易
         */
        $order_model = model('order');
        $refundreturn_model = model('refundreturn');
        $vrrefund_model = model('vrrefund');
        $complain_model = model('complain');
        // 订单总数
        $statistics['order'] = $order_model->getOrderCount(array());
        // 退款
        $statistics['refund'] = $refundreturn_model->getRefundreturn(array('refund_type' => 1, 'refund_state' => 2));
        // 退货
        $statistics['return'] = $refundreturn_model->getRefundreturn(array('refund_type' => 2, 'refund_state' => 2));
        // 虚拟订单退款
        $statistics['vr_refund'] = $vrrefund_model->getVrrefundCount(array('admin_state' => 1));
        // 投诉
        $statistics['complain_new_list'] = $complain_model->getComplainCount(array('complain_state' => 10));
        // 待仲裁
        $statistics['complain_handle_list'] = $complain_model->getComplainCount(array('complain_state' => 40));

        /**
         * 运营
         */
        // 抢购数量
        $statistics['groupbuy_verify_list'] = model('groupbuy')->getGroupbuyCount(array('groupbuy_state' => 10));
        // 积分订单
        $pointsorder_model = model('pointorder');
        $condition =array('point_orderstate' => array('in', array(11, 20)));
        $statistics['points_order'] = $pointsorder_model->getPointorderCount($condition);
        //待审核账单
        $bill_model = model('bill');
        $statistics['check_billno'] = $bill_model->getOrderbillCount(array('ob_state'=>BILL_STATE_STORE_COFIRM));
        //待支付账单
        $statistics['pay_billno'] = $bill_model->getOrderbillCount(array('ob_state'=>BILL_STATE_STORE_COFIRM));
        // 平台客服
        $statistics['mall_consult'] = model('mallconsult')->getMallconsultCount(array('mallconsult_isreply' => 0));
        // 服务站
        $statistics['delivery_point'] = model('deliverypoint')->getDeliverypointWaitVerifyCount(array());

        echo json_encode($statistics);
        exit;
    }

}

?>
