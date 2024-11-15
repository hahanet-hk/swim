<?php
    define('DEBUG', 1);
    include __DIR__.'/core/init.php';

    $where = array();
    if (!$is_admin)
    {
        $where['%teacher'] = '"'.$user['ID'].'"';
    }

    $where['%class_year'] = date('Y');

    $class_user = db_find('edu_class_user', $where, array('id' => 1));

    if (empty($class_user))
    {
        exit('沒有權限!');
    }

    $level_all = db_find('edu_level');

    // 獲取所有的班級
    $classes_id = array();

    foreach ($class_user as $key => $value)
    {
        // 如果不是管理员, 班级的时间又不在当前月范围, 则不加入考试班级列表
        if (!$is_admin && !is_current_month($value['month']))
        {
            continue;
        }
        // 如果班级有学生, 则把班级加入点名
        if (!empty(decode_json($value['student'])) || !empty(decode_json($value['student_transfer'])))
        {
            $classes_id[] = $value['class_id'];
        }
    }

    $classes_id = array_unique($classes_id);
    $classes    = db_find('edu_class', array('class_id' => $classes_id));
    $classes    = arrlist_change_key($classes, 'class_id');
    foreach ($class_user as $key => $value)
    {
        $class_user[$key]['class_name'] = isset($classes[$value['class_id']]['class_name']) ? $classes[$value['class_id']]['class_name'] : '未找到';
    }
    // 根據幼兒/成人等條件搜索班級
    $group = get('group');
    $classes_select = $classes;
    if ($group)
    {
        foreach ($classes_select as $key => $value) {
            if (strpos($value['class_name'], $group, 0) !== 0)
            {
                unset($classes_select[$key]);
                continue;
            }
        }
    }

    $classes_select = classes_sort($classes_select);

    //開始處理已選擇的班級
    $class_id = get('class_id');
    if ($class_id)
    {
        $class = arrlist_search($classes, array('class_id' => $class_id));
        $class = reset($class);
    }
    else
    {
        $class    = reset($classes_select);
        if (empty($class))
        {
            $class = array();
            $class['class_id'] = -1;
            $class['class_name'] = '';
            $class['date_month'] = '';
            $class['class_exam'] = '';
        }
        $class_id = $class['class_id'];
    }

    $months = arrlist_search($class_user, array('class_id'=>$class_id));
    if (empty($months))
    {
        $months = array();
    }
    // 獲取當前月
    $month = get('month');
    if ($month)
    {
        $class_month     = arrlist_search($class_user, array('class_id' => $class_id, 'month' => $month));
        $class_month     = reset($class_month);
    }
    else
    {
        foreach ($months as $key => $value)
        {
            if (is_current_month($value['month']))
            {
                $class_month = $value;
                break;
            }
        }
        if (empty($class_month))
        {
            $class_month = reset($months);
        }
        $month = $class_month['month'];
    }


    $class_user_one = db_find_one('edu_class_user', array('class_id'=>$class_id,'month'=>$month,'%class_year'=>date('Y')));


    $students = array();
    if (!empty($class_month))
    {
        $students_normal = decode_json($class_month['student']);
        $student_transfer = decode_json($class_month['student_transfer']);
        $students_normal = edu_get_user($students_normal);
        $student_transfer = edu_get_user($student_transfer);
        $students = array_merge($students_normal, $student_transfer);
    }

    $class_exam = decode_json($class_user_one['class_exam']);
    $level_select = array();
    $level_list = array();
    if (!empty($class_exam))
    {
        foreach ($class_exam as $_exam_id)
        {
            $_lv          = arrlist_search($level_all, array('id' => $_exam_id));
            $level_list[] = reset($_lv);
        }
    }
    foreach ($level_list as $key => $value)
    {
        $level_select[$value['id']] = $value['name'];
    }

    $level_id = get('level_id');
    $exam     = array();
    if ($level_id && !empty($level_select))
    {
        $level = arrlist_search($level_list, array('id' => $level_id));
        $level = reset($level);
    }
    elseif (!empty($level_select))
    {
        $level    = reset($level_list);
        $level_id = $level['id'];
    }
    else
    {
        $level    = array();
        $level_id = '';
    }

    if ($level_id)
    {
        $exam = db_find('edu_level', array('pid' => $level_id));
    }
    // exam select
    $exam = arrlist_change_key($exam, 'id');
    if (empty($exam))
    {
        $exam = array();
    }
    $exam_select = array();
    foreach ($exam as $key => $value)
    {
        $exam_select[$value['id']] = $value['name'];
    }
    $exam_id = get('exam_id');
    if ($exam_id && !empty($exam))
    {
        $exam = $exam[$exam_id];
    }
    else
    {
        $exam = reset($exam);
    }

    $result = array();
    if (!empty($exam))
    {
        $exam_all = $exam;
        $exam_id = $exam['id'];
        $exam    = decode_json($exam['data']);
        $result  = db_find('edu_result', array('class_id' => $classes_id, 'class_month' => $month, 'exam_id' => $exam_id, 'exam_date' => date('Y-m-d')), array('id' => -1));
    }

    if (empty($result))
    {
        $result = array();
    }
    else
    {
        $result = arrlist_change_key($result, 'user_id');
    }

    // exam select end
    // process result handle
    $handle = post('handle');
    if ($handle == 'result')
    {
        $result_status = 1;
        if (!is_current_month($month))
        {
            msg(0, '請選擇正確的月份!');
        }
        $result = post('result');
        $time   = time();
        foreach ($result as $key => $value)
        {
            $where                = array();
            $where['class_id']    = $class_id;
            $where['class_month'] = $month;
            $where['exam_id']     = $exam_id;
            $where['user_id']     = $key;
            $where['exam_date']   = date('Y-m-d', $time);

            $data                 = $where;
            $data['first_name']   = '';
            $data['last_name']    = '';
            $data['gender']       = '';
            $data['exam_data']    = $value['data'];
            $data['exam_history'] = '';
            // $data['exam_file']    = $value['exam_file'];

            $r = db_find_one('edu_result', $where);

            if ($r)
            {
                $history = decode_json($r['exam_history']);
                if (!is_array($history))
                {
                    $history = array();
                }

                $history[$time]       = $value['data'];
                $data['exam_history'] = $history;
                $result_status = db_update('edu_result', $data, $where);
            }
            else
            {
                $history              = array();
                $history[$time]       = $value['data'];
                $data['exam_date']    = date('Y-m-d', $time);
                $data['exam_history'] = $history;
                $data['created']      = $time;
                $result_status = db_insert('edu_result', $data);
            }
        }
        if ($result_status)
        {
            msg(1, '成績錄入成功!');
        }
        else
        {
            msg(0, '成績保存失敗, 請聯繫管理員!');
        }
    }
