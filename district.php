<?php
include __DIR__.'/core/init.php';
if (is_ajax()) {
    $id = post('district_id');
    $name = post('district_name');
    if ($id && $name) {
        db_update('edu_district', array('district_name'=>$name), array('district_id'=>$id));
    }else{
        db_insert('edu_district', array('district_name'=>$name));
    }
    msg(1, 'Success!', '#');
}

$list = db_find('edu_district');

?>
<?php include __DIR__.'/core/common_header.php'; ?>
    <div class="h3">地區管理</div>
    <div class="filter form condition">
        <ul>
        <?php foreach ($list as $key => $value): ?>
            <li><form action="" method="post"><?php echo form_text('district_name', $value) ?><?php echo form_hidden('district_id', $value) ?><button class="form-button">修改</button></form></li>
        <?php endforeach ?>
        </ul>
    </div>
    <form action="" method="post" class="add_district form">
        <div><b>新增區域:</b></div>
        <div style="margin-top: 5px;position: relative;">
            <?php echo form_text('district_name', ''); ?>
            <button class="form-button">新增</button>
        </div>
    </form>

</div>
</body>
</html>
<style>
.condition ul li{line-height: 2;margin: 3px 0;position: relative;}
.condition ul li button{width: 80px;position: absolute;top: 3px;bottom: 3px;right: 3px;z-index: 10;height: auto;line-height: 35px;}
.add_district button{width: 80px;position: absolute;top: 3px;bottom: 3px;right: 3px;z-index: 10;height: auto;line-height: 35px;}
</style>