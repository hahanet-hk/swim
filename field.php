<?php
include __DIR__.'/core/init.php';
$lv1_id = get('lv1');
$lv2_id = get('lv2');
$handle = post('handle');
if ($handle=='add_lv1') {
    $data = array();
    $data['pid'] = 0;
    $data['name'] = post('name');
    db_insert('edu_level', $data);
    msg(1, 'Success','#');
}
if ($handle=='add_lv2') {
    $data = array();
    $data['pid'] = $lv1_id;
    $data['name'] = post('name');
    db_insert('edu_level', $data);
    msg(1, 'Success','#');
}
if ($handle=='add_item') {
    $data = array();
    $data['pid'] = $lv2_id;
    $data['name'] = post('name');
    $insert = array();
    $insert['name'] = post('name');
    $insert['type'] = post('type');
    $insert['item'] = post('item');
    $insert['required'] = post('required');
    $data['data'] = encode_json($insert);
    db_insert('edu_level', $data);
    msg(1, 'Success','#');
}

if ($handle=='update_item') {
    $data = array();
    $data['id'] = post('id');
    $data['pid'] = $lv2_id;
    $data['name'] = post('name');
    $insert = array();
    $insert['name'] = post('name');
    $insert['type'] = post('type');
    $insert['item'] = post('item');
    $insert['required'] = post('required');
    $data['data'] = encode_json($insert);
    $data['file_level'] = post('file_level');
    db_update('edu_level', $data, array('id'=>$data['id']));
    msg(1, 'Success','#');
}

if ($handle == 'update_lv') {
    $data = array();
    $data['name'] = post('val');
    db_update('edu_level', $data, array('id'=>post('id')));
    msg(1, 'Success', '#');
}

if ($handle == 'delete_lv') {
    $pid = post('id');
    if (db_find_one('edu_level', array('pid'=>$pid))) {
        msg(0, '刪除失敗, 請先刪除當前分類下所有的評估項目!');
    }else{
        db_delete('edu_level', array('id'=>$pid));
        msg(1, 'Success');
    }
}


$level = db_find('edu_level');
?>
<?php include __DIR__.'/core/common_header.php'; ?>
<script src="https://libs.simphp.com/webuploader/webuploader.nolog.min.js"></script>
<div class="h3">評估項目</div>
<div class="level section pd">
    <ul>
        <li><b>課程</b>:
            <?php foreach (arrlist_search($level, array('pid'=>0)) as $key => $value): ?>
                <a <?php if ($lv1_id == $value['id']): ?>class="on"<?php endif ?> href="?lv1=<?php echo $value['id'] ?>" data-id="<?php echo $value['id'] ?>"><?php echo $value['name'] ?></a>
            <?php endforeach ?></li>
        <li class="mgt"><b>級別</b>:
            <?php if ($lv1_id): ?>
                <?php foreach (arrlist_search($level, array('pid'=>$lv1_id)) as $key => $value): ?>
                    <a <?php if ($lv2_id == $value['id']): ?>class="on"<?php endif ?> href="?lv1=<?php echo $lv1_id ?>&lv2=<?php echo $value['id'] ?>" data-id="<?php echo $value['id'] ?>"><?php echo $value['name'] ?></a>
                <?php endforeach ?></li>
            <?php endif ?>
        </li>
    </ul>
</div>
<div class="list">
    <table class="table">
        <thead>
            <tr>
                <th>評估項目</th>
                <th>答題類型</th>
                <th>選項(單選/多選必填)</th>
                <th>是否必填</th>
                <th>修改</th>
            </tr>
        </thead>
        <tbody class="field_list">
            <?php if ($lv2_id): ?>
                <?php foreach (arrlist_search($level, array('pid'=>$lv2_id)) as $key => $value): ?>
                        <?php $item = decode_json($value['data']); ?>
                        <tr>
                            <td>
                                <input type="text" value="<?php echo $item['name'] ?>" name="name" class="form-text"><?php echo form_hidden('id', $value['id']) ?><?php echo form_handle('update_item') ?>
                                <div class="file_level">
                                    <input type="text" value="<?php echo $value['file_level'] ?>" name="file_level" class="form-text"><span class="uploader"><i class="fa fa-upload"></i></span>
                                </div>
                            </td>
                            <td>
                                <form class="label">
                                    <label><input type="radio" name="type" value="text"  <?php if ($item['type']=='text'): ?>checked="checked"<?php endif ?> >文字</label>
                                    <label><input type="radio" name="type" value="number" <?php if ($item['type']=='number'): ?>checked="checked"<?php endif ?>>數字</label>
                                    <label><input type="radio" name="type" value="time" <?php if ($item['type']=='time'): ?>checked="checked"<?php endif ?>>時間</label>
                                    <label><input type="radio" name="type" value="radio" <?php if ($item['type']=='radio'): ?>checked="checked"<?php endif ?>>單選</label>
                                    <label><input type="radio" name="type" value="checkbox" <?php if ($item['type']=='checkbox'): ?>checked="checked"<?php endif ?>>多選</label>
                                </form>
                            </td>
                            <td>
                                <label><textarea name="item" class="form-textarea" placeholder="選項(單選/多選必填);每行一個選項"><?php echo $item['item'] ?></textarea></label>
                            </td>
                            <td><?php echo form_switch('required', $item['required']); ?></td>
                            <td>
                                <button class="form-button update_item">保存</button>
                                <button class="form-button delete_item mgt">刪除</button>
                            </td>
                        </tr>
                <?php endforeach ?>
            <?php endif ?>
        </tbody>
    </table>
