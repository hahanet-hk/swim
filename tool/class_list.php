<?php
    include __DIR__.'/../core/init.php';
    include __DIR__.'/../core/common_header.php';
    $class_list = db_find('edu_class');
?>
    <div class="h3">所有班級列表</div>
    <div class="section pd">
        <div id="class_list">
            <ul>
                <?php foreach ($class_list as $key => $value): ?>
                    <li><a target="_blank" href="/edu2/class.php?class=<?php echo $value['class_id'] ?>" target="_blank"><i><?php echo $key+1 ?></i><?php echo $value['class_name'] ?></a></li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
#class_list ul li{padding: 8px;}
#class_list ul li a{display: block;line-height: 36px;cursor: pointer;background: #ddd;border-radius: 6px;font-weight: bold;transition: all .3s;padding: 0 15px 0 5px;}
#class_list ul li a i{font-style: normal;text-align: center;display: block;width: 38px;float: left;}
#class_list ul li a:hover{background: #258671;color: #fff;}
</style>
