<?php
// define('DEBUG', 1);

include_once __DIR__.'/../core/init.php';
// 自动获取最新的班级/月份数据
$district       = array();
$term_taxonomy  = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.taxonomy' => 'product_cat'));
$products_attrs = db_find('wc_product_attributes_lookup', array('@terms.term_id' => 'wc_product_attributes_lookup.term_id', 'wc_product_attributes_lookup.taxonomy' => array('pa_time', 'pa_month')));
// 獲取一級分類
$terms = arrlist_search($term_taxonomy, array('parent' => 0));
foreach ($terms as $key => $value)
{
    if (strpos($value['name'], '游泳班', 0) !== false)
    {
        $district[$value['term_id']] = $value['name'];
    }
}
foreach ($district as $district_id => $district_name)
{
    // 獲取二級分類
    $terms2 = arrlist_search($term_taxonomy, array('parent' => $district_id));
    foreach ($terms2 as $key => $value)
    {
        $lv2 = $value['term_id'];
        // 獲取二級分類下產品
        $product_ids = array();
        $products    = db_find('term_relationships', array('term_taxonomy_id' => $value['term_id']));
        foreach ($products as $key => $value)
        {
            $product_ids[] = $value['object_id'];
        }
        $posts = db_find('posts', array('ID' => $product_ids));
        foreach ($posts as $k => $product)
        {
            $pa_time    = arrlist_search($products_attrs, array('product_or_parent_id' => $product['ID'], 'taxonomy' => 'pa_time'));
            $time_month = array();
            foreach ($pa_time as $k1 => $v1)
            {
                if (!isset($time_month[$v1['name']]))
                {
                    $time_month[$v1['name']] = array();
                }
                array_push($time_month[$v1['name']], $v1['product_id']);
            }
            foreach ($time_month as $k1 => $v1)
            {
                $_month = array();
                foreach ($products_attrs as $k2 => $v2)
                {
                    if (in_array($v2['product_id'], $v1) && $v2['taxonomy'] == 'pa_month')
                    {
                        $_month[] = $v2['name'];
                    }
                }
                $_month          = array_unique($_month);
                $time_month[$k1] = $_month;
            }
            foreach ($time_month as $ptime => $pmonth)
            {
                $class                 = array();
                $class['class_name']   = $product['post_title'].mb_substr($ptime, 1);
                $class['district_id']  = $lv2;
                $class['date_time']    = $ptime;
                $class['product_id']   = $product['ID'];
                $class['product_name'] = $product['post_title'];
                $lv3                   = explode('-', $product['post_title']);
                $class['lv3']          = empty($lv3[1]) ? '' : trim($lv3[1]);
                $update               = $class;
                $update['date_month'] = encode_json($pmonth);
                if (db_find_one('edu_class', $class))
                {
                    db_update('edu_class', $update, $class);
                }
                else
                {
                    db_insert('edu_class', $update);
                }
            }
        }
    }
}

// 自动处理班级月份数据并入库
$classes     = db_find('edu_class');
$classes     = arrlist_change_key($classes, 'class_id');
$class_uesrs = db_find('edu_class_user', array(), array('id' => -1));
foreach ($classes as $class)
{
    $dm = decode_json($class['date_month']);
    foreach ($dm as $m)
    {
        $exist = arrlist_search($class_uesrs, array('class_id' => $class['class_id'], 'month' => $m));
        if (empty($exist))
        {
            db_insert('edu_class_user', array('class_id' => $class['class_id'], 'month' => $m, 'student' => '', 'teacher' => ''));
        }
    }
}
// -------------------------------------------------------------------------------------------------
// ---------------------------------------------处理学生---------------------------------------------
// -------------------------------------------------------------------------------------------------
// 获取历史订单处理情况
$order_old = db_find('edu_order_status');
$order_old = arrlist_change_key($order_old, 'order_id');


$class_uesrs = db_find('edu_class_user', array('class_id'=>339));
// 更新每个班级学生
foreach ($class_uesrs as $class_user)
{
    // 获取该订单下学生ID
    $class   = $classes[$class_user['class_id']];
    $project = $class['product_name'].' - '.$class_user['month'].', '.$class['date_time'];
    $student_order = array();
    // 获取当前班级下所有的订单信息
    $orders  = db_find('woocommerce_order_items', array('@postmeta.post_id' => 'woocommerce_order_items.order_id', 'woocommerce_order_items.order_item_name' => $project));
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
                $orders_2[$value['order_id']] = $value;
                $orders_2[$value['order_id']][$mk] = $mv;
            }
        }
        $orders = $orders_2;
        foreach ($orders as $key => $value)
        {
            if (!empty($value['_date_completed']) && strtotime('-3 months') <= $value['_date_completed'])
            {
                // if (isset($order_old[$value['order_id']]))
                // {
                //     continue;
                // }
                $student_order[] = $value['_customer_user'];
                // db_insert('edu_order_status', array('order_id'=>$value['order_id'], 'status'=>1,'created'=>time()));
            }
        }
        // end
    }



    if (!empty($student_order))
    {
        $student_old = decode_json($class_user['student']);
        $student_all = array_merge($student_old, $student_order);
        $student = array_unique($student_all);
        $r = db_update('edu_class_user', array('student' => $student), array('id' => $class_user['id']));
        var_dump($r);
    }
}
// end

if (get('manual')=='0ce0f781b2da12d7')
{
    echo 'Update Success!';
}