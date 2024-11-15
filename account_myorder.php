<?php
$user_id = get_current_user_id();
$classes    = db_find('edu_class');
$classes    = arrlist_change_key($classes, 'class_id');

$order     = db_find('postmeta', array('meta_key' => '_customer_user', 'meta_value' => $user_id));
$order_ids = array();
foreach ($order as $key => $value)
{
    $order_ids[] = $value['post_id'];
}
$order_list = array();
if (!empty($order_ids))
{
    $list = db_find('postmeta', array('@woocommerce_order_items.order_id' => 'postmeta.post_id', '@posts.ID' => 'postmeta.post_id', 'postmeta.post_id' => $order_ids,'posts.post_status' => array('wc-completed', 'wc-refunded'),));
    foreach ($list as $key => $value)
    {
        if (empty($order_list[$value['order_id']]))
        {
            $order_list[$value['order_id']] = $value;
        }
        $order_list[$value['order_id']][$value['meta_key']] = $value['meta_value'];
    }
}
?>

<table class="table">
    <thead>
        <tr>
            <td>班級</td>
            <td>學費</td>
            <td>交費日期</td>
            <td>付款方式</td>
            <td>狀態</td>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($order_list as $key => $order): ?>
        <?php
            list($product_name, $datetime) = explode(' - ', $order['order_item_name']);
            list($pa_month, $pa_time)      = explode(',', $datetime);
            $product_name                  = trim($product_name);
            $pa_month                      = trim($pa_month);
            $pa_time                       = trim($pa_time);
            $class_name                    = $product_name.mb_substr($pa_time, 1);
            $class                         = db_find('edu_class', array('class_name' => $class_name));
            if (!empty($class))
            {
                $class             = reset($class);
                $where['class_id'] = $class['class_id'];
                $where['month']    = $pa_month;
                $where['user_id']  = $student_id;
                $r                 = db_find_one('edu_class_user_days', $where);
                if (empty($r))
                {
                    unset($where['user_id']);
                    $r = db_find_one('edu_class_user', $where);
                }
                $days = empty($r['days']) ? get_days($pa_time, $pa_month) : $r['days'];
                $days = explode(',', $days);
            }
            else
            {
                continue;
            }
        ?>
        <tr>
            <td><?php echo $class_name ?><br><?php echo $pa_month ?></td>
            <td><?php echo form_val('_order_total', $order); ?></td>
            <td><?php $_date = form_val('post_date', $order); $_date = strtotime($_date);echo date('Y-m-d', $_date); ?></td>
            <td><?php echo form_val('_payment_method_title', $order) ?></td>
            <td><?php echo $order['post_status'] == 'wc-refunded' ? '已退款' : '已付款' ?></td>
        </tr>
        <?php endforeach;?>





<?php
    $orders = db_find('edu_order', array('user_id' => $user_id, '!type' => 'refund'));
?>
<?php if ($orders): ?>
<?php foreach ($orders as $key => $order): ?>
    <tr>
        <td><?php echo form_val('class_name', $classes[$order['class_id']]); ?><br><?php echo form_val('month', $order); ?></td>
        <td><?php echo form_val('amount', $order); ?></td>
        <td><?php echo date('Y-m-d', form_val('order_date', $order)); ?></td>
        <td><?php echo form_val('gateway', $order); ?></td>
    </tr>
    <?php endforeach;?>
<?php endif;?>
    </tbody>
</table>
<style>
thead{font-weight: bold;}
tbody{font-size:14px;}
</style>