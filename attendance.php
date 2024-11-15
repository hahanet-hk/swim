<?php
    define('DEBUG', 1);
    include __DIR__.'/core/init.php';

    // filter classes
    $district_id  = get('district_id', 0);
    $district_id2 = empty($district_id) ? 0 : get('district_id2', 0);
    $lv3          = get('lv3');
    $class_id     = get('class_id');
    $class_year = get('class_year',date('Y'));

    $classes_id = array();
    $where = array();
    if (!$is_admin)
    {
        $where = array('%teacher'=>'"'.$user['ID'].'"');
    }
    $_class_user = db_find('edu_class_user', $where, array('id' => -1));
    foreach ($_class_user as $key => $value)
    {
        // 如果不是管理员, 班级的时间又不在当前月范围, 则不加入点名
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

    $district = array();
    $terms    = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.parent' => 0, 'term_taxonomy.taxonomy' => 'product_cat'));
    foreach ($terms as $key => $value)
    {
        if (strpos($value['name'], '游泳班', 0) !== false)
        {
            $district[$value['term_id']] = $value['name'];
        }
    }
    if ($district_id)
    {
        $district2 = array();
        $terms     = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.parent' => $district_id, 'term_taxonomy.taxonomy' => 'product_cat'));
        foreach ($terms as $key => $value)
        {
            if (strpos($value['name'], '游泳班', 0) !== false)
            {
                $district2[$value['term_id']] = $value['name'];
            }
        }
    }
    $where = array();
    if (!empty($classes_id))
    {
        $where['class_id'] = $classes_id;
    }

    if ($district_id2)
    {
        $where['district_id'] = $district_id2;
    }
    elseif ($district_id)
    {
        $dist_id = array();
        $terms   = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.parent' => $district_id, 'term_taxonomy.taxonomy' => 'product_cat'));
        foreach ($terms as $key => $value)
        {
            if (strpos($value['name'], '游泳班', 0) !== false)
            {
                $dist_id[] = $value['term_id'];
            }
        }
        $where['district_id'] = $dist_id;
    }
    $classes_lv3 = db_find('edu_class', $where);
    if ($lv3)
    {
        $where['lv3'] = $lv3;
    }

    // 獲取所有班級並按照星期和上下午排序
    $classes = db_find('edu_class', $where);
    $group   = get('group');
    if ($group)
    {
        foreach ($classes as $key => $value)
        {
            if (strpos($value['class_name'], $group, 0) !== 0)
            {
                unset($classes[$key]);
                continue;
            }
        }
    }
    $classes = classes_sort($classes);
    $classes_first = reset($classes);
    if (empty($classes_first))
    {
        $classes_first = array();
        $classes_first['class_id'] = 0;
    }




    // 獲取當前班級
    $classes  = arrlist_change_key($classes, 'class_id');


    if ($class_id && empty($classes[$class_id]))
    {
        exit('沒有該班級權限或當前班級無學生!');
    }


    $class    = empty($class_id) ? $classes_first : $classes[$class_id];
    $class_id = $class['class_id'];


    $months = db_find('edu_class_user', array('class_id' => $class_id));



    if (is_ajax())
    {
        $handle = post('handle');

        if (post('month'))
        {
            $month = post('month');
        }
        else
        {
            $month = post('data.0.month');
        }

        if ($is_admin && $handle == 'delete')
        {
            $date = post('date');
            $date = strtotime($date);
            $date = date('Y-m-d', $date);
            db_delete('edu_attendance', array('class_id' => $class_id, 'date' => $date));
            msg(1, 'Success');
        }

        $where = array();
        // 更新出席記錄
        if ($is_admin && $handle == 'attendance')
        {
            $date                 = post('date');
            $user_id              = post('user_id');
            $attendance           = post('attendance');
            $where                = array();
            $where['user_id']     = $user_id;
            $where['class_id']    = $class_id;
            $where['date']        = $date;
            $where['month']       = $month;
            $where['class_year']  = date('Y');
            $update               = array();
            $update['attendance'] = $attendance;

            if (db_find_one('edu_attendance', $where))
            {
                db_update('edu_attendance', $update, $where);
            }
            else
            {
                $where['attendance'] = $attendance;
                $r = db_insert('edu_attendance', $where);
            }
            msg(1, 'Success');
        }

        // 獲取出席列表
        $post       = post('data');
        $page       = post('page');
        $post       = $post[0];
        $post_date  = $post['date'];
        $class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $month));
        if (empty($class_user))
        {
            $class_user            = array();
            $class_user['student'] = '';
            $class_user['teacher'] = '';
            $class_user['student_transfer'] = '';
        }
        $student_ids    = decode_json($class_user['student']);
        $teacher_ids    = decode_json($class_user['teacher']);
        $student_transfer_ids = decode_json($class_user['student_transfer']);
        $class_user_ids = array_merge($student_ids, $teacher_ids, $student_transfer_ids);
        $pagesize       = 20;
        if ($handle == 'get_page')
        {
            $page_all = ceil(count($class_user_ids) / $pagesize);
            msg(1, $page_all);
        }
        if ($handle == 'get_list')
        {
            // present  late  absent
            $class_users = edu_get_user($class_user_ids);
            foreach ($class_users as $key => $value)
            {
                $class_users[$key]['index'] = 2;
                $class_users[$key]['style'] = 'student';
                if (in_array($value['ID'], $teacher_ids))
                {
                    $class_users[$key]['index'] = 1;
                    $class_users[$key]['style'] = 'teacher';
                }

                if (in_array($value['ID'], $student_transfer_ids))
                {
                    $class_users[$key]['index'] = 3;
                    $class_users[$key]['style'] = 'student_transfer';
                }
            }
            $class_users = arrlist_search($class_users, array(), array('index' => 1), $page, $pagesize);
            // 分月展示每個用戶的出席記錄
            foreach ($class_users as $key => &$user)
            {

                $attends = get_user_class_month_attend($user['ID'], $class_id, $month, $class_year);
                $user['attendance'] = attend_array_text($attends);
                $user['postday'] = empty($attends[$post_date]) ? '' : $attends[$post_date];
            }
            $data         = array();
            $data['list'] = $class_users;
            msg(1, $data);
        }
    }