?>
<?php include __DIR__.'/core/common_header.php';?>
<script src="https://libs.simphp.com/webuploader/webuploader.nolog.min.js"></script>
    <div class="h3">評估成績</div>
    <div class="filter form condition">
        <div class="wrap">
            <span class="tit">班別:</span>
            <select name="group" class="form-select">
                <option value="">請選擇</option>
                <?php $option_arr = array('幼兒游泳班', '兒童游泳班', '成人游泳班', '暑期幼兒游泳班', '暑期兒童游泳班', '成人四式改良班', '女子成人游泳班');?>
                <?php foreach ($option_arr as $key => $value): ?>
                    <option value="<?php echo $value; ?>"<?php if ($value == get('group')): ?>selected="selected"<?php endif;?>><?php echo $value; ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="wrap">
            <span class="tit">班級:</span>
            <select name="class_id" class="form-select">
                <?php foreach ($classes_select as $key => $value): ?>
                    <option value="<?php echo $value['class_id']; ?>"<?php if ($value['class_id']==$class_id): ?>selected="selected"<?php endif;?>><?php echo $value['class_name']; ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="wrap">
            <span class="tit">月份:</span>
            <select name="month" class="form-select">
                <?php foreach ($months as $key => $value): ?>
                    <option value="<?php echo $value['month']; ?>"<?php if ($value['month']==$month): ?>selected="selected"<?php endif;?>><?php echo $value['month']; ?></option>
                <?php endforeach;?>
            </select>
        </div>

        <?php if ($level_select): ?>
        <div class="wrap">
            <span class="tit">考試分類:</span>
            <?php echo form_select('level_id', $level_select, $level_id); ?>
        </div>
        <?php endif;?>

        <div class="wrap">
            <?php if (empty($exam_select)): ?>
                請聯繫管理員添加相關考試分類!
            <?php endif;?>
            <span class="tit">考試項目</span>
            <?php echo form_select('exam_id', $exam_select, $exam_id); ?>
        </div>


        <?php if (form_val('type', $exam) == 'time'): ?>
            <!-- <script src="https://cdn.jsdelivr.net/npm/sticksy/dist/sticksy.min.js"></script> -->
            <div class="sticky">
                <div class="timebox">
                    <div id="showtime"><span>00</span><span>:</span><span>00</span><span>:</span><span>00</span></div>
                    <div class="bnt"><button>開始</button><button>圈數</button></div>
                    <div id="record"></div>
                </div>
            </div>
            <script>
            $(function(){
                $('.sticky').scrollFix();
            });
            </script>
            <style>
            .sticky{z-index: 999999999999999999;}
            </style>

        <?php endif;?>

    </div>
    <div class="list <?php if (!is_current_month($month)){echo 'not_allowed';} ?>">
        <div class="table">
            <table class="table record">
                <thead>
                    <tr>
                        <th>用戶</th>
                        <th>性別</th>
                        <th>
                            <?php if($exam_all['file_level']): ?>
                                <a class="fa_video" target="_blank" data-id="<?php echo $exam_all['id']?>"><i class="fa fa-video-camera"></i> <?php echo isset($exam['name']) ? $exam['name'] : '成績'; ?></a>
                            <?php else: ?>
                                <?php echo isset($exam['name']) ? $exam['name'] : '成績'; ?>
                            <?php endif; ?>
                        </th>
                        <?php if(0): ?>
                        <th style="width:100px;">圖片/影片</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
