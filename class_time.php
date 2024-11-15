<?php
    define('DEBUG', '1');
    include __DIR__.'/core/init.php';
    $id        = get('class');
    $class     = db_find_one('edu_class', array('class_id' => $id));
    $class['date_month'] = decode_json($class['date_month']);
    $date_month = '';
    foreach ($class['date_month'] as $key => $value) {
        $date_month .= $value.',';
    }
    $date_month = substr($date_month,0, -1);

    if (post('handle')=='delete')
    {
        db_delete('edu_class', array('class_id'=>$id));
        db_delete('edu_class_user', array('class_id'=>$id));
        msg(1, 'Success');
    }

    $date_time = post('date_time');
    if (!empty($date_time))
    {
        $date_month = post('date_month');
        $date_month = explode(',', $date_month);
        foreach ($date_month as $key => $value) {
            $date_month[$key] = trim($value);
        }
        $date_month = encode_json($date_month);
        if (mb_substr($date_time, 0, 1) != '逢')
        {
            $date_time = '逢'.$date_time;
        }
        db_update('edu_class', array('date_time' => $date_time, 'date_month'=>$date_month,'class_name' => $class['product_name'].mb_substr($date_time, 1)), array('class_id' => $id));
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
    <script src="https://cdn.jsdelivr.net/gh/jquery/jquery@1.7.2/jquery.min.js"></script>
    <script src="https://toms.cc/assets/msg.min.js"></script>
    <script src="https://toms.cc/assets/jquery.simphp.min.js"></script>
    <script src="https://libs.simphp.com/layer/3.2.0/layer.js"></script>
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<table class="table">
    <tbody>
        <tr>
            <td style="width: 50px">班级</td>
            <td><?php echo $class['class_name']; ?></td>
        </tr>
        <tr>
            <td>时间</td>
            <td><?php echo form_text('date_time', $class); ?></td>
        </tr>
        <tr>
            <td>月份</td>
            <td>
                <?php echo form_text('date_month', $date_month); ?>
                <p tl>請用英文逗號隔開,例:1月,2月</p>
            </td>
        </tr>
        <tr>
            <td colspan="2"><button class="form-button save">保存</button></td>
        </tr>
        <tr>
            <td colspan="2"><button class="form-button del">永久刪除此班級所有資料</button></td>
        </tr>
    </tbody>
</table>
</body>
</html>
<style>
html,body{background: #fff;}
table{background: #fff;}
</style>
<script>
$(function(){
    $("button.save").click(function(event) {
        $.ajax({
            url: '#',
            type: 'POST',
            data: {date_time: $("input[name=date_time]").val(),date_month: $("input[name=date_month]").val()},
            success:function(msg){
                top.$msg.success('Success');
                top.location.reload();
            }
        });
    });

    $("button.del").click(function(event) {
        layer.open({type:1,title: '是否確認刪除?', content:'<div style="padding: 10px;"><button class="form-button mgt delete_btn">確認刪除</button></div>'});
    });
    $('body').on('click', 'button.delete_btn', function () {
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'delete'},
            success:function(msg){
                top.$msg.success('Success');
                top.location.href = 'classes.php';
            }
        });
    });

});
</script>