?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3">點名管理</div>
<?php if ($is_admin): ?>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($district_id)): ?>class="on"<?php endif;?>><a href="?district_id=<?php echo 0; ?>&district_id2=<?php echo $district_id2; ?>&lv3=<?php echo $lv3; ?>">所有</a></li>

        <?php foreach ($district as $_district_id => $_district_name): ?>
            <li class="<?php echo $_district_id == $district_id ? 'on' : ''; ?>"><a href="?district_id=<?php echo $_district_id; ?>"><?php echo $_district_name; ?></a></li>
        <?php endforeach;?>
        </ul>
    </div>
    <?php if (!empty($district2)): ?>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($district_id2)): ?>class="on"<?php endif;?>><a href="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo 0; ?>&lv3=<?php echo $lv3; ?>">所有</a></li>

        <?php foreach ($district2 as $_district_id => $_district_name): ?>
            <li class="<?php echo $_district_id == $district_id2 ? 'on' : ''; ?>"><a href="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $_district_id; ?>"><?php echo $_district_name; ?></a></li>
        <?php endforeach;?>
        </ul>
    </div>
    <?php endif;?>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($lv3)): ?>class="on"<?php endif;?>><a href="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $district_id2; ?>&lv3=<?php echo 0; ?>">所有</a></li>

        <?php foreach ($classes_lv3 as $class): ?>
        <?php
            if (!isset($_lv3_))
            {
                $_lv3_ = array();
            }
            if (isset($_lv3_[$class['lv3']]))
            {
                continue;
            }
            else
            {
                $_lv3_[$class['lv3']] = 1;
            }
        ?>
            <li class="<?php echo $class['lv3'] == $lv3 ? 'on' : ''; ?>"><a href="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $district_id2; ?>&lv3=<?php echo $class['lv3']; ?>"><?php echo $class['lv3']; ?></a></li>
        <?php endforeach;?>
        </ul>
    </div>
