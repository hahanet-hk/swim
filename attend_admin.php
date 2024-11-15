<?php
include __DIR__.'/core/init.php';
// filter classes
$district_id = get('district_id');
$district_id2 = get('district_id2');
if (empty($district_id)) {
    $district_id2 = 0;
}
$lv3 = get('lv3');
$district = array();
$terms    = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.parent' => 0, 'term_taxonomy.taxonomy' => 'product_cat'));
foreach ($terms as $key => $value)
{
    if (strpos($value['name'], '游泳班', 0) !== false)
    {
        $district[$value['term_id']] = $value['name'];
    }
}
if ($district_id) {
    $district2 = array();
    $terms    = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.parent' => $district_id, 'term_taxonomy.taxonomy' => 'product_cat'));
    foreach ($terms as $key => $value)
    {
        if (strpos($value['name'], '游泳班', 0) !== false)
        {
            $district2[$value['term_id']] = $value['name'];
        }
    }
}
$where = array();
if ($district_id2)
{
    $where['district_id'] = $district_id2;
}
else if($district_id)
{
    $dist_id = array();
    $terms    = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.parent' => $district_id, 'term_taxonomy.taxonomy' => 'product_cat'));
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
if ($lv3) {
    $where['lv3'] = $lv3;
}

$classes = db_find('edu_class', $where);
// filter classes end

    switch (date('w'))
    {
        case 0:
            $week = '日';
            break;
        case 6:
            $week = '六';
            break;
        case 5:
            $week = '五';
            break;
        case 4:
            $week = '四';
            break;
        case 3:
            $week = '三';
            break;
        case 2:
            $week = '二';
            break;
        case 1:
            $week = '一';
            break;
    }

    $group = get('group');

    foreach ($classes as $key => &$value)
    {
        if ($group && strpos($value['class_name'], $group, 0) !== 0)
        {
            unset($classes[$key]);
            continue;
        }

        $_class_name = $value['class_name'];
        $_pos = strpos($_class_name, '星期');
        $_class_name = substr($_class_name, $_pos);

        if (strpos($_class_name, $week, 0) !== false)
        {
            $value['sort1'] = 0;
        }
        else
        {
            $value['sort1'] = 1;
        }
        $am = date('a');
        if (strpos($_class_name, $am, 0) !== false)
        {
            $value['sort2'] = 0;
        }
        else
        {
            $value['sort2'] = 1;
        }
    }

    $classes = arrlist_search($classes, array(), array('sort1'=>1, 'sort2'=>1, 'class_name'=>1));




    if (is_ajax())
    {
        $handle = post('handle');
        if ($handle == 'delete')
        {
            $date = post('date');
            $date = strtotime($date);
            $date = date('Y-m-d', $date);
            db_delete('edu_attendance', array('class_id' => post('class_id'), 'date' => $date));
            msg(1, 'Success');
        }
        if (post('class_id'))
        {
            $class_id = post('class_id');
        }
        else
        {
            $class_id = post('data.0.class_id');
        }
        $where               = array();
        $class               = arrlist_search($classes, array('class_id' => $class_id));
        $class               = reset($class);
        $class['date_month'] = decode_json($class['date_month']);
        foreach ($class['date_month'] as $month)
        {
            if (is_current_month($month))
            {
                break;
            }
        }
        if ($handle == 'attendance')
        {
            $date                 = post('date');
            $user_id              = post('user_id');
            $attendance           = post('attendance');
            $where                = array();
            $where['user_id']     = $user_id;
            $where['class_id']    = $class_id;
            $where['date']        = $date;
            $where['month']       = $month;
            $update               = array();
            $update['attendance'] = $attendance;
            if (db_find_one('edu_attendance', $where))
            {
                db_update('edu_attendance', $update, $where);
            }
            else
            {
                $where['attendance'] = $attendance;
                db_insert('edu_attendance', $where);
            }
            msg(1, 'Success');
        }
        $post       = post('data');
        $page       = post('page');
        $post       = $post[0];
        $class_id   = $post['class_id'];
        $post_date  = $post['date'];
        $class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $month));
        if (empty($class_user))
        {
            $class_user            = array();
            $class_user['student'] = '';
            $class_user['teacher'] = '';
        }
        $student_ids = decode_json($class_user['student']);
        $teacher_ids = decode_json($class_user['teacher']);
        if (empty($student_ids))
        {
            $student_ids = array();
        }
        if (empty($teacher_ids))
        {
            $teacher_ids = array();
        }
        $class_user_ids = array_merge($student_ids, $teacher_ids);
        $pagesize       = 20;
        if ($handle == 'get_page')
        {
            $page_all = ceil(count($class_user_ids) / $pagesize);
            msg(1, $page_all);
        }
        if ($handle == 'get_list')
        {

            if (empty($class_user['days']))
            {
                $class['class_date'] = get_days($class['date_time'], $month);
            }else{
                $class['class_date'] = $class_user['days'];
            }

            $class['class_date'] = explode(',', $class['class_date']);
            $class['class_date'] = encode_json($class['class_date']);


            $class_date = decode_json($class['class_date']);
            if (empty($class_date)) {
                $class_date = array();
            }
            // present  late  absent
            $class_users = edu_get_user($class_user_ids);
            foreach ($class_users as $key => $value)
            {
                if (in_array($value['ID'], $teacher_ids))
                {
                    $class_users[$key]['is_teacher'] = 1;
                }
                else
                {
                    $class_users[$key]['is_teacher'] = 0;
                }
            }
            $class_users = arrlist_search($class_users, array(), array('is_teacher' => -1), $page, $pagesize);
            $attendance  = db_find('edu_attendance', array('class_id' => $class_id,'month'=>$month));
            $date_new    = array();
            foreach ($attendance as $key => $value)
            {
                $date_new[] = $value['date'];
            }
            $date_new   = array_unique($date_new);
            $class_date = array_merge($class_date, $date_new);
            $class_date = days_sort($class_date);

            foreach ($class_users as $key => &$user)
            {
                $user_days = db_find_one('edu_class_user_days', array('class_id'=>$class_id,'month'=>$month, 'user_id'=>$user['ID']));
                if (!empty($user_days))
                {
                    $user_days = $user_days['days'];
                    $user_days = explode(',', $user_days);
                    $user_days = array_merge($user_days, $date_new);
                    $user_days = array_unique($user_days);
                }else{
                    $user_days = $class_date;
                }

                $user_days = days_sort($user_days);

                foreach ($user_days as $ymd)
                {
                    $ymd_attendance = 'present';
                    if ($user['ID'])
                    {
                        $_ymd_attendance = arrlist_search($attendance, array('class_id' => $class_id, 'date' => $ymd, 'user_id' => $user['ID']));
                        if (!empty($_ymd_attendance))
                        {
                            $ymd_attendance = reset($_ymd_attendance);
                            $ymd_attendance = $ymd_attendance['attendance'];
                        }
                    }
                    $user['attendance'][$ymd] = $ymd_attendance;
                }
            }

            foreach ($class_users as $key => &$user)
            {
                $_attend = array();
                foreach ($user['attendance'] as $ymd => $attend)
                {
                    $timestrape = strtotime($ymd);
                    $m          = date('n', $timestrape);
                    $d          = date('j', $timestrape);
                    if (isset($_attend[$attend][$m]))
                    {
                        $_attend[$attend][$m] .= $d.', ';
                    }
                    else
                    {
                        $_attend[$attend][$m] = $d.', ';
                    }
                }
                $user['attendance']['present'] = '';
                $user['attendance']['late']    = '';
                $user['attendance']['absent']  = '';
                $user['attendance']['clear']  = '';
                foreach ($_attend as $att => $ymd)
                {
                    $a = '';
                    foreach ($ymd as $m => $d)
                    {
                        $a .= $m.'月: '.substr($d, 0, -2).'&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    $user['attendance'][$att] = $a;
                }
                $postday = arrlist_search($attendance, array('class_id' => $class_id, 'date' => $post_date, 'user_id' => $user['ID']));
                if ($postday)
                {
                    $postday = reset($postday);
                }
                else
                {
                    $postday = array();
                }
                if (!empty($post_date) && in_array($post_date, $class_date))
                {
                    $present = 'present';
                }
                else
                {
                    $present = '';
                }
                $user['postday'] = empty($postday['attendance']) ? $present : $postday['attendance'];
            }
            $data         = array();
            $data['list'] = $class_users;
            msg(1, $data);
        }
    }
