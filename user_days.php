<?php
    define('DEBUG', 1);
    include __DIR__.'/core/init.php';
    $class_id   = get('class');
    $month      = get('month');
    $next_month = get_string_next_month($month);
    $user_id    = get('user_id');
    $class_year = get('class_year', date('Y'));
    $where      = array('class_id' => $class_id, 'month' => $month, 'user_id' => $user_id, 'class_year' => $class_year);
    $class      = db_find_one('edu_class_user_days', $where);
    $days       = post('days');
    $days       = explode(',', $days);
    $days       = days_sort($days);
    if (!empty($days))
    {
        $post_days = implode(',', $days);
    }
    else
    {
        $post_days = '';
    }

    if (isset($_POST['days']))
    {
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

        if (!empty($next_month_days))
        {
            $next_class_user    = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $next_month, 'class_year' => $class_year));
            $current_class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $month, 'class_year' => $class_year));
            $next_month_days    = implode(',', $next_month_days);

            $is_student = 1;
            if (!in_array($user_id, decode_json($current_class_user['student'])))
            {
                $is_student = 0;
            }

            // 如果不存在下个月班级, 则创建, 把学生更新到对应的班级
            if (!$next_class_user)
            {
                $data               = array();
                $data['sort']       = year_month_sort($class_year, $next_month);
                $data['month']      = $next_month;
                $data['class_id']   = $current_class_user['class_id'];
                $data['class_year'] = $current_class_user['class_year'];
                $data['teacher']    = $current_class_user['teacher'];
                $data['class_exam'] = $current_class_user['class_exam'];
                $_student           = encode_json(array((string) $user_id));
                if ($is_student)
                {
                    $data['student'] = $_student;
                }
                else
                {
                    $data['student_transfer'] = $_student;
                }
                db_insert('edu_class_user', $data);
            }
            else
            {
                $role           = in_class_user($user_id, $current_class_user);
                $class_user_new = meger_class_user($user_id, $current_class_user, $next_class_user);
                unset($class_user_new['id']);
                db_update('edu_class_user', $class_user_new, array('id' => $next_class_user['id']));
            }
        }

        // 更新下月學生日期
        $next_where               = array();
        $next_where['class_id']   = $class_id;
        $next_where['month']      = $next_month;
        $next_where['user_id']    = $user_id;
        $next_where['class_year'] = $class_year;
        if (db_find_one('edu_class_user_days', $next_where))
        {
            db_update('edu_class_user_days', array('days' => $next_month_days), $next_where);
        }
        else
        {
            $next_where['days'] = $next_month_days;
            unset($next_where['id']);

            $r = db_insert('edu_class_user_days', $next_where);
        }

        // 记录所有日期, 方便处理下个月学费
        // 不用 $days = implode(',', $now_month_days);
        if ($class)
        {
            db_update('edu_class_user_days', array('days' => $post_days), $where);
        }
        else
        {
            $data         = $where;
            $data['days'] = $post_days;
            db_insert('edu_class_user_days', $data);
        }
        msg(1, 'Success', '#');
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
            <td>
                <?php
                    $_days = form_val('days', $class);
                    $_days = $_days == '[]' ? '' : $_days;
                    echo form_text('days', $_days);
                ?>
            </td>
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
                top.$.msg(msg);

                // top.$msg.success('Success');
                //
            }
        });
    });
});
</script>