<?php
    define('DEBUG', 1);
    include __DIR__.'/core/init.php';
    $no_renew                     = db_find('edu_order_status', array('status' => 1));
    $no_renew                     = arrlist_change_key($no_renew, 'order_id');
    $month                        = get('month', date('Y-m'));
    $renew_type                   = get('type');
    $prev_next                    = get_prev_next_month($month);
    $now_timestamp                = strtotime($month.'-1');
    $next_timestamp               = strtotime('+1 month', $now_timestamp);
    $next_date                    = date('Y-m', $next_timestamp);
    list($next_year, $next_month) = explode('-', $next_date);
    if (substr($next_month, 0, 1) == '0')
    {
        $next_month = substr($next_month, 1);
    }
    $next_month                     = $next_month.'月';
    list($class_year, $class_month) = explode('-', $month);
    if (substr($class_month, 0, 1) == '0')
    {
        $class_month = substr($class_month, 1);
    }

    $class_month_array   = array();
    $class_month_array[] = $class_month.'月';
    $class_month_array[] = ($class_month - 1).'月-'.$class_month.'月';
    $class_month_array[] = $class_month.'月-'.($class_month + 1).'月';
    $class_users         = db_find('edu_class_user', array('class_year' => $class_year, 'month' => $class_month_array));
    $order_woo           = get_order_list();
    $order_whatsapp      = db_find('edu_order', array('order_source' => 'manual', '>created' => (time() - 86400 * 90)));
    $orders              = array();

    foreach ($order_woo as $key => $value)
    {
        if (empty($value['user_id']))
        {
            continue;
        }
        $order                   = array();
        $order['user_id']        = $value['user_id'];
        $order['class_year']     = $value['class_year'];
        $order['month']          = $value['month'];
        $order['class_name']     = $value['class_name'];
        $order['order_renew_id'] = 'woo_'.$value['order_id'];
        $orders[]                = $order;
    }
    foreach ($order_whatsapp as $key => $value)
    {
        if (empty($value['user_id']))
        {
            continue;
        }
        $order                   = array();
        $order['user_id']        = $value['user_id'];
        $order['class_year']     = empty($value['class_year']) ? 2024 : $value['class_year'];
        $order['month']          = $value['month'];
        $order['class_name']     = get_edu_class($value['class_id'], 'class_name');
        $order['order_renew_id'] = 'whatsapp_'.$value['id'];
        $orders[]                = $order;
    }

    $student = array();
    foreach ($class_users as $key => $value)
    {
        $_student           = decode_json($value['student']);
        $_students_transfer = decode_json($value['student_transfer']);
        $student            = array_merge($student, $_student);
        $student            = array_merge($student, $_students_transfer);
    }
    $students          = edu_get_user($student);
    $next_month_length = mb_strlen($next_month);

    foreach ($students as $key => &$student)
    {
        $paid      = 0;
        $_no_renew = 0;
        foreach ($orders as $key => $order)
        {
            if ($order['user_id'] != $student['ID'])
            {
                continue;
            }
            $_month_arr = get_array_month($order['month']);
            if ($class_year == $order['class_year'] && in_array($class_month, $_month_arr) && !empty($no_renew[$order['order_renew_id']]))
            {
                $_no_renew = 1;
            }
            if ($order['class_year'] == $next_year)
            {
                if ($order['month'] == $next_month || mb_substr($order['month'], 0, $next_month_length) == $next_month || mb_substr($order['month'], -$next_month_length) == $next_month)
                {
                    $paid = 1;
                    break;
                }
            }
        }
        if ($paid == 0 && $_no_renew == 1)
        {
            $paid = 2;
        }
        $student['paid'] = $paid;
    }
    $students = arrlist_sort($students, array('paid' => 1));
