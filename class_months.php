<?php
    define('DEBUG', '1');
    include __DIR__.'/core/init.php';
    $class_id = get('class_id');
    $handle   = post('handle');
    if ($handle == 'add')
    {
        $data               = array();
        $data['class_id']   = $class_id;
        $data['month']      = trim(post('month'));
        $data['month']      = month_num_string($data['month']);
        $data['class_year'] = trim(post('class_year'));

        if (!db_find_one('edu_class_user', $data))
        {
            $data['sort'] = year_month_sort($data['class_year'], $data['month']);
            db_insert('edu_class_user', $data);
        }
        msg(1, 'Success', '#');
    }
    if ($handle == 'del')
    {
        $data               = array();
        $data['class_id']   = $class_id;
        $data['month']      = trim(post('month'));
        $data['month']      = month_num_string($data['month']);
        $data['class_year'] = trim(post('class_year'));
        db_delete('edu_class_user', $data);
        msg(1, 'Success', 'class.php?class='.$class_id);
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
    <script src="/edu2/assets/main.js"></script>
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<table class="table">
    <tbody>
        <?php if (get('router')=='show'): ?>
        <tr>
            <td colspan="2"><a href="class.php?class=<?php echo $class_id ?>"><b><u>點擊返回</u> <?php echo get_edu_class($class_id, 'class_name');?></b></a></td>
        </tr>
        <?php endif ?>
        <tr>
            <td>輸入年份</td>
            <td>
                <?php echo form_text('class_year', date('Y')); ?>
            </td>
        </tr>
        <tr>
            <td>輸入月份</td>
            <td>
                <?php echo form_text('month'); ?>
                <p tl>請用英文逗號隔開,例:1月, 2月, 3月-4月</p>
            </td>
        </tr>
        <tr>
            <td colspan="2"><button class="form-button save">新增</button></td>
        </tr>
        <tr>
            <td colspan="2"><button class="form-button del">刪除</button></td>
        </tr>
    </tbody>
</table>
</body>
</html>
<style>
html,body{background: #fff;}
table{background: #fff;}
td u{font-style: normal;font-weight: normal;}
</style>
<script>
$(function(){
    $("button.save").click(function(event) {
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle:'add', class_year: $("input[name=class_year]").val(),month: $("input[name=month]").val()},
            success:function(msg){
                top.$.msg(msg);
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
            data: {handle: 'del', class_year: $("input[name=class_year]").val(),month: $("input[name=month]").val()},
            success:function(msg){
                top.$.msg(msg);
            }
        });
    });

});
</script>