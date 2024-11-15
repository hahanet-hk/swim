<?php
    define('DEBUG', 1);
    include __DIR__.'/core/init.php';
    $student_id          = get('student_id');
    $user_id             = $student_id;
    $order_id            = get('order_id');
    $classes             = db_find('edu_class');
    $classes             = arrlist_change_key($classes, 'class_id');
    $order               = db_find_one('edu_order', array('id' => $order_id));
    $order['class_name'] = $classes[$order['class_id']]['class_name'];
    $handle              = post('handle');
    if ($handle == 'order_refund')
    {
        $data = post('data');
        if (empty($data['refund_reason']) && empty($data['refund_reason2']))
        {
            msg(0, '退款原因不能為空!');
        }
        if (empty($data['refund_fee']))
        {
            msg(0, '退款金額不能為空!');
        }
        if (empty($data['refund_date']))
        {
            msg(0, '退款日期不能為空!');
        }

        if ($data['refund_fee'] > $order['amount'])
        {
            msg(0, '退款金額不能超過支付金額!');
        }
        if ($data['refund_reason2'])
        {
            $data['refund_reason'] = $data['refund_reason2'];
        }
        unset($data['refund_reason2']);
        $data['refund_date'] = strtotime($data['refund_date']);
        db_update('edu_order', $data, array('id' => $order_id));
        msg(1, 'Success');
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@1.11.2/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker.standalone.min.css">
    <script src="https://toms.cc/assets/msg.min.js"></script>
    <script src="https://toms.cc/assets/jquery.simphp.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="/edu2/assets/main.js"></script>
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<table class="table">
    <tbody>
        <tr>
            <td style="width: 50px;">班級</td>
            <td style="width: 290px;" tl>
                <?php echo $order['class_name']; ?>
            </td>
        </tr>
        <tr>
            <td>年份</td>
            <td tl><?php echo form_val('class_year', $order); ?></td>
        </tr>
        <tr>
            <td>月份</td>
            <td tl>
                <?php echo form_val('month', $order); ?>
            </td>
        </tr>
        <tr>
            <td>支付金額</td>
            <td tl><?php echo form_val('amount', $order); ?></td>
        </tr>
        <tr>
            <td>退款金額</td>
            <?php $refund_fee = form_val('refund_fee', $order) ? form_val('refund_fee', $order) : form_val('amount', $order) ?>
            <td><?php echo form_text('refund_fee', $refund_fee); ?></td>
        </tr>
        <tr>
            <td>退款原因</td>
            <td>
                <label><input type="radio" name="refund_reason" value="沒有足夠人數開班">沒有足夠人數開班</label>
                <label><input type="radio" name="refund_reason" value="缺少教練">缺少教練</label>
                <label><?php echo form_text('refund_reason2', form_val('refund_reason', $order), '其他退款原因, 請輸入'); ?></label>
            </td>
        </tr>
        <tr>
            <td>退款日期</td>
            <td><?php echo form_date('refund_date', date('Y-m-d')); ?></td>
        </tr>
        <tr>
            <td colspan="2"><button class="form-button">保存</button></td>
        </tr>
    </tbody>
</table>
</body>
</html>
<style>
html,body{background: #fff;width: 100%;overflow-x: hidden;overflow-y: auto;}
table{background: #fff;width: 100%;table-layout:fixed;}
td select{min-width: 200px;width: 100% !important;}
.select2-container{font-size: 13px;}
.datepicker-dropdown{width: 230px;}
</style>
<script>
$(function(){
    $('select.form-select2-ajax').on("change", function(e) {
        $('select[name=month]').val(null).trigger('change');
    });

    $("button").click(function(event) {
        var data = $(".table").serializeObject();
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle:'order_refund', data: data},
            success:function(msg){
                if (msg.code==0) {
                    top.$msg.error(msg.msg);
                }else{
                    top.$msg.success('Success');
                    top.location.reload();
                }

            }
        });
    });
});
</script>