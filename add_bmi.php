<?php
    function calc_bmi($height_cm, $weight_kg)
    {
        if (empty($height_cm) || empty($weight_kg))
        {
            return 0;
        }
        $height_m = $height_cm / 100;
        $bmi      = $weight_kg / ($height_m * $height_m);
        return number_format($bmi, 2);
    }
    include __DIR__.'/core/include.php';
    $user_id = EDU_UID;
    $bmi_id  = get('id');
    $handle  = post('handle');
    if ($handle == 'del')
    {
        db_delete('edu_bmi', array('user_id' => $user_id, 'id' => $bmi_id));
    }
    if ($handle == 'modify')
    {
        $data         = post('data');
        if (empty($data['hc']))
        {
            unset($data['hc']);
        }
        $data['bmi']  = calc_bmi($data['height'], $data['weight']);
        $data['date'] = strtotime($data['date']);
        if ($bmi_id)
        {
            $where            = array();
            $where['user_id'] = $user_id;
            $where['id']      = $bmi_id;
            db_update('edu_bmi', $data, $where);
        }
        else
        {
            $data['user_id'] = $user_id;
            db_insert('edu_bmi', $data);
        }
        msg(1, 'Success');
    }
    $bmi = array();
    if ($bmi_id)
    {
        $bmi         = db_find_one('edu_bmi', array('user_id' => $user_id, 'id' => $bmi_id));
        $bmi['date'] = date('Y-m-d', $bmi['date']);
    }
    if (empty($bmi['date']))
    {
        $bmi['date'] = date('Y-m-d');
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
    <script src="https://cdn.jsdelivr.net/npm/layui-laydate@5.3.1/src/laydate.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/layui-laydate@5.3.1/src/theme/default/laydate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<table class="table">
    <tbody>
        <tr>
            <td style="width: 90px;">身高</td>
            <td><?php echo form_text('height', $bmi, '輸入數字，cm為單位，如100'); ?></td>
        </tr>
        <tr>
            <td>體重</td>
            <td><?php echo form_text('weight', $bmi, '輸入數字，kg為單位，如28'); ?></td>
        </tr>
        <tr>
            <td>頭圍</td>
            <td><?php echo form_text('hc', $bmi, '輸入數字，cm為單位，如38'); ?></td>
        </tr>
        <tr>
            <td>測量日期</td>
            <td><?php echo form_date('date', $bmi); ?></td>
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
</style>
<script>
$(function(){
    $("button").click(function(event) {
        var data = $(".table").serializeObject();
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'modify', data: data},
            success:function(msg){
                top.$msg.success('Success');
                top.location.reload();
            }
        });
    });
    $("input[date]").each(function(index, el) {
        laydate.render({
            elem: el,
            lang: 'en'
        });
    });
});
</script>