?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3">
        學員列表
        <div><a href="?month=<?php echo date('Y-m'); ?>">點擊進入<b>本月</b>學員列表</a></div>
    </div>
    <div class="section month">
        <ul>
            <?php foreach ($prev_next as $key => $value): ?>
            <li><a href="?month=<?php echo $value; ?>"<?php echo $key == 3 ? 'on' : ''; ?>><?php echo $value; ?></a></li>
            <?php endforeach;?>
        </ul>
    </div>
    <div class="section renew_filter">
        <ul>
            <li><a href="?month=<?php echo get('month'); ?>&type=all"<?php if ($renew_type == '' || $renew_type == 'all'): ?> class="form-button"<?php endif;?>>全部</a></li>
            <li><a href="?month=<?php echo get('month'); ?>&type=0"<?php if ($renew_type == 0): ?> class="form-button"<?php endif;?>>未續費</a></li>
            <li><a href="?month=<?php echo get('month'); ?>&type=1"<?php if ($renew_type == 1): ?> class="form-button"<?php endif;?>>已續費</a></li>
            <li><a href="?month=<?php echo get('month'); ?>&type=2"<?php if ($renew_type == 2): ?> class="form-button"<?php endif;?>>不續費</a></li>
        </ul>
    </div>
    <!-- 1, 已續費, 0 未續費 2 不續費 -->
    <div class="section pd">
        <table table>
            <tr>
                <td>編號</td>
                <td>中文名</td>
                <td>英文名</td>
                <td>電話</td>
                <td>泳班</td>
                <td>續費</td>
            </tr>
            <?php foreach ($students as $key => $value): ?>
<?php
    if ($renew_type == 0 && $value['paid'] != 0)
    {
        continue;
    }
    if ($renew_type == 1 && $value['paid'] != 1)
    {
        continue;
    }
    if ($renew_type == 2 && $value['paid'] != 2)
    {
        continue;
    }
?>
                <tr>
                    <td tc><?php echo $key; ?></td>
                    <td><a href="student.php?user_id=<?php echo $value['ID']; ?>"><?php echo $value['first_name']; ?></a></td>
                    <td><a href="student.php?user_id=<?php echo $value['ID']; ?>"><?php echo $value['last_name']; ?></a></td>
                    <td><a href="student.php?user_id=<?php echo $value['ID']; ?>"><?php echo $value['billing_phone']; ?></a></td>
                    <td>
                        <?php
                            $class_users = arrlist_search($orders, array('user_id' => $value['ID']));

                            foreach ($class_users as $k2 => $v2)
                            {
                                echo $v2['class_name'].'('.$v2['month'].')<br>';
                            }
                        ?>
                    </td>
                    <td>
                        <?php
                            switch ($value['paid'])
                            {
                                case 0:
                                    echo '<i>未續費</i>';
                                    break;
                                case 1:
                                    echo '<u>已續費</u>';
                                    break;
                                default:
                                    echo '<s>不續費</s>';
                                    break;
                        }?>
                    </td>
                </tr>
            <?php endforeach;?>
        </table>
    </div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
.month ul{text-align: center;}
.month ul li{display: inline-block;margin: 0 5px;font-weight: bold;}
.month ul li a{display: block;padding: 8px 5px;color: #888;}
.month ul li a[on]{color: red;}
.renew_filter{padding: 5px 0;}
.renew_filter ul{text-align: center;}
.renew_filter li{display: inline-block;}
.renew_filter li a{display: block;width: 100px;cursor: pointer;height: 40px;line-height: 40px;border-radius: 6px;background: #eee;transition: none;}
.renew_filter li a.form-button{background: linear-gradient(45deg, rgb(13,83,86) 0%,rgb(69,203,149) 100%);}
.h3 div{font-weight: normal;color: red;font-size: 16px;margin-top: 8px;}
.h3 div b{font-weight: bold;font-size: 18px;}
table[table]{width: 100%;}
table[table] td{padding: 3px 5px;}
table[table] td a{display: block;}
table[table] tr:nth-child(2n+1){background:rgba(0, 0, 0, 0.06);}
table[table] tr:hover{background: #00ac4e;color: #fff;text-decoration: underline;}
table[table] tr:hover a{color: #fff;text-decoration: underline;}
table[table] tr td i{color: red;font-style: normal;}
table[table] tr td u{color: green;font-style: normal;}
table[table] tr td s{color: blue;font-style: normal;text-decoration: line-through;}
</style>