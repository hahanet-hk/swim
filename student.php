<?php
    define('DEBUG', 1);
    include __DIR__.'/core/init.php';
    $student_id = get('user_id');
    $user_id    = $student_id;
    $no_more_renew = db_find('edu_order_status', array('user_id'=>$user_id,'status'=>1));
    $no_more_renew = arrlist_change_key($no_more_renew,'order_id');
    if (empty($student_id))
    {
        exit('student not exists!');
    }
    $student = get_user_one($student_id);
    // 設置每節課的費用
    if (isset($_POST['class_fee']))
    {
        $k = 'user_class_fee_'.$student_id.'#'.post('class_id');
        kv_set($k, post('class_fee'));
        msg(1, 'Success');
    }
    // 獲取每節課費用
    $student_class_fee = db_find_one('edu_user', array('user_id' => $user_id));
    $student_class_fee = empty($student_class_fee['class_fee']) ? 0 : $student_class_fee['class_fee'];
    // 獲取教練每節課費用
    $hourly_wage = 200;
    $u           = db_find_one('edu_user', array('user_id' => $student_id));
    if (!empty($u['hourly_wage']))
    {
        $hourly_wage = $u['hourly_wage'];
    }
    // 下載教練工資單
    if (isset($_GET['download']))
    {
        $calc_ymd   = get('month', date('Y-m'));
        $calc_month = date('n月 ', strtotime($calc_ymd));
        $calc       = calc_salary($student_id, $calc_ymd, $hourly_wage);
        $rt_index   = 0;
        $rt         = array();
        $rt_index++;
        $rt[] = array($student['billing_first_name'].'薪資表('.$calc_ymd.')', '', '', '', '');
        $rt_index++;
        $rt[$rt_index] = array('班級', '上課日期', '堂數', '每堂(HK$)', '合計(HK$)');
        foreach ($calc['classes'] as $key => $value)
        {
            $rt_index++;
            $d = $calc_month;
            foreach ($value['days2'] as $k2 => $v2)
            {
                $k2 = date('j', strtotime($k2));
                $d .= $k2.', ';
            }
            $d             = substr($d, 0, -2);
            $num           = count($value['days2']);
            $rt[$rt_index] = array($value['class_name'], $d, $num, $hourly_wage, $num * $hourly_wage);
        }
        $rt_index++;
        $rt[$rt_index] = array('', '', '', '', '');
        $rt_index++;
        $rt[$rt_index] = array('', '平日入場費', $calc['workday_num'], '17', $calc['workday_fee']);
        $rt_index++;
        $rt[$rt_index] = array('', '周末入場費', $calc['weekend_num'], '19', $calc['weekend_fee']);
        $rt_index++;
        $rt[$rt_index] = array('', '', '', '合計', $calc['amount']);
        $file_name     = $student['billing_first_name'].$calc_ymd.'_'.time().'.xlsx';
        header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($file_name).'"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $style1 = array('border' => 'left,right,top,bottom', 'border-color' => '#666666', 'border-style' => 'medium', 'halign' => 'center', 'valign' => 'center', 'height' => 22, 'font' => 'Microsoft Jhenghei', 'font-size' => 12, 'font-style' => 'bold');
        $style2 = array('border' => 'left,right,top,bottom', 'border-color' => '#666666', 'border-style' => 'medium', 'valign' => 'center', 'height' => 18, 'font' => 'Microsoft Jhenghei', 'font-size' => 10, 'font-style' => 'bold');
        $style3 = array('border' => 'left,right,top,bottom', 'border-color' => '#666666', 'border-style' => 'medium', 'valign' => 'center', 'height' => 18, 'font' => 'Microsoft Jhenghei', 'font-size' => 10);
        $writer = new XLSXWriter();
        $sheet1 = '薪資';
        $header = array("string", "string", "string", "string", "string", "string");
        $writer->writeSheetHeader($sheet1, $header, array('suppress_row' => true, 'widths' => array(55, 40, 10, 10, 10), 'border' => 'left,right,top,bottom', 'border-color' => '#000000', 'border-style' => 'thin'));
        foreach ($rt as $key => $row)
        {
            if ($key == 0)
            {
                $writer->writeSheetRow($sheet1, $row, $style1);
                continue;
            }
            if ($key == 1)
            {
                $writer->writeSheetRow($sheet1, $row, $style2);
                continue;
            }
            $writer->writeSheetRow($sheet1, $row, $style3);
        }
        $writer->markMergedCell($sheet1, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 4);
        $writer->writeToStdOut();
        exit;
    }
    $user_id    = $student_id;
    $attendance = db_find('edu_attendance', array('user_id' => $student_id));
    $classes    = db_find('edu_class');
    $classes    = arrlist_change_key($classes, 'class_id');
    $handle     = post('handle');

    if ($handle=='renew_stop')
    {
        $_order_id = post('order_id');
        $type = substr($_order_id, 0, 4) == 'woo_' ? 'woo' :'whatsapp';
        $data = array();
        $data['order_id'] = $_order_id;
        $data['type'] = $type;
        $data['status'] = 1;
        $data['user_id'] = $user_id;
        db_replace('edu_order_status', $data);
        msg(1, 'Success', '#');
    }

    if ($handle == 'delete')
    {
        db_delete('edu_order', array('id' => post('id')));
        msg(1, 'Success!');
    }
    if (isset($_POST['note']))
    {
        $note  = post('note');
        $where = array('user_id' => $student_id);
        $data  = array('note' => $note);
        if (db_find_one('edu_user', $where))
        {
            db_update('edu_user', array('note' => $note), $where);
        }
        else
        {
            db_insert('edu_user', array_merge($where, $data));
        }
        msg(1, 'Success');
    }
    if (isset($_POST['no_renew']))
    {
        $no_renew  = post('no_renew', 0);
        $where = array('user_id' => $student_id);
        $data  = array('no_renew' => $no_renew);
        if (db_find_one('edu_user', $where))
        {
            db_update('edu_user', array('no_renew' => $no_renew), $where);
        }
        else
        {
            db_insert('edu_user', array_merge($where, $data));
        }
        msg(1, 'Success','#');
    }


    $student   = edu_get_user($student_id);
    $student   = reset($student);
    $order     = db_find('postmeta', array('meta_key' => '_customer_user', 'meta_value' => $student_id));
    $order_ids = array();
    foreach ($order as $key => $value)
    {
        $order_ids[] = $value['post_id'];
    }
    $order_list = array();
    if (!empty($order_ids))
    {
        $list = db_find('postmeta', array('@woocommerce_order_items.order_id' => 'postmeta.post_id', '@posts.ID' => 'postmeta.post_id', 'postmeta.post_id' => $order_ids, 'posts.post_status' => array('wc-completed', 'wc-refunded')));
        foreach ($list as $key => $value)
        {
            if (empty($order_list[$value['order_id']]))
            {
                $order_list[$value['order_id']] = $value;
            }
            $order_list[$value['order_id']][$value['meta_key']] = $value['meta_value'];
        }
    }

    foreach ($order_list as $key => $order)
    {
        if (strpos($order['order_item_name'], '游泳班') === false && strpos($order['order_item_name'], '泳隊訓練') === false)
        {
            unset($order_list[$key]);
            continue;
        }
        if (strpos($order['order_item_name'], ' - ') === false)
        {
            unset($order_list[$key]);
            continue;
        }
        if (strpos($order['order_item_name'], ',') === false)
        {
            unset($order_list[$key]);
            continue;
        }

        list($product_name, $datetime) = explode(' - ', $order['order_item_name']);
        list($pa_month, $pa_time)      = explode(',', $datetime);
        $product_name                  = trim($product_name);
        $pa_month                      = trim($pa_month);
        $pa_time                       = trim($pa_time);
        $class_name                    = $product_name.mb_substr($pa_time, 1);
        $class                         = db_find_one('edu_class', array('class_name' => $class_name));
        if (empty($class))
        {
            unset($order_list[$key]);
            continue;
        }
        $order_list[$key]['class_name'] = $class_name;
        $order_list[$key]['class_id'] = $class['class_id'];
        $order_list[$key]['month']      = $pa_month;
        $order_list[$key]['time']       = $pa_time;
        $_date_time = strtotime($order['post_date']);
        $_y         = date('Y', $_date_time);
        $order_month         = date('n', $_date_time);

        $class_month = get_array_month($order['order_item_name']);
        $class_month = reset($class_month);

        // 如果報名時間大於10月份, 班級時間小於4月份
        if ($order_month>9 && $class_month<5)
        {
            $_y = $_y+1;
        }
        $order_list[$key]['class_year'] = $_y;
    }

    $whatsapp_orders = db_find('edu_order', array('user_id' => $student_id, 'order_source' => 'manual'));

    $order_all = $order_list;
    foreach ($whatsapp_orders as $key => $value) {
        $order_all[] = $value;
    }

    $note = db_find_one('edu_user', array('user_id' => $user_id));
    include __DIR__.'/core/common_header.php';
