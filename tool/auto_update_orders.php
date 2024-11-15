<?php
include_once __DIR__.'/../core/init.php';

// db_update('edu_class', array('class_year'=>2024), array());
// db_update('edu_class_user', array('class_year'=>2024), array());
// db_update('edu_class_user_days', array('class_year'=>2024), array());
// db_update('edu_result', array('class_year'=>2024), array());
// db_update('edu_attendance', array('class_year'=>2024), array());

define('DEBUG', 1);
// ############查询历史记录开始, 此处更新所有订单后后注释掉
// $order_id = session_get('order_id');
// // $order_id = 0;
// if (empty($order_id)) {
//     $orders = db_find_one('woocommerce_order_items',
//     array(
//         '@posts.ID'         => 'woocommerce_order_items.order_id',
//         '@postmeta.post_id' => 'woocommerce_order_items.order_id',
//         array('posts.post_status' => 'wc-completed', '|posts.post_status' => 'wc-refunded'),
//     ),
//     array('postmeta.post_id' => 1),
//     );
//     $order_id = $orders['order_id'];
// }

// $orders = db_find('woocommerce_order_items',
//     array(
//         '@posts.ID'         => 'woocommerce_order_items.order_id',
//         '@postmeta.post_id' => 'woocommerce_order_items.order_id',
//         '>=posts.ID'  => $order_id,
//         '<=posts.ID'  => $order_id+1000,
//         array('posts.post_status' => 'wc-completed', '|posts.post_status' => 'wc-refunded'),
//     ),
//     array('postmeta.post_id' => 1),
// );
// session_set('order_id', $order_id+1000);
// ############查询历史记录结束, 此处更新所有订单后后注释掉


// ############ 此处为自动更新近一个月的订单开始
$orders = db_find('woocommerce_order_items',
    array(
        '@posts.ID'         => 'woocommerce_order_items.order_id',
        '@postmeta.post_id' => 'woocommerce_order_items.order_id',
        '>posts.post_date'  => date('Y-m-d H:i:s', (time() - 86400 * 30)),
        array('posts.post_status' => 'wc-completed', '|posts.post_status' => 'wc-refunded'),
    ),
    array('postmeta.post_id' => 1),
);
// ############ 此处为自动更新近一个月的订单结束
$orders_2 = array();
if (!empty($orders))
{
    foreach ($orders as $key => $value)
    {
        $mk = $value['meta_key'];
        $mv = $value['meta_value'];
        unset($value['meta_key'], $value['meta_value']);
        if (isset($orders_2[$value['order_id']]))
        {
            $orders_2[$value['order_id']][$mk] = $mv;
        }
        else
        {
            $orders_2[$value['order_id']]      = $value;
            $orders_2[$value['order_id']][$mk] = $mv;
        }
    }


    $orders = $orders_2;
    foreach ($orders as $key => $value)
    {
        if ($value['post_status'] == 'wc-completed' || $value['post_status'] == 'wc-refunded')
        {
            $data                   = array();
            $data['amount']         = $value['_order_total'];
            $data['gateway']        = $value['_payment_method_title'];
            $data['order_date']     = strtotime($value['post_date']);
            $data['created']        = time();
            $data['user_id']        = $value['_customer_user'];
            $data['woo_class_name'] = $value['order_item_name'];
            $data['woo_order_id']   = $value['order_id'];
            $data['order_source']   = 'woocommerce';
            $data['woo_status']     = $value['post_status'];

            $where = array('woo_order_id' => $value['order_id']);


            if (db_find_one('edu_order', $where))
            {
                db_update('edu_order', $data, $where);
            }
            else
            {
                db_insert('edu_order', $data);
            }
        }
    }
    echo 1;
}else{
    echo 'end';
}

exit("\n...end...");