</div>
<div class="section add">
    <table class="table">
            <tr>
                <td colspan="4"><?php echo form_text('lv1') ?></td>
                <td><button class="form-button add_lv1">增加課程</button></td>
            </tr>
        <?php if ($lv1_id): ?>
            <tr>
                <td colspan="4"><?php echo form_text('lv2') ?></td>
                <td><button class="form-button add_lv2">增加級別</button></td>
            </tr>
        <?php endif ?>
        <?php if ($lv2_id): ?>
            <tr>
                <td>
                    <input type="text" value="" name="name" class="form-text" placeholder="請輸入評估項目, eg: 遊泳時間"><?php echo form_handle('add_item') ?>
                </td>
                <td>
                    <form class="label">
                        <label><input type="radio" name="type" value="text">文字</label>
                        <label><input type="radio" name="type" value="number">數字</label>
                        <label><input type="radio" name="type" value="time">時間</label>
                        <label><input type="radio" name="type" value="radio">單選</label>
                        <label><input type="radio" name="type" value="checkbox">多選</label>
                    </form>
                </td>
                <td>
                    <label><textarea name="item" class="form-textarea" placeholder="選項(單選/多選必填);每行一個選項"></textarea></label>
                </td>
                <td><?php echo form_switch('required') ?>必填</td>
                <td><button class="form-button add_item">增加評估項目</button></td>
            </tr>
        <?php endif ?>
    </table>
</div>
</div>
</body>
</html>
<style>
.wrapper .form-textarea{height: 118px;resize: none;overflow: hidden;padding: 3px 5px;}
.add td{border: none !important;}
.level ul li a{margin-left: 8px;}
.level ul li a.on{color: #125d5b;font-weight: bold;}
.level ul li{padding-left: 45px;text-indent: -45px;}
form.label>label{margin: 5px 0px;display: block;}
div.file_level{position: relative;}
div.file_level span.uploader{position: absolute;width: 60px;top:0;bottom:0;z-index: 10;background: #000;color: #fff;right:0;font-size: 18px;text-align:center;line-height:35px;cursor: pointer;}
</style>


<script>
$(function(){
    $("input[form_switch]").form_switch();
    $(".add_lv1").click(function(event) {
        var val =  $(".add input[name=lv1]").val();
        if (!val) {
            return;
        }
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'add_lv1', name: val},
            success:function(msg){
                $msg.success(msg.msg,function(){
                    window.location.reload();
                });
            }
        });
    });

<?php if (!empty($lv1_id)): ?>
    $(".add_lv2").click(function(event) {
        var val =  $(".add input[name=lv2]").val();
        if (!val) {
            return;
        }
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'add_lv2', name: val, pid: <?php echo $lv1_id; ?>},
            success:function(msg){
                $msg.success(msg.msg,function(){
                    window.location.reload();
                });
            }
        });
    });
<?php endif ?>

    $(".add_item").click(function(event) {
        $(this).parents('tr').send();
        window.location.reload();
    });

    $(".level ul li a").contextMenu({
        target: function(ele) {
            $_data_id = ele.attr('data-id');
            $_data_txt = ele.text();
        },
        menu: [{
                text: "修改",
                callback: function() {
                    layer.open({type:2,title: '請輸入新的名字', content:'popup_field.php?id='+$_data_id});
                }
            },
            {
                text: "刪除",
                callback: function() {
                    layer.open({type:1,title: '是否確認刪除?', content:'<div style="padding: 10px;">請確認該分類下所有評估項目已刪除!<button class="form-button mgt delete_btn" data-id="'+$_data_id+'">確認刪除</button></div>'});
                }
            }
        ]
    });

    $("body").on('click', 'button.update_btn', function(event) {
        var data_id = $(this).prev('input').attr('data-id');
        var data_val = $(this).prev('input').val();
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'update_lv', id:data_id, val: data_val},
            success:function(msg){
                $msg.success(msg.msg);
                window.location.reload();
            }
        });
    });

    $("body").on('click', 'button.delete_btn', function(event) {
        var data_id = $(this).attr('data-id');
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'delete_lv', id:data_id},
            success:function(msg){
                layer.closeAll();
                if (msg.code) {
                    $msg.success(msg.msg, function(){
                        window.location.reload();
                    });
                }else{
                    $msg.error(msg.msg);
                }

            }
        });
    });

    $("body").on('click', 'button.update_item', function(event) {
        $(this).parents('tr').send();
    });

    $("body").on('click', 'button.delete_item', function(event) {
        $_data_id = $(this).parents('tr').find('input[name=id]').val();
        layer.open({type:1,title: '是否確認刪除?', content:'<div style="padding: 10px;"><button class="form-button mgt delete_btn" data-id="'+$_data_id+'">確認刪除</button></div>'});
    });

    $('span.uploader').each(function(index,el){
        GUID = WebUploader.Base.guid();
        var uploader = WebUploader.create({
            runtimeOrder: 'html5',
            server: 'uploader.php',
            auto: true,
            pick: el,
            resize: false,
            chunked: true,
            chunkSize: 1.5 * 1024 * 1024,
            compress: false,
            threads:1,
            formData: {
                guid: GUID
            }
        });
       uploader.on( 'uploadSuccess', function(file, msg) {
           $(el).prev('input').val(msg.url);
       });
   });


});
</script>