?>
<form action="" method="post">
<h3>個人資料</h3>
<div class="table">
    <table class="table">
        <tr>
            <td>中文名</td>
            <td><?php echo form_val('billing_first_name', $student); ?></td>
            <td>英文名</td>
            <td><?php echo form_val('billing_last_name', $student); ?></td>
        </tr>
        <tr>
            <td>出生日期</td>
            <td><?php echo form_val('billing_birthdate', $student); ?></td>
            <td>年齡</td>
            <td><?php echo calculate_age(form_val('billing_birthdate', $student)); ?></td>
        </tr>
        <tr>
            <td>學校/公司</td>
            <td><?php echo form_val('billing_school', $student); ?></td>
            <td>地址</td>
            <td><?php echo form_val('billing_address_1', $student); ?></td>
        </tr>
        <tr>
            <td>電話</td>
            <td><?php echo form_val('billing_phone', $student); ?></td>
            <td>郵箱</td>
            <td><?php echo form_val('billing_email', $student); ?></td>
        </tr>
        <tr>
            <td>緊急聯絡人</td>
            <td><?php echo form_val('billing_contactname', $student); ?></td>
            <td>緊急聯絡人電話</td>
            <td><?php echo form_val('billing_contactphone', $student); ?></td>
        </tr>
        <tr hidden>
            <td>終止續費</td>
            <td><?php echo form_radio('no_renew', form_val('no_renew', $note)); ?></td>
        </tr>
    </table>
