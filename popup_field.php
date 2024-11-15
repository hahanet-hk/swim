<?php
    include __DIR__.'/core/init.php';
    $handle = post('handle');
    $id = get('id');
    if (empty($id)) exit;
    $data = db_find_one('edu_level', array('id'=>$id));
    if ($handle=='update')
    {
        $update = array();
        $update['name'] = post('name');
        $update['link'] = post('link');
        db_update('edu_level', $update, array('id'=>$id));
        msg(1, 'Success','#');
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
        <tr>
            <td>名稱</td>
            <td><?php echo form_text('name', $data); ?></td>
        </tr>
        <tr>
            <td>連結</td>
            <td><?php echo form_text('link', $data); ?></td>
        </tr>
        <tr>
            <td colspan="2"><?php echo form_handle('update');?><button class="form-button">保存</button></td>
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
</script>