<?php endif ?>


    <div class="filter form condition">

        <?php if (!$is_admin): ?>
        <div class="x1">
            <div class="wrap">
                <span class="tit">班別:</span>
                <select name="group" class="form-select">
                    <option value="">請選擇</option>
                    <?php $option_arr = array('幼兒游泳班', '兒童游泳班', '成人游泳班', '暑期幼兒游泳班', '暑期兒童游泳班', '成人四式改良班', '女子成人游泳班');?>
                    <?php foreach ($option_arr as $key => $option): ?>
                        <option value="<?php echo $option ?>" <?php if ($option==get('group')): ?>selected="selected"<?php endif ?>><?php echo $option ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <?php endif ?>

        <div class="x1">
            <div class="wrap">
                <span class="tit">班級:</span>
                <select name="class_id" class="form-select">
                    <?php foreach ($classes as $key => $class): ?>
                        <option value="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $district_id2; ?>&lv3=<?php echo $lv3; ?>&class_id=<?php echo $class['class_id']; ?>"<?php if ($class['class_id'] == get('class_id')): ?>selected="selected"<?php endif;?>><?php echo $class['class_name']; ?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="x1" <?php if (!$is_admin): ?>hidden<?php endif ?>>
            <div class="wrap">
                <span class="tit">年:</span>
                <select name="class_year" class="form-select">
                    <?php for ($i=2024; $i <= date('Y')+1; $i++) { ?>
                        <option value="<?php echo $i; ?>"<?php if ($i == date('Y')): ?>selected="selected"<?php endif;?>><?php echo $i; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="x1" <?php if (!$is_admin): ?>hidden<?php endif ?>>
            <div class="wrap">
                <span class="tit">月:</span>
                <select name="month" class="form-select">
                    <?php foreach ($months as $key => $month): ?>
                        <option value="<?php echo $month['month']; ?>"<?php if (is_current_month($month['month'])): ?>selected="selected"<?php endif;?>><?php echo $month['month']; ?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="x1">
            <div class="wrap">
                <span class="tit">日期:</span>
                <input type="text" name="date" id="_date" value="<?php echo date('Y-m-d'); ?>" placeholder="Select Date" class="form-text" style="display: none;">
                <div id="_datepicker"></div>
            </div>
        </div>
    </div>
    <div class="section" style="padding:5px;">
        提示: 灰色為老師, 黃色為插班/補堂學生
    </div>
    <div class="list">
        <div class="table">
            <table class="table">
                <thead>
                    <tr>
                        <th>用戶</th>
                        <th>性別</th>
                        <th>出席記錄</th>
                        <th>出席</th>
                        <th>請假</th>
                        <th>取消</th>
                        <?php if ($is_admin): ?>
                        <th>清除</th>
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody>
                    <script type="text/html">
                    {{each msg.list v i}}
                    <tr {{v.style}}>
                        <td>
                            <svg t="1716299558807" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1702" width="64" height="64"><path d="M512 512c109.02806282 0 197.75390625-88.69262695 197.75390625-197.75390625S621.02806282 116.4921875 512 116.4921875 314.24609375 205.21803093 314.24609375 314.24609375s88.72584343 197.75390625 197.75390625 197.75390625zM149.45142937 799.96290398v16.24826431C149.45142937 895.14819336 275.25535774 907.5078125 431.44849968 907.5078125h161.10300064c150.06277085 0 281.99707031-12.35961914 281.99707031-91.29664421V799.96367646c0-150.98510742-115.15843391-255.00366211-269.30760383-255.00366212h-186.51437759c-154.1499424 0-269.27515984 103.98533821-269.27515984 255.00366211z" fill="#333333" p-id="1703"></path></svg>
                            <div data-user-score="{{v.ID}}">{{v.last_name}}<br>{{v.first_name}}</div>
                            <a tel href="tel:{{v.billing_phone}}" target="_blank">{{v.billing_phone}}</a>
                        </td>
                        <td>{{v.billing_gender}}</td>
                        <td tl>
                            出席: {{@v.attendance.present}}<br>
                            請假: {{@v.attendance.late}}<br>
                            取消: {{@v.attendance.absent}}<br>
                            <?php if ($is_admin): ?>
                            清除: {{@v.attendance.clear}}
                            <?php endif ?>

                        </td>
                        <td tc user-id="{{v.ID}}"><b val="present" class="attendBtn present {{if v.postday=='present'}}on{{/if}}"></b></td>
                        <td tc user-id="{{v.ID}}"><b val="late" class="attendBtn late {{if v.postday=='late'}}on{{/if}}"></b></td>
                        <td tc user-id="{{v.ID}}"><b val="absent" class="attendBtn absent {{if v.postday=='absent'}}on{{/if}}"></b></td>
                        <?php if ($is_admin): ?>
                            <td tc user-id="{{v.ID}}"><b val="clear" class="attendBtn clear {{if v.postday=='clear'}}on{{/if}}"></b></td>
                        <?php endif ?>
                    </tr>
                    {{/each}}
                    </script>
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination"></div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
b.attendBtn{cursor: pointer;}
b.attendBtn:before{font-family: 'FontAwesome';content: '\f096';font-size: 26px;font-weight: normal;}
b.attendBtn.on:before{content: '\f0c8';}
.present{color: green;}
.late{color: #ee9c0f;}
.absent{color: red;}
.layui-laydate-content table{border-radius: 0 !important;}
.form td{border: 1px solid rgba(13,83,86,0.1);}
.form th{background: rgba(13,83,86,0.2);border: 1px solid rgba(13,83,86,0.1);}
.filter .form-button{text-transform: uppercase;}
.list .table thead{background: #00ac4e;color: #fff;height: 38px;line-height: 38px;user-select: none;}
.table{width: 100%;overflow-y: hidden;}
table.table{width: 100%;border-spacing:0;border-collapse:collapse;overflow: hidden;}
table.table td{border: solid 1px #ddd;padding: 3px;}
table.table img{width: 45px;}
table.table tr[teacher] td{background: #ccc;}
table.table tr[student_transfer] td{background: yellow;}
table.table td:first-child{width: 72px;}
table.table td:nth-child(2){width:50px;}

.district ul li.on{font-weight: bold;}
ul li{line-height:32px;float: left;margin-right: 10px;line-height: 1.2;}
ul li h3{padding: 10px;}
ul li h3 span{float: right;color: #fff;}
ul li h3 span i{margin-left: 2px;}
li.class{transition: all .3s;border-radius: 4px;background: #258872;overflow: hidden;margin-top: 15px;}
li.class h3{color: #fff;}
li.class ul{background: #d1dddd;}
li.class ul li{padding: 0 10px;}
a[tel]{color:#00f;}

.layui-layer-iframe{max-width: 92% !important;}

@media all and (max-width: 650px)
{
    table.table td:nth-child(1){font-size: 12px;}
}

</style>
<script>
$(function(){
    laydate.render({elem: '#_datepicker', position: 'static', lang: 'en', value: $("#_date").val(), showBottom: false, mondayStart: 1, change: function(value){
        $("#_date").val(value);
        $(".table table tbody").load_list();
    }});

    $("select[name=group]").on('change', function(event) {
        window.location.href='?group='+$(this).val();
    });

    $("select[name=class_id]").on('change', function(event) {
        window.location.href = $(this).val();
    });

    <?php if ($class_id): ?>
        $(".table table tbody").load_list();
    <?php endif ?>

    $("select[name=month]").on('change', function(event) {
        $(".table table tbody").load_list();
    });

    <?php if($is_admin): ?>
    $("body").on('click', '.attendBtn', function(event) {
        $this = $(this);
        user_id = $(this).parent().attr('user-id');
        date = $("#_date").val();
        month = $("select[name=month]").val();
        attendance = $this.attr('val');
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'attendance', user_id: user_id, date: date, month: month, attendance: attendance},
            success:function(msg){
                $this.parents('tr').find('b').removeClass('on');
                $this.addClass('on');
                $msg.success(msg.msg);
            }
        });
    });
    <?php endif; ?>

    $("body").on('click', '[data-user-score]', function(event) {
        event.preventDefault();
        var user_id = $(this).attr("data-user-score");
        layer.open({type:2, area: ['350px', '400px'], content: "history_score_user.php?user_id="+user_id, title: '歷史成績' });
    });




    <?php if ($is_admin): ?>
            $('td[lay-ymd]').contextMenu({
                target: function(ele) {
                    $ymd = ele.attr('lay-ymd');
                    $class_id = $("select[name=class_id]").val();
                },
                menu: [{
                        text: "刪除當日記錄",
                        callback: function() {
                            $.ajax({
                                url: '#',
                                type: 'POST',
                                data: {handle: 'delete', date:$ymd, class_id: $class_id},
                                success:function(msg){
                                    $msg.success(msg.msg);
                                    window.location.reload();
                                }
                            });
                        }
                    },
                    {
                        text: "取消刪除",
                        callback: function() {
                        }
                    }
                ]
            });
    <?php endif ?>


});
</script>
