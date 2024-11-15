<?php
    include __DIR__.'/core/init.php';

    $class_id = get('class');
    $month    = get('month');
    if (empty($class_id))
    {
        http_redirect('./');
    }

    $class = db_find_one('edu_class', array('class_id' => $class_id));

    $project = $class['product_name'].' - '.$month.', 逢'.$class['date_time'];
    $project = str_replace('逢逢', '逢', $project);
    $student = array();
    $orders  = db_find('woocommerce_order_items', array('@postmeta.post_id' => 'woocommerce_order_items.order_id', 'woocommerce_order_items.order_item_name' => $project));

    if (!empty($orders))
    {
        foreach ($orders as $key => $value)
        {
            unset($orders[$key]);
            if (!isset($orders[$value['order_item_id']]['order_id']) && isset($value['order_id']))
            {
                $orders[$value['order_item_id']]['order_id'] = $value['order_id'];
            }
            $orders[$value['order_item_id']][$value['meta_key']] = $value['meta_value'];
        }
        foreach ($orders as $key => $value)
        {
            if (empty($value['_date_completed']))
            {
                unset($orders[$key]);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/jquery/jquery@1.7.2/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker.standalone.min.css">
    <script src="https://toms.cc/assets/msg.min.js"></script>
    <script src="https://toms.cc/assets/jquery.simphp.min.js"></script>
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<table class="table">
    <tbody>
        <?php foreach ($orders as $key => $value): ?>
        <tr>
            <td>訂單ID</td>
            <td><?php echo form_val('order_id', $value); ?></td>
        </tr>
        <tr>
            <td>中文姓名</td>
            <td><?php echo form_val('_billing_first_name', $value); ?></td>
        </tr>
        <tr>
            <td>英文姓名</td>
            <td><?php echo form_val('_billing_last_name', $value); ?></td>
        </tr>
        <tr>
            <td>郵箱</td>
            <td><?php echo form_val('_billing_email', $value); ?></td>
        </tr>
        <?php endforeach;?>
    </tbody>
</table>
</body>
</html>
<style>
html,body{background: #fff;}
table{background: #fff;}
table tr:nth-child(4n+1){background: #eee;}
</style>
<script>
$(function(){
    $("button").click(function(event) {
        var data = $("table.table").serializeObject();
        $.ajax({
            url: '#',
            type: 'POST',
            data: data,
            success:function(msg){
                top.$msg.success('Success');
                top.location.reload();
            }
        });
    });
});

