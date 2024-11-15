<?php

    include __DIR__.'/core/init.php';

    $handle = post('handle');

    if ($is_admin && $handle == 'delete_score')
    {
        $_id = post('id');
        if ($_id)
        {
            $time = time();
            $rd = db_find_one('edu_result', array('id'=>$_id));
            $history = decode_json($rd['exam_history']);
            if (!is_array($history)) {
                $history = array();
            }
            $history[] = array($time=>'');
            db_update('edu_result', array('exam_data'=>'', 'exam_history'=>$history), array('id'=>$_id) );
            db_insert('edu_admin_log', array('created'=>time(),'handle'=>'刪除','edu_result_id'=>$_id, 'before'=>$rd['exam_data'], 'after'=>'', 'admin_user_id'=>EDU_UID));
        }
        msg(1, 'Success!');
    }

    $classes     = db_find('edu_class');
    $classes     = arrlist_change_key($classes, 'class_id');
    $class_id    = get('class_id');
    $class_month = get('class_month');
    $exam_date   = get('exam_date');
    $record      = db_find('edu_result', array('class_id' => $class_id, 'class_month' => $class_month, 'exam_date' => $exam_date));

    $exam_ids = array();
    foreach ($record as $key => $value) {
        $exam_ids[] = $value['exam_id'];
    }
    $exam_ids = array_unique($exam_ids);


    $level = db_find('edu_level');
    $level = arrlist_change_key($level, 'id');

    // $first = reset($record);
    // $lv_id = $first['exam_id'];
    // $lv2   = arrlist_search($level, array('id' => $lv_id));
    // $lv2   = reset($lv2);
    // $exams = arrlist_search($level, array('pid' => $lv2['pid']));


    $class_ids = array();
    if (!$is_admin) {
        $class_ids = [];
        foreach ($class_user as $key => $value)
        {
            $class_ids[] = $value['class_id'];
        }

        if (!in_array($class_id, $class_ids )) {
            exit;
        }
    }




    $users = array();
    foreach ($record as $key => $value)
    {
        $users[] = $value['user_id'];
    }
    $users = edu_get_user($users);



    if ($is_admin && $handle == 'result')
    {

        $time = time();
        $where                = array();
        $where['class_id']    = $class_id;
        $where['class_month'] = $class_month;
        $where['exam_date']   = $exam_date;

        $data = post('result');
        foreach ($data as $user_id => $user_score)
        {
            foreach ($user_score as $exam_id => $exam_data)
            {
                $exam_id           = substr($exam_id, 4);
                $where['exam_id']  = $exam_id;
                $where['user_id'] = $user_id;
                $data              = $where;
                $data['exam_data'] = $exam_data;
                $rt                = db_find_one('edu_result', $where);
                if ($rt)
                {
                    $_exam_data = is_array($exam_data) ? encode_json($exam_data) : $exam_data;
                    if ($rt['exam_data']==$_exam_data)
                    {
                        continue;
                    }
                    $history = decode_json($rt['exam_history']);
                    if (!is_array($history)) {
                        $history = array();
                    }
                    $history[$time] = $exam_data;
                    $data['exam_history'] = $history;
                    db_update('edu_result', $data, $where);
                    db_insert('edu_admin_log', array('created'=>time(),'handle'=>'修改','edu_result_id'=>$rt['id'], 'admin_user_id'=>EDU_UID, 'before'=>$rt['exam_data'], 'after'=>$exam_data));
                }
            }
        }
        msg(1, 'Success!');
    }



?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3"><?php echo $classes[$class_id]['class_name']; ?>(<?php echo $class_month; ?>)考試記錄</div>
    <div class="table list">
        <table class="table record">
            <thead>
                <tr>
                    <th>用戶資料</th>
                    <th>成績</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user_id => $user): ?>
                    <?php
                    $a = 0;
                    $s   = arrlist_search($record, array('user_id' => $user_id));
                    foreach ($s as $s1) {
                        if (!empty($s1['exam_data']) || $s1['exam_data']==0) {
                            $a = 1;
                        }
                    }
                    if ($a==0)
                    {
                        continue;
                    }
                    ?>


                <tr>
                    <td _user_id="<?php echo $user_id; ?>">
                        <svg t="1716299558807" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1702" width="64" height="64"><path d="M512 512c109.02806282 0 197.75390625-88.69262695 197.75390625-197.75390625S621.02806282 116.4921875 512 116.4921875 314.24609375 205.21803093 314.24609375 314.24609375s88.72584343 197.75390625 197.75390625 197.75390625zM149.45142937 799.96290398v16.24826431C149.45142937 895.14819336 275.25535774 907.5078125 431.44849968 907.5078125h161.10300064c150.06277085 0 281.99707031-12.35961914 281.99707031-91.29664421V799.96367646c0-150.98510742-115.15843391-255.00366211-269.30760383-255.00366212h-186.51437759c-154.1499424 0-269.27515984 103.98533821-269.27515984 255.00366211z" fill="#333333" p-id="1703"></path></svg>
                        <br>
                        <?php echo $user['last_name']; ?><br><?php echo $user['first_name']; ?><br>年齡:
                        <?php if(empty($user['billing_birthdate'])): ?>
                        <?php echo form_val('billing_age', $user);?>
                        <?php else: ?>
                        <?php echo calculate_age(form_val('billing_birthdate', $user));?>
                        <?php endif; ?>
                        <br>
                        <span history_score>成績記錄</span>
                    </td>
                    <td>
<table class="table table2" data-id="<?php echo $user_id; ?>">
                        <?php foreach ($exam_ids as $exam_id): ?>