?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3">點名管理</div>

    <div class="section district pd">
        <ul>
            <li <?php if (empty($district_id)): ?>class="on"<?php endif ?>><a href="?district_id=<?php echo 0; ?>&district_id2=<?php echo $district_id2 ?>&lv3=<?php echo $lv3 ?>">所有</a></li>
        <?php foreach ($district as $_district_id => $_district_name): ?>
            <li class="<?php echo $_district_id==$district_id ? 'on' : '';?>"><a href="?district_id=<?php echo $_district_id ?>"><?php echo $_district_name ?></a></li>
        <?php endforeach ?>
        </ul>
    </div>
    <?php if (!empty($district2)): ?>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($district_id2)): ?>class="on"<?php endif ?>><a href="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo 0; ?>&lv3=<?php echo $lv3 ?>">所有</a></li>
        <?php foreach ($district2 as $_district_id => $_district_name): ?>
            <li class="<?php echo $_district_id==$district_id2 ? 'on' : '';?>"><a href="?district_id=<?php echo $district_id ?>&district_id2=<?php echo $_district_id ?>"><?php echo $_district_name ?></a></li>
        <?php endforeach ?>
        </ul>
    </div>
    <?php endif ?>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($lv3)): ?>class="on"<?php endif ?>><a href="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $district_id2 ?>&lv3=<?php echo 0; ?>">所有</a></li>
        <?php foreach ($classes_lv3 as $class): ?>
            <?php
            if (!isset($_lv3_)) {
                $_lv3_ = array();
            }
            if (isset($_lv3_[$class['lv3']])) {
                continue;
            }else{
                $_lv3_[$class['lv3']] = 1;
            }
            ?>
            <li class="<?php echo $class['lv3']==$lv3 ? 'on' : '';?>"><a href="?district_id=<?php echo $district_id ?>&district_id2=<?php echo $district_id2 ?>&lv3=<?php echo $class['lv3'] ?>"><?php echo $class['lv3'] ?></a></li>
        <?php endforeach ?>
        </ul>
    </div>


    <div class="filter form condition">
        <div class="x1">

        <div class="wrap" style="display: none;">
            <span class="tit">班別:</span>
            <select name="group" class="form-select">
                <option value="">請選擇</option>
                <?php $option_arr = array('幼兒游泳班', '兒童游泳班', '成人游泳班', '暑期幼兒游泳班', '暑期兒童游泳班', '成人四式改良班', '女子成人游泳班');?>
                <?php foreach ($option_arr as $key => $option): ?>
                    <option value="<?php echo $option ?>" <?php if ($option==get('group')): ?>selected="selected"<?php endif ?>><?php echo $option ?></option>
                <?php endforeach ?>
            </select>
        </div>


            <div class="wrap">
                <span class="tit">Select Class:</span>
                <select name="class_id" class="form-select">
                    <?php foreach ($classes as $key => $class): ?>
                        <option value="<?php echo $class['class_id']; ?>"<?php if ($class['class_id'] == get('class_id')): ?>selected="selected"<?php endif;?>><?php echo $class['class_name']; ?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="x1">
            <div class="wrap">
                <span class="tit">Date</span>
                <input type="text" name="date" id="_date" value="<?php echo date('Y-m-d'); ?>" placeholder="Select Date" class="form-text">
                <div id="_datepicker"></div>
            </div>
        </div>
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
                        <th>清除</th>
                    </tr>
                </thead>
                <tbody>
                    <script type="text/html">
                    {{each msg.list v i}}
                    <tr {{v.is_teacher ? 'class=teacher' : ''}}>
                        <td><svg t="1716299558807" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1702" width="64" height="64"><path d="M512 512c109.02806282 0 197.75390625-88.69262695 197.75390625-197.75390625S621.02806282 116.4921875 512 116.4921875 314.24609375 205.21803093 314.24609375 314.24609375s88.72584343 197.75390625 197.75390625 197.75390625zM149.45142937 799.96290398v16.24826431C149.45142937 895.14819336 275.25535774 907.5078125 431.44849968 907.5078125h161.10300064c150.06277085 0 281.99707031-12.35961914 281.99707031-91.29664421V799.96367646c0-150.98510742-115.15843391-255.00366211-269.30760383-255.00366212h-186.51437759c-154.1499424 0-269.27515984 103.98533821-269.27515984 255.00366211z" fill="#333333" p-id="1703"></path></svg>
                            <br>{{v.last_name}} <br>{{v.first_name}}
                        </td>
                        <td>{{v.billing_gender}}</td>
                        <td>
                            出席: {{@v.attendance.present}}<br>
                            請假: {{@v.attendance.late}}<br>
                            取消: {{@v.attendance.absent}}<br>
                            清除: {{@v.attendance.clear}}<br>
                        </td>
                        <td tc user-id="{{v.ID}}"><b val="present" class="attendBtn present {{if v.postday=='present'}}on{{/if}}"></b></td>
                        <td tc user-id="{{v.ID}}"><b val="late" class="attendBtn late {{if v.postday=='late'}}on{{/if}}"></b></td>
                        <td tc user-id="{{v.ID}}"><b val="absent" class="attendBtn absent {{if v.postday=='absent'}}on{{/if}}"></b></td>
                        <td tc user-id="{{v.ID}}"><b val="clear" class="attendBtn clear {{if v.postday=='clear'}}on{{/if}}"></b></td>
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
table.table td{text-align: center;border: solid 1px #ddd;padding: 3px;}
table.table img{width: 45px;}
table.table td:nth-child(3){text-align: left !important;}
table.table td:nth-child(4){text-align: center;}
table.table td:nth-child(5){text-align: left;}
table.table td:nth-child(6){text-align: center !important;}
table.table tr.teacher td{background: #ccc;}

.district ul li.on{font-weight: bold;}
ul li{border-bottom: dashed 1px #eee;line-height:32px;float: left;margin-right: 10px;line-height: 1.2;}
ul li h3{padding: 10px;}
ul li h3 span{float: right;color: #fff;}
ul li h3 span i{margin-left: 2px;}
li.class{transition: all .3s;border-radius: 4px;background: #258872;overflow: hidden;margin-top: 15px;}
li.class h3{color: #fff;}
li.class ul{background: #d1dddd;}
li.class ul li{padding: 0 10px;}


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
        $(".table table tbody").load_list();
    });
    $(".table table tbody").load_list();
    $("body").on('click', '.attendBtn', function(event) {
        $this = $(this);
        user_id = $(this).parent().attr('user-id');
        date = $("#_date").val();
        class_id = $("select[name=class_id]").val();
        attendance = $this.attr('val');
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'attendance', user_id: user_id, date: date, class_id: class_id, attendance: attendance},
            success:function(msg){
                $this.parents('tr').find('b').removeClass('on');
                $this.addClass('on');
                $msg.success(msg.msg);
            }
        });
    });
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
});
</script>