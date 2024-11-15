<?php
    include __DIR__.'/core/init.php';
    $user_id = get('user_id');
    $month = get('month');
    $where = array();
    $where['%teacher'] = '"'.$user_id.'"';
    if (!empty($month)) {
        $month = date('n', strtotime($month));
        $month = $month.'月';
        $where[] = array('month'=>$month, '|%>month'=>$month.'-', '|%<month'=>'-'.$month);
    }
    $teacher_class = db_find('edu_class_user', $where);
    $classes = db_find('edu_class');
    $classes = arrlist_change_key($classes, 'class_id');
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
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<table class="table">
    <tbody>
        <?php foreach ($teacher_class as $key => $value): ?>
            <tr>
                <td><a href="class.php?class=<?php echo $value['class_id'] ?>&month=<?php echo $value['month'] ?>"><?php echo $classes[$value['class_id']]['class_name'].' ('.$value['month'].')' ?></a></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
</body>
</html>
<style>
html,body{background: #fff;width: 100%;overflow-x: hidden;overflow-y: auto;}
table.table{background: #fff;width: 100%;table-layout:fixed;}
table.table td{font-size: 15px !important;text-align: left !important;padding: 8px 10px;}
</style>
<script>
$(function(){
    $("body").on('click', 'a', function(event) {
        var url = $(this).attr('href');
        event.preventDefault();
        top.window.location.href = url;
    });
});
</script>