<?php $ID = $student['ID'];?>
                    <tr <?php echo in_array($ID, $student_transfer) ? 'student_transfer' : '';?> each_user data-id="<?php echo $ID;?>">
                        <td>
                            <svg t="1716299558807" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1702" width="64" height="64"><path d="M512 512c109.02806282 0 197.75390625-88.69262695 197.75390625-197.75390625S621.02806282 116.4921875 512 116.4921875 314.24609375 205.21803093 314.24609375 314.24609375s88.72584343 197.75390625 197.75390625 197.75390625zM149.45142937 799.96290398v16.24826431C149.45142937 895.14819336 275.25535774 907.5078125 431.44849968 907.5078125h161.10300064c150.06277085 0 281.99707031-12.35961914 281.99707031-91.29664421V799.96367646c0-150.98510742-115.15843391-255.00366211-269.30760383-255.00366212h-186.51437759c-154.1499424 0-269.27515984 103.98533821-269.27515984 255.00366211z" fill="#333333" p-id="1703"></path></svg>
                            <br>
                            <div data-user-score="<?php echo $student['ID'] ?>">
                                <?php echo $student['last_name']; ?><br><?php echo $student['first_name']; ?>
                            </div>

                        </td>
                        <td><?php echo form_val('billing_gender', $student); ?></td>
                        <td>
                            <table class="table exam_list" data-id="<?php echo $student['ID']; ?>">
                                <tr>
                                    <td>
                                        <?php

                                            $result_score = empty($result[$ID]) ? array() : $result[$ID];
                                            $score = form_val('exam_data', $result_score);

                                            $name = 'data';
                                            $type = form_val('type', $exam);
                                            switch ($type)
                                            {
                                                case 'text':
                                                    echo form_text($name, $score);
                                                    break;
                                                case 'number':
                                                    echo "<input type=\"number\" name=\"{$name}\" value=\"".form_val($name, $score).'" class="form-text">';
                                                    break;
                                                case 'time':
                                                    echo "<div class=\"form-time\"><input type=\"text\" name=\"{$name}\" value=\"".form_val($name, $score).'"time readonly class="form-text"><button class="form-button insert_time">錄入時間</button></div>';
                                                    break;
                                                case 'radio':
                                                    $_ = explode("\n", $exam['item']);
                                                    echo '<form>';
                                                    $_sval = form_val($name, $score);
                                                    foreach ($_ as $k1 => $v1)
                                                    {
                                                        $checked = $_sval === $k1 ? 'checked="checked"' : '';
                                                        echo "<label><input type=\"radio\" name=\"{$name}\" value=\"{$k1}\" {$checked}>{$v1}</label>";
                                                    }
                                                    echo '</form>';
                                                    break;
                                                case 'checkbox':
                                                    $_ = explode("\n", $exam['item']);
                                                    echo '<form>';
                                                    $_sval = form_val($name, $score);
                                                    if (!empty($_sval))
                                                    {
                                                        $_sval = decode_json($_sval);
                                                    }
                                                    $_sval = (array) $_sval;

                                                    foreach ($_ as $k1 => $v1)
                                                    {
                                                        $checked = in_array($k1, $_sval) ? 'checked="checked"' : '';
                                                        echo "<label><input type=\"checkbox\" name=\"{$name}[]\" value=\"{$k1}\" {$checked}>{$v1}</label>";
                                                    }
                                                    echo '</form>';
                                                    break;
                                                default:
                                                    // echo form_text('f'.$key, $score);
                                                    break;
                                            }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