</div>
<?php $is_user_teacher = db_find_one('edu_class_user', array('%teacher' => '%'.$user_id.'%'));
?>
<?php if ($is_user_teacher): ?>
    <h3>教練上課記錄</h3>
    <div class="table teacher_classes_record">
        <ul>
        <?php
            $calc = calc_salary($student_id, get('month', date('Y-m')), $hourly_wage);
            foreach ($calc['classes'] as $key => $value)
            {
                echo '<li><div>'.$value['class_name'].' &nbsp; ('.$value['month'].')</div>';
                foreach ($value['days2'] as $k2 => $v2)
                {
                    echo '<span>'.$k2.'</span>';
                }
                echo '</li>';
            }
        ?>
        <p>平日入場費:<?php echo $calc['workday_num']; ?> * 17 = $<?php echo $calc['workday_fee']; ?></p>
        <p>周末入場費:                            <?php echo $calc['weekend_num']; ?> * 19 = $<?php echo $calc['weekend_fee']; ?></p>
        <p>課時薪資:                         <?php echo $calc['class_num']; ?> *<?php echo $hourly_wage; ?> = $<?php echo $calc['class_fee']; ?></p>
        <p><b>合計薪資</b>: $<?php echo $calc['amount']; ?></p>
        </ul>
        <div>
            <div class="h3">按月查詢</div>
            <ul class="month_list">
            <?php
                $startDate = new DateTime('2024-08-01');
                $endDate   = new DateTime();
                while ($startDate <= $endDate)
                {
                    $_month = $startDate->format('Y-m');
                    $_class = '';
                    if (get('month') == $_month)
                    {
                        $_class = 'on';
                    }
                    elseif (!get('month') && date('Y-m') == $_month)
                    {
                        $_class = 'on';
                    }
                    echo '<li '.$_class.'><a href="?user_id='.$user_id.'&month='.$_month.'">'.$_month.'</a></li>';
                    $startDate->modify('+1 month');
                }
            ?>
            </ul>
        </div>
        <div style="text-align: center;padding-bottom: 15px;"><a href="?user_id=<?php echo $user_id; ?>&month=<?php echo get('month', date('Y-m')); ?>&download" class="form-button" style="width: 150px;">下載薪資表</a></div>
    </div>
    <style>
    .teacher_classes_record {overflow: hidden !important;background: #fff;padding:5px 10px;}
    .teacher_classes_record ul{display: block;}
    .teacher_classes_record ul li {padding: 5px;background:rgba(0, 172, 78, 0.02);margin:6px 0;border-radius: 4px;border: 1px solid rgba(0, 172, 78, 0.2);}
    .teacher_classes_record ul li div{margin: 0 3px;font-weight: bold;font-size: 14px;}
    .teacher_classes_record ul li span{margin: 0 3px;padding: 0 4px;display: inline-block;font-size: 14px;background:rgba(0, 172, 78, 0.15);border-radius: 4px;}
    .teacher_classes_record p{line-height: 1.6}
    .month_list li{display: inline-block;margin: 0 5px !important;}
    .month_list li[on]{background: #00ac4e;color: #fff;}
    </style>
<?php endif;?>
<?php if (!$is_user_teacher): ?>
<!-- 學生頁面 -->
<h3>出席記錄</h3>
<div style="padding: 15px;background: #fff;border-radius: 0 0 6px 6px;border: solid 1px #ddd;">
<?php
    $class_months = db_find('edu_class_user', array(array('%student' => '"'.$user_id.'"', '|%student_transfer' => '"'.$user_id.'"')));
?>
<?php if (empty($class_months)): ?>
    <p tc>沒有任何記錄</p>
<?php else: ?>
<?php foreach ($class_months as $key => $value): ?>
<?php
    $attends      = get_user_class_month_attend($user_id, $value['class_id'], $value['month'], $value['class_year']);
    $attends_text = attend_array_text($attends);
?>
        <div class="tit"><?php echo $classes[$value['class_id']]['class_name']; ?> (<?php echo $value['month']; ?>)</div>
        <div class="txt">
            <?php foreach ($attends_text as $k2 => $v2): ?>
                <?php echo attend_text($k2); ?>:<?php echo $v2; ?><br>
            <?php endforeach;?>
        </div>
    <?php endforeach;?>
<?php endif;?>
<style>
.tit{margin-bottom: 0;font-weight: bold;}
.txt{margin-bottom: 15px;}
</style>
</div>
<div id="loading_fee">
    <h3>網上報名</h3>
    <div class="table">
        <table class="table">
            <thead>
                <tr>
                    <td>訂單</td>
                    <td>班級</td>
                    <td>學費</td>
                    <td>交費日期</td>
                    <td>每堂學費</td>
                    <td>最後一堂</td>
                    <td>下次付款</td>
                    <td>管理</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_list as $key => $order): ?>
<?php
    if ($order['post_status'] != 'wc-completed')
    {
        continue;
    }
?>
                <tr>
    <?php

        list($product_name, $datetime) = explode(' - ', $order['order_item_name']);
        list($pa_month, $pa_time)      = explode(',', $datetime);
        $product_name                  = trim($product_name);
        $pa_month                      = trim($pa_month);
        $pa_time                       = trim($pa_time);
        $class_name                    = $product_name.mb_substr($pa_time, 1);
        $class                         = db_find_one('edu_class', array('class_name' => $class_name));
        if (empty($class))
        {
            continue;
        }

        $next = get_next_year_month($order['class_year'], $order['month']);
        $next_where = array();
        $next_where['class_id'] = $order['class_id'];
        $next_where['month'] = $next['month'];
        $next_where['class_year'] = $next['class_year'];
        $renew_status = arrlist_search_one($order_all, $next_where) ? 1 : 0;
        $renew_stop_status = empty($no_more_renew['woo_'.$order['order_id']]) ? 0 : 1;
        $renew_button_text = $renew_status ? '已續費' : '待續費';
        if ($renew_stop_status)
        {
            $renew_button_text = '不續費';
        }

    $_system_year = date('Y');
    $_system_month = date('n');
    if (date('j')<16)
    {
        $_system_month = $_system_month-1;
        if (date('n')==1)
        {
            $_system_year = $_system_year-1;
        }
    }
    $renew_current = (is_current_year($order['class_year'], $_system_year) && is_current_month($order['month'], $_system_month)) ? 1 : 0;
        // $renew_current = 1;
        // 獲取當前班級的年
        $renew           = 0;
        $order_date      = form_val('_paid_date', $order);
        $order_timestamp = strtotime($order_date);
        $order_y         = date('Y', $order_timestamp);
        $order_n         = date('n', $order_timestamp);
        $month_last      = get_array_month($pa_month);
        $month_last      = end($month_last);
        if ($order_n > $month_last)
        {
            $class_year = $order_y + 1;
        }
        else
        {
            $class_year = $order_y;
        }
        $_amount = form_val('_order_total', $order);
        $renew   = get_renew_price2($student_id, $class_name, $pa_month, $class_year = $class_year, $_amount);
    ?>
                    <td><?php echo $order['order_id']; ?></td>
                    <td tl>

                        <?php if ($class): ?>
                            <a href="class.php?class=<?php echo $class['class_id']; ?>" target="_blank"><?php echo $class_name; ?>(<b><?php echo $pa_month; ?></b>)</a>
                        <?php else: ?>
<?php echo form_val('order_item_name', $order); ?>
<?php endif;?>
                    </td>
                    <td><?php echo form_val('_order_total', $order); ?></td>
                    <td><?php $_date = form_val('_paid_date', $order);
                        echo $_date ? date('Y-m-d', strtotime($_date)) : '';?></td>
                    <td><?php echo $renew['price']; ?></td>
                    <td><?php echo empty($renew['class_days']) ? '' : end($renew['class_days']); ?></td>
                    <td><?php echo $renew['renew']; ?></td>
                    <td handle renew_current<?php echo $renew_current ?>>
                        <?php if (form_val('renew', $renew)): ?>
                            <u hidden renew_status<?php echo $renew_status ?> order_renew order-id="0" order-class_id="<?php echo $class['class_id']; ?>" order-month="<?php echo $pa_month; ?>" order-renew="<?php echo form_val('renew', $renew); ?>"><?php echo $renew_button_text ?></u>
                            <?php if ($renew_stop_status): ?>
                                <s>不續費</s>
                            <?php endif ?>
                        <?php endif;?>
                    </td>
                </tr>
                <?php if ($renew_current && !$renew_status && !$renew_stop_status): ?>
                <tr>
                    <td colspan="8" tl>
                        <div renew_text style="background: #eee;padding: 15px;border-radius: 4px;line-height: 1.5;">
                            <?php echo $renew['text']; ?>
                        </div>
                        <div renew_btns>
                            <div>
                                <span renew_status<?php echo $renew_status ?> order_renew order-id="0" order-class_id="<?php echo $class['class_id']; ?>" order-month="<?php echo $pa_month; ?>" order-renew="<?php echo form_val('renew', $renew); ?>">繼續報名</span>
                            </div>
                            <div>
                                <span renew_stop order-id="woo_<?php echo form_val('order_id', $order); ?>">不續費</span>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endif;?>
<?php endforeach;?>
            </tbody>
        </table>
    </div>
    <h3>WhatsApp報名</h3>
    <div class="table">
        <table class="table table1">
            <thead>
                <tr>
                    <td>訂單</td>
                    <td>班級</td>
                    <td>學費</td>
                    <td>交費日期</td>
                    <td>退款</td>
                    <td>退款原因</td>
                    <td>退款日期</td>
                    <td>付款方式</td>
                    <td>下次付款</td>
                    <td tc>管理</td>
                </tr>
            </thead>
            <tbody>
        <?php

        ?>
<?php if ($whatsapp_orders): ?>
<?php foreach ($whatsapp_orders as $key => $order): ?>
<?php
    $_class_name = !empty($classes[$order['class_id']]) ? form_val('class_name', $classes[$order['class_id']]) : '';
    $order['class_year'] = $order['class_year'] ? $order['class_year'] : date('Y');
    $_month      = form_val('month', $order);
    $_amount     = form_val('amount', $order);
    $class_year  = empty($order['class_year']) ? date('Y') :  $order['class_year'];
    $next = get_next_year_month($order['class_year'], $order['month']);
    $next_where = array();
    $next_where['class_id'] = $order['class_id'];
    $next_where['month'] = $next['month'];
    $next_where['class_year'] = $next['class_year'];
    $renew_status = arrlist_search_one($order_all, $next_where) ? 1 : 0;
    $renew_button_text = $renew_status ? '已續費' : '待續費';
    $renew_stop_status = empty($no_more_renew['whatsapp_'.$order['id']]) ? 0 : 1;
    if ($renew_stop_status)
    {
        $renew_button_text = '不續費';
    }
    // $renew_current = (is_current_year($order['class_year']) && is_current_month($order['month'])) ? 1 : 0;
    // $renew_current = 1;
    $_system_year = date('Y');
    $_system_month = date('n');
    if (date('j')<16)
    {
        $_system_month = $_system_month-1;
        if (date('n')==1)
        {
            $_system_year = $_system_year-1;
        }
    }
    $renew_current = (is_current_year($order['class_year'], $_system_year) && is_current_month($order['month'], $_system_month)) ? 1 : 0;


    if ($_class_name)
    {
        $renew = get_renew_price2($student_id, $_class_name, $_month, $class_year, $_amount);
    }
    else
    {
        $renew = 0;
    }
?>
            <tr>
                <td><?php echo form_val('id', $order); ?></td>
                <td tl><a href="class.php?class=<?php echo $order['class_id']; ?>&month=<?php echo $_month; ?>" target="_blank"><?php echo $_class_name; ?>(<b><?php echo $_month; ?></b>)</a></td>
                <td><?php echo $_amount; ?></td>
                <td><?php echo date('Y-m-d', form_val('order_date', $order)); ?></td>
                <td><?php echo form_val('refund_fee', $order); ?></td>
                <td><?php echo form_val('refund_reason', $order); ?></td>
                <td><?php echo form_val('refund_date', $order) ? date('Y-m-d', form_val('refund_date', $order)) : ''; ?></td>
                <td><?php echo form_val('gateway', $order); ?></td>
                <td tc><?php echo form_val('renew', $renew); ?></td>
                <td tc handle renew_current<?php echo $renew_current ?>>
                    <u hidden renew_status<?php echo $renew_status ?> order_renew order-id="<?php echo form_val('id', $order); ?>" order-renew="<?php echo form_val('renew', $renew); ?>"><?php echo $renew_button_text; ?></u>
                    <?php if ($renew_stop_status): ?>
                        <s>不續費</s>
                    <?php endif ?>
                    <u order_refund order-id="<?php echo form_val('id', $order); ?>">退款</u>
                    <u order_del order-id="<?php echo form_val('id', $order); ?>">刪除</u>
                </td>
            </tr>
            <?php if ($renew_current && !$renew_status && !$renew_stop_status): ?>
            <tr>
                <td colspan="11" tl>
                    <div renew_text style="background: #eee;padding: 15px;border-radius: 4px;line-height: 1.5;">
                        <?php echo $renew['text']; ?>
                    </div>
                    <div renew_btns>
                        <div><span renew_status<?php echo $renew_status ?> order_renew order-id="<?php echo form_val('id', $order); ?>" order-renew="<?php echo form_val('renew', $renew); ?>">繼續報名</span></div>
                        <div><span renew_stop order-id="whatsapp_<?php echo form_val('id', $order); ?>">不續費</span></div>
                    </div>
                </td>
            </tr>
            <?php endif;?>
<?php endforeach;?>
<?php endif;?>
            </tbody>
        </table>
    </div>
</div>
<div style="text-align: right;" mgt><span class="form-button" add_order style="width: 60px;"><i class="fa fa-plus"></i> 新增</span></div>
<?php endif;?>
<!-- 學生頁面結束 -->
<h3>備忘</h3>
<textarea name="note" class="form-textarea" placeholder="學生停學、補堂次數資料.....等事項"><?php echo form_val('note', $note); ?></textarea>
<button class="form-button mgt">保存</button>
</form>
<a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
h3{text-align: center;background: #00ac4e;color: #fff;padding: 8px;border-radius: 6px 6px 0 0;margin-top: 15px;font-size: 16px;}
div.table{width: 100%;overflow: auto;}
table.table{width: 100%;border-spacing:0;border-collapse: collapse;background: #fff;border-radius: 0 0 6px 6px;border: solid 1px #ddd;box-sizing: border-box;overflow: visible;border: hide;min-width: 720px;}
table.table td{border: solid 1px #ddd;padding: 6px;}
textarea{border-radius: 0 0 6px 6px!important;padding: 6px !important;resize: none;min-height: 100px;}
tfoot{display: none;}
i.fa-close{cursor: pointer;}
span.class_fee{display: inline-block;width: 50px;}
span.class_fee input{background: none;border: none;border-bottom: solid 1px #fff;border-radius: 0;outline: none;height: 30px;line-height: 30px;text-align: center;font-weight: bold;font-size: 16px;padding: 0;}
span.class_fee input:focus{outline: none !important;border: none;border-bottom: solid 1px #fff;}
[handle] u{cursor: pointer;}
[handle] u:hover{text-decoration: underline;}
[renew_current1] [renew_status0]{color: red;font-weight: bold;}
[handle] s{cursor: not-allowed;color: red;text-decoration: line-through;user-select: none;}
[renew_status1]{color: green;}
[renew_btns]{overflow: hidden;}
[renew_btns] div{width: 50%;float: left;text-align: center;border: 1px solid #fff;}
[renew_btns] span{display: block;background:#33a681;width: 100%;line-height: 36px;font-size: 15px;color: #fff;font-weight: bold;border-radius: 6px;cursor: pointer;user-select: none;transition: all .3s;}
[renew_btns] span:hover{background: #248470;}
</style>
<script>
$(function(){
    $("[add_order]").click(function(event) {
        layer.open({type:2,title:'新增報名',area:['360px','520px'],content: 'order_add.php?student_id=<?php echo $student_id; ?>'});
    });
    $("[add_refund]").click(function(event) {
        layer.open({type:2,title:'新增退款',area:['360px','520px'],content: 'order_refund.php?student_id=<?php echo $student_id; ?>'});
    });
    $("body").on('click', 'u[order_del]', function(event) {
        var id = $(this).attr('order-id');
        layer.confirm('確認刪除?', {
            btn: ['確認', '取消'] //按鈕
        }, function() {
            $.ajax({
                url: '#',
                type: 'POST',
                data: {handle: 'delete',id:id},
                success:function(msg){
                    $msg.success('Success');
                    window.location.reload();
                }
            });
        }, function() {
        });
    });
    $("body").on('click', 'u[order_modify]', function(event) {
        var id = $(this).attr('order-id');
        layer.open({type:2,title:'修改',area:['360px','520px'],content: 'order_add.php?student_id=<?php echo $student_id; ?>'});
    });

    $("body").on('click', '[renew_stop]', function(event) {
        event.preventDefault();
        var order_id = $(this).attr('order-id');
        $.ajax({
            url: '#',
            type: 'POST',
            dataType: 'json',
            data: {handle: 'renew_stop', order_id: order_id},
            success: function(msg){
                top.$.msg(msg);
            }
        });

    });

    $("body").on('click', '[order_renew]', function(event) {
        var order_id = $(this).attr('order-id');
        var order_renew = $(this).attr('order-renew');
        if (order_id=="0"){
            var class_id = $(this).attr('order-class_id');
            var month = $(this).attr('order-month');
        }else{
            var class_id = 0;
            var month = 0;
        }
        layer.open({type:2,title:'續費',area:['360px','520px'],content: 'order_renew.php?student_id=<?php echo $student_id; ?>&order_id='+order_id+'&order_renew='+order_renew+'&class_id='+class_id+'&month='+month});
    });
    $("body").on('click', 'u[order_refund]', function(event) {
        var order_id = $(this).attr('order-id');
        layer.open({type:2,title:'退款',area:['360px','520px'],content: 'order_refund.php?student_id=<?php echo $student_id; ?>&order_id='+order_id});
    });
    $("input[class_fee]").each(function(index, el) {
        $(this).data('data-val', $(this).val());
    });
    $("#loading_fee").on('click', 'input[class_fee]', function(event) {
        event.preventDefault();
        event.stopPropagation();
    });
    $("#loading_fee").on('blur', 'input[class_fee]', function(event) {
        $this = $(this);
        var v = $(this).val();
        var dv = $(this).data('data-val');
        if(v != dv){
            var d = {};
            d['user_id'] =                           <?php echo $user_id; ?>;
            d['class_id'] = $(this).attr('class_id');
            d['class_fee'] = v;
            $.ajax({
                url: '#',
                type: 'POST',
                dataType: 'json',
                data: d,
                success: function(msg)
                {
                    $this.data('data-val', $this.val());
                    $.msg(msg, function(){
                        $('#loading_fee').load('student.php?user_id=<?php echo $user_id; ?> #loading_fee>*', function(){
                            $("input[class_fee]").each(function(index, el) {
                                $(this).data('data-val', $(this).val());
                            });
                        });
                    });
                }
            })
        }
    });
    $("#loading_fee").on('keypress', 'input[class_fee]', function(event) {
        if (event.which === 13)
        {
            event.preventDefault()
            $(this).blur();
        }
    });
    $("body").on('click', '[renew_text]', function(event) {
        event.preventDefault();
        $ele = $($(this).prop('outerHTML'));
        $ele.find("._rm").remove();
        var text = '';
        $ele.find('p').each(function() {
            text += $(this).text() + '\n';
        });
        text = text.trim();
        var $textarea = $('<textarea>').val(text).appendTo('body').select();
        document.execCommand('copy');
        $textarea.remove();
        $msg.success('Text has been copied to the clipboard!');
    });
    $("input[name=no_renew]").click(function(event) {
        $.ajax({
            url: '#',
            type: 'POST',
            dataType: 'json',
            data: {no_renew: $(this).val()},
            success:function(msg)
            {
                top.$.msg(msg);
            }
        });

    });
});
</script>