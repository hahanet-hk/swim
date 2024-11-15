<?php
define('DEBUG', 1);
include_once __DIR__.'/../core/init.php';
// 獲取所有班級資料
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
$all_products = array();
foreach ($district as $district_id => $district_name)
{
    // 獲取二級分類
    $terms2 = arrlist_search($term_taxonomy, array('parent' => $district_id));
    foreach ($terms2 as $key => $value)
    {
        $product_district_id = $value['term_id'];
        $lv2                 = $value['term_id'];
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
            $_temp                 = array();
            $_temp['product_name'] = $product['post_title'];
            $_temp['district_id']  = $product_district_id;
            $_temp['product_id']   = $product['ID'];
            $all_products[]        = $_temp;
        }
    }
}
$all_products = arrlist_change_key($all_products, 'product_name');
// 获取订单
$orders = db_find('woocommerce_order_items',
    array(
        '@posts.ID'         => 'woocommerce_order_items.order_id',
        '@postmeta.post_id' => 'woocommerce_order_items.order_id',
        // '>posts.post_date'  => date('Y-m-d H:i:s', (time() - 86400 * 90)),
        '>posts.post_date'  => '2024-09-01 00:00:00',
        'posts.post_status' => array('wc-completed', 'wc-refunded'),
    ),
    array('postmeta.post_id' => -1),
);
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
}

// post_date

foreach ($orders as $key => $order)
{
    // $order_date = $order['post_date'];

    $order_item_name = $order['order_item_name'];
    if (strpos($order_item_name, ' - ') === false || strpos($order_item_name, '逢') == false)
    {
        continue;
    }
    $pos          = strpos($order_item_name, ' - ');
    $product_name = substr($order_item_name, 0, $pos);
    $pa           = substr($order_item_name, $pos + 3);
    $pos          = mb_strpos($pa, '逢');
    $pa_month     = mb_substr($pa, 0, $pos);
    $pa_time      = mb_substr($pa, $pos + 1);
    $pa_month     = trim($pa_month, "\t\n\r\v\f ,");
    $pa_time      = trim($pa_time, "\t\n\r\v\f ,");
    $class_name   = $product_name.$pa_time;
    $class_year   = get_class_year($order['post_date'], $pa_month);
    $class        = db_find_one('edu_class', array('class_name' => $class_name));
    $err_info     = "\n<BR>".$order['post_status'].': order_id '.$order['order_id']." (".$class_name.') --- ';

    if (empty($product_name))
    {
        echo 'OrderID: '.$order['order_id'].' '.'訂單中產品名稱有誤!<BR>';
        continue;
    }

    if (empty($pa_time))
    {
        echo 'OrderID: '.$order['order_id'].' '.'訂單中班級時間有誤!<BR>';
        continue;
    }

    if (empty($class_name))
    {
        echo 'OrderID: '.$order['order_id'].' '.'訂單中班級名稱有誤!<BR>';
        continue;
    }

    // 如果班級不存在, 則創建!
    if (empty($class))
    {
        $pos_district  = mb_strpos($product_name, '游泳班-', 0);
        $district_name = mb_substr($product_name, $pos_district + 4);

        if (!isset($all_products[$product_name]))
        {
            echo $err_info."班級在WP中不存在!";
            continue;
        }
        $product = $all_products[$product_name];
        if (empty($district_name))
        {
            exit('班級地區出錯!');
        }
        $data                 = array();
        $data['class_name']   = $class_name;
        $data['district_id']  = $product['district_id'];
        $data['product_id']   = $product['product_id'];
        $data['product_name'] = $product['product_name'];
        $data['date_time']    = $pa_time;
        $data['lv3']          = $district_name;
        $data['class_year']   = $class_year;
        $_class_id            = db_insert('edu_class', $data);
        if (empty($_class_id))
        {
            exit('班級插入失敗!');
        }
        $data['class_id'] = $_class_id;
        $class            = $data;
    }

    if (empty($pa_month))
    {
        echo 'OrderID: '.$order['order_id'].' '.$order_item_name.' 月份不存在!<BR>';
        continue;
    }

    // continue;
    $class_user = db_find_one('edu_class_user', array('class_id' => $class['class_id'], 'month' => $pa_month, 'class_year' => $class_year));

    // 處理退款-支付完成
    if ($order['post_status'] == 'wc-refunded')
    {
        if (empty($class_user))
        {
            // echo $err_info.'class_user 不存在, 不需要刪除學生!';
            continue;
        }
        // 如果退款, 則刪除學生
        $order_old  = decode_json($class_user['order_id']);
        $order_new  = array($order['order_id']);
        $order_last = array_unique(array_merge($order_old, $order_new));
        //
        $student_old = decode_json($class_user['student']);
        foreach ($student_old as $k01 => $v01)
        {
            if ($v01 == $order['_customer_user'])
            {
                unset($student_old[$k01]);
            }
        }
        $student_old = array_unique($student_old);

        $data             = array();
        $data['student']  = encode_json($student_old);
        $data['order_id'] = encode_json($order_last);
        db_update('edu_class_user', $data, array('id' => $class_user['id']));
    }
    else
    {

        // 如果支付成功, 則插入/更新學生
        $data               = array();
        $data['class_id']   = $class['class_id'];
        $data['month']      = $pa_month;
        $data['class_year'] = $class_year;
        if (empty($class_user))
        {
            $teacher = '';
            $prev    = db_find_one('edu_class_user', array('class_id' => $class['class_id']), array('id' => -1));
            if (!empty($prev))
            {
                $teacher = $prev['teacher'];
            }
            $data['teacher']    = $teacher;
            $data['student']    = encode_json(array($order['_customer_user']));
            $data['order_id']   = encode_json(array($order['order_id']));
            $data['class_exam'] = get_class_exam($class_name);
            $data['sort']       = year_month_sort($data['class_year'], $data['month']);
            db_insert('edu_class_user', $data);
        }
        else
        {
            $order_old = decode_json($class_user['order_id']);
            if (in_array($order['order_id'], $order_old))
            {
                continue;
            }
            $order_new        = array($order['order_id']);
            $student_old      = decode_json($class_user['student']);
            $student_new      = array($order['_customer_user']);
            $student          = array_unique(array_merge($student_old, $student_new));
            $order            = array_unique(array_merge($order_old, $order_new));
            $where            = $data;
            $data['student']  = encode_json($student);
            $data['order_id'] = encode_json($order);
            db_update('edu_class_user', $data, $where);
        }
    }
}

exit("\n<BR>---END---");