<?php if(0): ?>
    <td>
        <?php echo form_file('exam_file', $result_score);?>
    </td>
<?php endif; ?>
                    </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="section">
        <?php if (is_current_month($month)) {?>
            <button class="form-button">保存成績</button>
        <?php } ?>
    </div>


</div>
</body>
</html>
<style>
.form-time{position: relative;}
.timebox{margin:10px auto;width:100%;background: #333;border-radius: 6px;padding: 10px;z-index: 999;}
.timebox .bnt{text-align: center;}
.timebox #showtime{margin:20px;margin-bottom: 20px;text-align: center;}
.timebox span{font-size: 60px;color: #fff;}
.timebox button{width:100px;height:100px;border-radius: 50px;border:0;outline:none ;margin:0 48px;font-size:24px;}
.timebox #record{margin-top:20px;}
.timebox #record div{height:30px;border-bottom:1px dotted #666;}
.timebox #record span{font-size:20px;}
.timebox .left{float:left;}
.timebox .right{float:right;}
td .form-button{width:72px;height: 60px;line-height:60px;background: #333;color: #fff;cursor: pointer;border-radius: 4px;transition: all .3s;width: 100%;}
td .form-button:hover{background: #666;}
/*table.record{table-layout: fixed;}*/
table.record td:nth-child(1){width: 72px;}
table.record td:nth-child(1) img{width:45px;}
table.record td:nth-child(2){width:50px;}
table.record table td:nth-child(1){width: 100px;}
table.record>tbody>tr>td:last-child{padding: 0;}
table.record>tbody>tr{border-bottom: solid 3px #ddd;}
table.record table{border: hidden !important;}
table.record table td form{padding: 10px 5px;text-align:left;line-height: 1.6;}
table.record table td form label{margin: 0 6px;}
table tr[student_transfer]{background: rgba(0,172,78,0.06);}
table tr[student_transfer]:hover{background: rgba(0,172,78,0.2);}

.fa_video{background: #1e9eff;display: block;}

.insert_time.disabled{cursor: not-allowed;background: #ddd;}
.insert_time.disabled:hover{cursor: not-allowed;background: #ddd;}
form label{display: inline-block;}
.not_allowed{position: relative;}
.not_allowed:after{content:'';position: absolute;z-index: 999;width: 100%;height:100%;background:rgba(192,102,102,0.5);top:0;bottom:0;left:0;right:0;cursor: not-allowed;}

.webuploader-pick{line-height: inherit;text-align: center;padding: 0 15px;color: #fff;background:#1e9fff;height: 35px;line-height: 35px;border-radius: 4px;}
.webuploader-container { position: relative;overflow: hidden;}
.webuploader-element-invisible { position: absolute !important; clip: rect(1px 1px 1px 1px);}
.webuploader-pick-hover { background: #007cd8; }
.webuploader-pick-disable { opacity: 0.6; pointer-events: none; }
.layui-layer-iframe{overflow: visible !important;}
@media all and (max-width: 650px)
{
    table.table table td:first-child{width: 60px;font-size: 14px;}
    table.table td:nth-child(1){width: 30px;font-size: 12px;}
    table.table td:nth-child(1) img{width:25px;}
    .timebox #showtime{margin: 0;}
    .timebox .bnt{display: flex;justify-content: space-between;width:180px;margin-left: auto;margin-right: auto;}
    .timebox button{width: 80px;height:80px;margin: 0px;font-size: 21px;font-weight: bold;border-radius: 50%;}
    .timebox button:first-child{margin-bottom: 15px;}

    .timebox span{font-size: 38px;}
    .timebox #record{margin-top: 0;}
    .scrollfix-top{width: 190px !important; }
    .scrollfix-top .timebox .bnt{width: 150px;}
    .scrollfix-top .timebox .bnt button{width: 60px;height: 30px;font-size: 15px;border-radius: 6px;}
    td .form-button:hover{background:#333 !important;}
    .insert_time.disabled:hover{cursor: not-allowed;background: #ddd !important;}
    table.record>tbody>tr>td:nth-child(2){width: 40px;}
}
</style>
<script>
$(function(){
    document.oncontextmenu = function () {
        return false;
    }
    $("select[name=group]").on('change', function(event) {
        window.location.href='?group='+$(this).val();
    });

    $("select[name=class_id]").on('change', function(event) {
        window.location.href='?group=<?php echo get('group'); ?>&class_id='+$(this).val();
    });
    $("select[name=month]").on('change', function(event) {
        window.location.href='?group=<?php echo get('group'); ?>&class_id=<?php echo get('class_id'); ?>&month='+$(this).val();
    });
    $("select[name=level_id]").on('change', function(event) {
        window.location.href='?group=<?php echo get('group'); ?>&class_id=<?php echo get('class_id'); ?>&month=<?php echo get('month'); ?>&level_id='+$(this).val();
    });
    $("select[name=exam_id]").on('change', function(event) {
        window.location.href='?group=<?php echo get('group'); ?>&class_id=<?php echo get('class_id'); ?>&month=<?php echo get('month'); ?>&level_id=<?php echo get('level_id'); ?>&exam_id='+$(this).val();
    });


    $("input[form_switch]").form_switch();


    $(".section .form-button").click(function(event) {
        result = {};
        $("tr[each_user]").each(function(index, el) {
            var student_id = $(this).attr('data-id');
            var data = $(this).serializeObject();
            console.log(data);
            result[student_id] = data;
        });
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'result', result:result},
            success:function(msg){
                $msg.success(msg.msg);
            }
        });
    });
    $("a.fa_video").click(function(event){
        event.preventDefault();
        var id = $(this).attr('data-id');
        layer.open({type:2, area: ['350px', '280px'], content: "video_player.php?id="+id, title: false});
    });

    $("button.insert_time").click(function(event) {
        if (!$(this).hasClass('disabled'))
        {
            if ($("#showtime").text()=='00:00:00')
            {
                $msg.error('請開始Stopwatch!');
                return false;
            }

            $(this).prev('input').val($("#showtime").text());

            if ($(this).prev('input').val().length>3) {
                $(this).addClass('disabled');
            }

            result = {};
            $ele = $(this).parents('.exam_list');
            var student_id = $ele.attr('data-id');
            var data = $ele.serializeObject();
            result[student_id] = data;
            $.ajax({
                url: '#',
                type: 'POST',
                data: {
                    handle: 'result',
                    result: result
                },
                success: function(msg) {
                    if(msg.code)
                    {
                        $msg.success(msg.msg);
                    }
                    else
                    {
                        $msg.error(msg.msg);
                    }
                }
            });
        }
    });

    $('span.form_upload').each(function(index,el){
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

    $("body").on('click', '[data-user-score]', function(event) {
        event.preventDefault();
        var user_id = $(this).attr("data-user-score");
        layer.open({type:2, area: ['350px', '400px'], content: "history_score_user.php?user_id="+user_id, title: '歷史成績' });
    });




});
</script>
<script>
//添加事件
$(function(){
        var min=0;
        var sec=0;
        var ms=0;
        var timer=null;
        var count=0;
//點擊第一個按鈕
    $('.bnt button:eq(1)').click(function(){
        if($(this).html()=='圈數'){
            if(!timer){
                alert("沒有開啟定時器!");
                return;
            }
                count++;
                var right1="<span class='right'>"+$('#showtime').text()+"</span>";
                var insertStr = "<div><span class='left'>圈數"+count+"</span>" +right1+"</div>";
                $("#record").prepend($(insertStr));
        }else{
            min=0;
            sec=0;
            ms=0;
            count=0;
            $('#showtime span:eq(0)').html('00');
            $('#showtime span:eq(2)').html('00');
            $('#showtime span:eq(4)').html('00');
            $('#record').html('');
            }
    });
//點擊第二個按鈕
    $('.bnt button:eq(0)').click(function(){
        if($(this).html()=='開始'){
            $(this).html('暫停');
            $('.bnt button:eq(1)').html('圈數');
            clearInterval(timer);
            timer=setInterval(show,10)
        }else{
            $(this).html('開始');
            $('.bnt button:eq(1)').html('重設');
            clearInterval(timer);
        }
    });
//生成時間
    function show(){
        ms++;
        if(sec==60){
            min++;sec=0;
        }
        if(ms==100){
            sec++;ms=0;
        }
        var msStr=ms;
        if(ms<10){
            msStr="0"+ms;
        }
        var secStr=sec;
        if(sec<10){
            secStr="0"+sec;
        }
        var minStr=min;
        if(min<10){
            minStr="0"+min;
        }
        $('#showtime span:eq(0)').html(minStr);
        $('#showtime span:eq(2)').html(secStr);
        $('#showtime span:eq(4)').html(msStr);
    }
})
</script>