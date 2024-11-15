<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Education</title>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@1.11.2/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/layui-laydate@5.3.1/src/laydate.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/layui-laydate@5.3.1/src/theme/default/laydate.min.css" rel="stylesheet">
    <script src="https://libs.simphp.com/laypage/laypage.js"></script>
    <script src="https://libs.simphp.com/layer/3.2.0/layer.js"></script>
    <script src="https://toms.cc/assets/msg.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker.standalone.min.css">
    <script src="https://toms.cc/assets/jquery.simphp.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="/edu2/assets/main.js?<?php echo time() ?>"></script>
    <link rel="stylesheet" href="/edu2/assets/style.css?<?php echo time() ?>">
</head>
<body>
<iframe src="tool/auto_update_class_user.php" frameborder="0" style="display: none;"></iframe>
<div class="wrapper">
    <div class="menu">
        <ul>
            <?php if ($is_admin): ?>
                <li><a href="/edu2/">班級管理</a></li>
                <li><a href="/edu2/attendance.php">點名管理</a></li>
                <li style="display: none;"><a href="user.php">用戶管理</a></li>
                <li><a href="/edu2/field.php">評估項目</a></li>
                <li><a href="/edu2/analysis.php">統計分析</a></li>
                <li><a href="/edu2/history.php">查看成績</a></li>
                <li><a href="/edu2/coach.php">教練管理</a></li>
                <li><a href="/edu2/renew_list.php">續費管理</a></li>
            <?php else: ?>
                <li><a href="/edu2/result.php">教練評分</a></li>
                <li><a href="/edu2/attendance.php">點名管理</a></li>
                <li><a href="/edu2/history.php">查看成績</a></li>
                <li><a href="/my-account/">回到我的賬戶</a></li>
            <?php endif ?>
        </ul>
    </div>