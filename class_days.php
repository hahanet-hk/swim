<?php
    define('DEBUG', 1);

    include __DIR__.'/core/init.php';
    $id         = get('class');
    $month      = get('month');
    $class_year = get('class_year', date('Y'));
    $class      = db_find_one('edu_class_user', array('class_id' => $id, 'month' => $month, 'class_year' => $class_year));
    $days       = post('days');

    if (isset($_POST['days']))
    {
        $days = explode(',', $days);
        $days = days_sort($days);

        // 判斷日期是否當前月, 如果不在當前月, 則創建下個月的班級
        $next_month_days     = array();
        $now_month_days      = array();
        $current_month_array = get_array_month($month);

        foreach ($days as $key => $value)
        {
            $d = date('m', strtotime($value));

            if (!in_array($d, $current_month_array))
            {
                $next_month_days[] = $value;
            }
            else
            {
                $now_month_days[] = $value;
            }
        }

        $days = implode(',', $now_month_days);
        db_update('edu_class_user', array('days' => $days), array('class_id' => $id, 'month' => $month, 'class_year' => $class_year));

        if ($next_month_days)
        {
            $next_month = get_string_next_month($month);
            $days       = implode(',', $next_month_days);
            if (!db_find_one('edu_class_user', array('class_id' => $id, 'month' => $next_month, 'class_year' => $class_year)))
            {
                db_insert('edu_class_user', array('class_id' => $id, 'month' => $next_month, 'class_year' => $class_year, 'days' => $days, 'student' => $class['student'], 'student_transfer' => $class['student_transfer'], 'teacher' => $class['teacher'], 'class_exam' => $class['class_exam'], 'sort' => year_month_sort($class_year, $next_month)));
            }
            else
            {
                db_update('edu_class_user', array('days' => $days, 'sort' => year_month_sort($class_year, $next_month)), array('class_id' => $id, 'month' => $next_month, 'class_year' => $class_year, 'sort' => year_month_sort($class_year, $next_month)));
            }
        }

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
            <td>日期</td>
            <td><?php echo form_text('days', $class); ?></td>
        </tr>
        <tr>
            <td colspan="2"><button class="form-button">保存</button></td>
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
    $("body").on('focus', 'input[name=days]', function(event) {
        $(this).datepicker({
            multidate: true,
            format: 'yyyy-mm-dd'
        });
    });
    $("button").click(function(event) {
        $.ajax({
            url: '#',
            type: 'POST',
            data: {days: $("input[name=days]").val()},
            success:function(msg){
                top.$msg.success('Success');
                top.location.reload();
            }
        });
    });
});
</script>