<?php
    // $exam_id = $exam['id'];

    $exam    = arrlist_search($level, array('id'=>$exam_id));
    $exam = reset($exam);
    $exam = decode_json($exam['data']);
    $exam_result   = arrlist_search($record, array('user_id' => $user_id, 'exam_id' => $exam_id));
    if (is_array($exam_result))
    {
        $exam_result = reset($exam_result);
    }
    else
    {
        $exam_result = array();
    }

    if (!isset($exam_result['exam_data']) || $exam_result['exam_data']=='') {
        continue;
    }

    $score_id = form_val('id', $exam_result);
    $score = form_val('exam_data', $exam_result);
    $history = form_val('exam_history', $exam_result);
    $history = decode_json($history);
    if (empty($history)) {
        $history = array();
    }
    $history_count = count($history);
    $name  = 'data'.$exam_id;
    echo "<tr>";
    echo "<td data-score-id=\"{$score_id}\" popup><div class=\"exam_name\"><span class=\"xcount\">{$history_count}</span> {$exam['name']}</div></td>";
    // echo "<td style=\"width:100px\">".(form_val('exam_file', $exam_result) ? "<span data-score-id=\"{$score_id}\" player>圖片/影片</span>" : '')."</td>";
    echo "<td data-score-id=\"{$score_id}\">";

    switch ($exam['type'])
    {
        case 'text':
            echo form_text($name, $score);
            break;
        case 'number':
            echo "<input type=\"number\" name=\"{$name}\" value=\"".form_val($name, $score).'" class="form-text">';
            break;
        case 'time':
            echo "<div class=\"form-time\"><input type=\"text\" name=\"{$name}\" value=\"".form_val($name, $score).'"time class="form-text"></div>';
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
            if (!is_array($_sval))
            {
                $_sval = array();
            }

            foreach ($_ as $k1 => $v1)
            {
                $checked = in_array($k1, $_sval) ? 'checked="checked"' : '';
                echo "<label><input type=\"checkbox\" name=\"{$name}[]\" value=\"{$k1}\" {$checked}>{$v1}</label>";
            }
            echo '</form>';
            break;
        default:
            echo form_text('f'.$key, $score);
            break;
    }
    if ($is_admin)
    {
        echo "<i class=\"fa fa-close\"></i>";
        echo "</td>";
    }

    echo '</tr>';

?>

<?php endforeach;?>
</table>
                    </td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
        <?php if ($is_admin): ?>
            <button class="save form-button">保存</button>
        <?php endif ?>
    </div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
.section ul li{line-height: 35px;}
.section ul li span{float: right;}
table.table td:first-child{width: 150px;}
div.exam_name{font-weight: bold;text-align:left;color: #00ac4e;}
td form{text-align: left;}
.table2{border: hidden;}
.table2 td:first-child{width: 123px !important;}
.table2 td{position: relative;}
.table2 td form{min-height: 32px;}
.table2 td i.fa{position: absolute;right: 10px;z-index: 10;cursor: pointer;cursor: pointer;height: 12px;margin-top: auto;margin-bottom: auto;top: 0;bottom: 0;}
.table2 td i.fa:hover{color: red;}

/*.table2 td i.fa-history{position: absolute;right: 30px;z-index: 10;cursor: pointer;cursor: pointer;height: 12px;margin-top: auto;margin-bottom: auto;top: 0;bottom: 0;}*/
.xcount{display: inline-block;background: red;color: #fff;border-radius: 50%;aspect-ratio:1;font-weight: normal;font-size: 12px;width: 20px;line-height: 20px;text-align: center;}
[popup] .exam_name{cursor: pointer;}
[popup]:hover .exam_name{color: #0d5356;}
.form-button{display: block;}
form label{display: inline-block;margin-right:5px;}
span[history_score]{cursor: pointer;font-weight: bold;color:#1f7b6b;}
span[player]{cursor: pointer;color: blue;}
@media all and (max-width: 650px)
{
    .h3{font-size: 15px}
    table.table td:first-child{width: 50px;font-size: 12px;}
    table.table td:first-child svg{width: 45px !important;}
}



</style>
<script>
$(function(){
$("button.save").click(function(event) {
    result = {};
    $("table.table2").each(function(index, el) {
        var student_id = $(this).attr('data-id');
        var data = $(this).serializeObject();
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

    $("td[data-score-id] i.fa-close").click(function(event) {
        var id = $(this).parent('td').attr('data-score-id');
        layer.confirm('確認刪除?', {
            btn: ['確認', '取消'] //按钮
        }, function() {
            if (id) {
                $.ajax({
                    url: '#',
                    type: 'POST',
                    data: {
                        handle: 'delete_score',
                        id: id
                    },
                    success: function(msg) {
                        $msg.success(msg.msg, function() {
                        });
                        window.location.reload();
                    }
                });
            }
        }, function() {
        });
    });


    $("td[popup] div.exam_name").click(function(event) {
        var id = $(this).parent('td').attr('data-score-id');
        layer.open({type:2, area: ['350px', '400px'], content: "history_score.php?id="+id, title: '成績記錄' });
    });

    $("span[player]").click(function(event) {
        var id = $(this).attr('data-score-id');
        layer.open({type:2, area: ['350px', '400px'], content: "player.php?id="+id, title: '查看文件' });
    });

    $("span[history_score]").click(function(event){
        var user_id = $(this).parent('td').attr('_user_id');
        layer.open({type:2, area: ['350px', '400px'], content: "history_score_user.php?user_id="+user_id, title: '歷史成績' });
    });

<?php if (!$is_admin): ?>
$(":input").each(function(index, el) {
    $(this).attr('disabled','disabled');
});
<?php endif ?>


});
</script>

