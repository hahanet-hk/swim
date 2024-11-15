<?php
    define("DEBUG", true);
    include 'core/init.php';

    $class_id = get('class');
    if (empty($class_id))
    {
        http_redirect('./');
    }
    $level      = db_find('edu_level');
    $class_year = get('year');
    if (empty($class_year))
    {
        $class_year = date('Y');
    }

    $class = db_find_one('edu_class', array('class_id' => $class_id));

    if (empty($class))
    {
        exit('班級不存在! 點此返回 <a href="classes.php">班級列表</a>');
    }

    $class['class_exam'] = decode_json($class['class_exam']);
    $class['date_month'] = array();

    $class_month = get('month');

    $exam   = array();
    $class_users = db_find('edu_class_user', array('class_id' => $class_id), array('sort' => 1), array(8));

    foreach ($class_users as $key => $value)
    {
        if (empty($value['month']))
        {
            unset($class_users[$key]);
            continue;
        }

        if (1 || !empty(decode_json($value['student'])) || !empty(decode_json($value['student_transfer'])))
        {
            $class['date_month'][] = $value['month'];
            if (empty($class_month) && is_current_month($value['month']))
            {
                $class_month = $value['month'];
            }
        }
    }

    if (empty($class_month))
    {
        $class_month = end($class['date_month']);
    }

    if (empty($class['date_month']))
    {
        exit('班級月份未找到! <a href="class_months.php?router=show&class_id='.$class_id.'">新增月份</a>');
    }

    $class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $class_month, 'class_year' => $class_year));

    if (empty($class_user))
    {
        exit('未找到對應月份的班級!');
    }

    $class_exam = decode_json($class_user['class_exam']);

    if (empty($class_exam) || empty($class_exam[0]))
    {
        $class_exam = array();
    }

    // 新增老師觸發更新班級考試項目事件
    $teacher = post('teacher');

    if ($teacher && empty($class_exam))
    {
        $_class_exam = get_class_exam($class['class_name']);
        db_update('edu_class_user', array('class_exam' => encode_json($_class_exam)), array('class_id' => $class_id, 'month' => $class_month, 'class_year' => $class_year));
    }
    // 更新考試事件處理完成

    $exam = array();
    if (!empty($class_exam))
    {
        foreach ($class_exam as $key => $value)
        {
            if ($value)
            {
                $exam[] = arrlist_search_one($level, array('id' => $value));
            }
        }
    }

    if (empty($class['class_exam']))
    {
        $class['class_exam'] = array();
    }
    if (empty($class['date_month']))
    {
        $class['date_month'] = array();
    }
    $handle = post('handle');

    // $class_month; $class_id; 獲取上月班級
    if ($handle == 'get_prev_data')
    {
        $_prev_month = get_string_prev_month($class_user['month']);
        $prev        = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $_prev_month), array('id' => -1));
        if ($prev)
        {
            $month_next = get_string_next_month($prev['month']);
            if ($month_next == $class_month)
            {
                $_data                     = array();
                $_data['student']          = array_merge(decode_json($prev['student']), decode_json($class_user['student']));
                $_data['student_transfer'] = array_merge(decode_json($prev['student_transfer']), decode_json($class_user['student_transfer']));
                $_data['teacher']          = $prev['teacher'];
                $_data['class_exam']       = $prev['class_exam'];
                $_data['student']          = array_unique($_data['student']);
                $_data['student_transfer'] = array_unique($_data['student_transfer']);
                db_update('edu_class_user', $_data, array('id' => $class_user['id']));
                msg(1, '獲取上月資料成功!', '#');
            }
            else
            {
                msg(0, '上月班級不存在!');
            }
        }
        else
        {
            msg(0, '上月班級不存在!');
        }

        msg(0, 'Failed');
    }

    if ($handle == 'add_exam')
    {
        $id = post('id');
        if (is_array($class_exam))
        {
            array_push($class_exam, $id);
        }
        else
        {
            $class_exam = array($id);
        }
        $class_exam = encode_json($class_exam);
        db_update('edu_class_user', array('class_exam' => $class_exam), array('class_id' => $class_id, 'month' => $class_month, 'class_year' => $class_year));
        msg(1, 'Success');
    }

    if ($handle == 'update_exam')
    {
        $_examID = post('id');
        if (is_array($_examID))
        {
            $_examID = array_unique($_examID);
        }
        else
        {
            $_examID = array();
        }
        db_update('edu_class_user', array('class_exam' => $_examID), array('class_id' => $class_id, 'month' => $class_month, 'class_year' => $class_year));
        msg(1, 'Success');
    }

    if ($handle == 'get_lv2')
    {
        $list = arrlist_search($level, array('pid' => post('pid')));
        msg(1, array('list' => $list));
    }

    if ($handle == 'get_user')
    {
        $kw = post('kw');
        $rt = get_user_by_kw($kw);
        msg(1, array('list' => $rt));
    }

    if ($handle == 'get_month_data')
    {
        if (empty($class_user))
        {
            exit('班級月份不存在');
        }
        $month                  = post('month');
        $_date_output           = get_class_user_days_string($class_user);
        $teacher                = decode_json($class_user['teacher']);
        $student                = decode_json($class_user['student']);
        $student_transfer       = decode_json($class_user['student_transfer']);
        $teacher                = edu_get_user($teacher);
        $student                = edu_get_user($student);
        $student_transfer       = edu_get_user($student_transfer);
        $rt                     = array();
        $rt['date_time']        = $_date_output;
        $rt['student']          = $student;
        $rt['teacher']          = $teacher;
        $rt['student_transfer'] = $student_transfer;
        // 班级上堂人数
        $rt['analytisc_days'] = get_class_every_day_attend($class_user);
        msg(1, $rt);
    }

    if ($handle == 'update_user')
    {
        $data                     = array();
        $month                    = post('date_month');
        $data['teacher']          = encode_json(post('teacher'));
        $data['student']          = encode_json(post('student'));
        $data['student_transfer'] = encode_json(post('student_transfer'));
        db_update('edu_class_user', $data, array('class_id' => $class_id, 'month' => $month, 'class_year' => $class_year));
        msg(1, 'Success', '#');
    }
?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3">班級管理</div>
        <form action="" method="post">
        <?php echo form_handle('update_user'); ?>
        <div class="filter form sublist">
            <div class="x1">
                <table class="table mytable">
                    <tr>
                        <td>班級:</td>
                        <td class="class_name"><?php echo $class['class_name']; ?><u><i class="fa fa-edit"></i></u></td>
                    </tr>
                    <tr>
                        <td>評分:</td>
                        <td>
                            <ul class="exam_list sublist sortable">
                                <?php if (!empty($exam)): ?>
<?php foreach ($exam as $key => $value): ?>
                                        <li data-id="<?php echo $value['id']; ?>"><?php echo $value['name']; ?><u><i class="fa fa-close"></i></u></li>
                                    <?php endforeach;?>
<?php endif;?>
                            </ul>
                            <div class="add_exam add_btn"><span class="form-button">新增評估</span></div>
                        </td>
                    </tr>
                    <tr>
                        <td>月份:</td>
                        <td>
                            <ul class="class_date sublist">
                                <li>
                                <?php foreach ($class_users as $key => $value): ?>
<?php
$_ext  = ($class_year == $value['class_year'] && $value['month'] == $class_month) ? 'checked="checked"' : '';
?>
                                    <label class="label-input"><input type="radio" name="date_month" year="<?php echo $value['class_year'] ?>" value="<?php echo $value['month']; ?>"<?php echo $_ext; ?>><?php echo $value['month']; ?></label>
                                <?php endforeach;?>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><span class="form-button class_months_btn" style="width: 100px;float: right;">管理月份</span></td>
                    </tr>
                    <tr>
                        <td>日期</td>
                        <td class="class_days">
                            <div class="date_txt">
                            </div>
                            <u><i class="fa fa-edit"></i></u>
                        </td>
                    </tr>
                    <tr>
                        <td style="line-height:1.2">每日<br>人數</td>
                        <td id="analytisc_days"></td>
                    </tr>
                    <tr>
                        <td>老師:</td>
                        <td class="rtable">
                            <div class="xtable">
                                <table class="class_teacher sublist">
                                    <thead>
                                        <tr>
                                            <td>ID</td>
                                            <td>Last Name</td>
                                            <td>First Name</td>
                                            <td>Gender</td>
                                            <td>Email</td>
                                            <td>Phone</td>
                                            <td>Delete</td>
                                        </tr>
                                    </thead>
                                    <tbody class="teacher_list">
                                        <script type="text/html">
                                        {{each teacher v i}}
                                        <tr>
                                            <td><span class="id">{{v.ID}}</span><input type="hidden" name="teacher[]" value="{{v.ID}}"></td>
                                            <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_last_name}}</a></td>
                                            <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_first_name}}</a></td>
                                            <td>{{v.billing_gender}}</td>
                                            <td>{{v.user_email}}</td>
                                            <td><a target="_blank" href="https://wa.me/{{v.billing_phone}}">{{v.billing_phone}}</a></td>
                                            <td><u><i class="fa fa-close"></i></u></td>
                                        </tr>
                                        {{/each}}
                                        </script>
                                    </tbody>
                                </table>
                            </div>
                            <div class="box" tabindex="-1">
                                <div class="add_teacher add_btn add_user"><input type="text" class="form-text"><span class="form-label">新增老師</span></div>
                                <div class="suggest_wrap" data-role="teacher">
                                    <span class="close"><i class="fa fa-close"></i></span>
                                    <div class="table">
                                        <table class="suggest">
                                            <script type="text/html">
                                            {{each list v i}}
                                            <tr>
                                                <td><span class="id">{{v.ID}}</span><input type="hidden" value="{{v.ID}}"></td>
                                                <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_last_name}}</a></td>
                                                <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_first_name}}</a></td>
                                                <td>{{v.billing_gender}}</td>
                                                <td>{{v.user_email}}</td>
                                                <td><a target="_blank" href="https://wa.me/{{v.billing_phone}}">{{v.billing_phone}}</a></td>
                                                <td><u><i class="fa fa-close"></i></u></td>
                                            </tr>
                                            {{/each}}
                                            </script>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <br>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="padding: 3px;">
                            <a href="attendance.php?class_id=<?php echo $class['class_id']; ?>"><span class="form-button my_attn_btn" style="width: 100px;float: right;">點名管理</span></a>
                            <span class="form-button order_btn" style="width: 100px;float: right;margin-right: 10px;">報名記錄</span>
                            <span class="form-button get_prev_btn" style="width: 100px;float: right;margin-right: 10px;">獲取上月資料</span>
                        </td>
                    </tr>
                    <tr>
                        <td>學生:</td>
                        <td class="rtable">
                            <div class="xtable">
                                <table class="class_student sublist">
                                    <thead>
                                        <tr>
                                            <td>ID</td>
                                            <td>Last Name</td>
                                            <td>First Name</td>
                                            <td>Gender</td>
                                            <td>Email</td>
                                            <td>Phone</td>
                                            <td>Days</td>
                                            <td>Delete</td>
                                        </tr>
                                    </thead>
                                    <tbody class="student_list">
                                        <script type="text/html">
                                        {{each student v i}}
                                        <tr>
                                            <td><span class="id">{{v.ID}}</span><input type="hidden" value="{{v.ID}}" name="student[]"></td>
                                            <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_last_name}}</a></td>
                                            <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_first_name}}</a></td>
                                            <td>{{v.billing_gender}}</td>
                                            <td>{{v.user_email}}</td>
                                            <td><a target="_blank" href="https://wa.me/{{v.billing_phone}}">{{v.billing_phone}}</a></td>
                                            <td><u><i class="fa fa-clock-o"></i></u></td>
                                            <td><u><i class="fa fa-close"></i></u></td>
                                        </tr>
                                        {{/each}}
                                        </script>
                                    </tbody>
                                </table>
                            </div>
                            <div class="box" tabindex="-1">
                                <div class="add_student add_btn add_user"><input type="text" class="form-text"><span class="form-label">新增學生</span></div>
                                <div class="suggest_wrap" data-role="student">
                                    <span class="close"><i class="fa fa-close"></i></span>
                                    <div class="table">
                                        <table class="suggest">
                                            <script type="text/html">
                                            {{each list v i}}
                                            <tr>
                                                <td><span class="id">{{v.ID}}</span><input type="hidden" value="{{v.ID}}"></td>
                                                <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_last_name}}</a></td>
                                                <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_first_name}}</a></td>
                                                <td>{{v.billing_gender}}</td>
                                                <td>{{v.user_email}}</td>
                                                <td><a target="_blank" href="https://wa.me/{{v.billing_phone}}">{{v.billing_phone}}</a></td>
                                                <td><u><i class="fa fa-clock-o"></i></u></td>
                                                <td><u><i class="fa fa-close"></i></u></td>
                                            </tr>
                                            {{/each}}
                                            </script>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="line-height:1.2">插班<br>補堂<br>其他</td>
                        <td class="rtable">
                            <div class="xtable">
                                <table class="class_student sublist">
                                    <thead>
                                        <tr>
                                            <td>ID</td>
                                            <td>Last Name</td>
                                            <td>First Name</td>
                                            <td>Gender</td>
                                            <td>Email</td>
                                            <td>Phone</td>
                                            <td>Days</td>
                                            <td>Delete</td>
                                        </tr>
                                    </thead>
                                    <tbody class="student_list">
                                        <script type="text/html">
                                        {{each student_transfer v i}}
                                        <tr>
                                            <td><span class="id">{{v.ID}}</span><input type="hidden" value="{{v.ID}}" name="student_transfer[]"></td>
                                            <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_last_name}}</a></td>
                                            <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_first_name}}</a></td>
                                            <td>{{v.billing_gender}}</td>
                                            <td>{{v.user_email}}</td>
                                            <td><a target="_blank" href="https://wa.me/{{v.billing_phone}}">{{v.billing_phone}}</a></td>
                                            <td><u><i class="fa fa-clock-o"></i></u></td>
                                            <td><u><i class="fa fa-close"></i></u></td>
                                        </tr>
                                        {{/each}}
                                        </script>
                                    </tbody>
                                </table>
                            </div>
                            <div class="box" tabindex="-1">
                                <div class="add_student add_btn add_user"><input type="text" class="form-text"><span class="form-label">新增插班生</span></div>
                                <div class="suggest_wrap" data-role="student_transfer">
                                    <span class="close"><i class="fa fa-close"></i></span>
                                    <div class="table">
                                        <table class="suggest">
                                            <script type="text/html">
                                            {{each list v i}}
                                            <tr>
                                                <td><span class="id">{{v.ID}}</span><input type="hidden" value="{{v.ID}}"></td>
                                                <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_last_name}}</a></td>
                                                <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.billing_first_name}}</a></td>
                                                <td>{{v.billing_gender}}</td>
                                                <td>{{v.user_email}}</td>
                                                <td><a target="_blank" href="https://wa.me/{{v.billing_phone}}">{{v.billing_phone}}</a></td>
                                                <td><u><i class="fa fa-clock-o"></i></u></td>
                                                <td><u><i class="fa fa-close"></i></u></td>
                                            </tr>
                                            {{/each}}
                                            </script>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        </form>
        <div class="section"><button class="form-button form-submit">保存</button></div>
        <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<div class="hide">
    <div class="level">
        <div>請選擇課程</div>
        <div class="lv1">
            <select name="lv1" class="form-select">
                <?php foreach (arrlist_search($level, array('pid' => 0)) as $key => $value): ?>
<?php
    if (!isset($_pid))
    {
        $_pid = $value['id'];
    }
?>
                    <option value="<?php echo $value['id']; ?>"><?php echo $value['name']; ?></option>
                <?php endforeach;?>

            </select>
        </div>
        <div class="mgt">請選擇級別</div>
        <div class="lv2">
            <select name="lv2" class="form-select">
                <script type="text/html">
                    {{each list v i}}
                    <option value="{{v.id}}">{{v.name}}</option>
                    {{/each}}
                </script>
            </select>
        </div>
        <div>
            <button class="form-button mgt add_exam">新增</button>
        </div>
    </div>
</div>
<style>
.level{width: 300px;height: 210px;padding: 15px;}
.hide{width: 0;height: 0;overflow: hidden;}
.label-input{margin-right: 10px;}
.class_days{position: relative;}
.class_days u{position: absolute;right: 10px;top: 0;bottom: 0;height: 15px;margin-top: auto;margin-bottom: auto;cursor: pointer;}
ul li ul{margin-left: 50px;margin-top: 10px;}
ul ul li{border-bottom: dashed 1px #eee;line-height:32px;}
ul li h3 a{float: right;color: #333;}
ul li h3 a i{margin-left: 2px;}
table.table td{border: none !important;}
table.table tr td:first-child{vertical-align: top;line-height: 38px;font-weight: bold;width: 60px;}
table.table tr td:last-child{text-align: left;}
table.table tr td ul li{line-height: 32px;}
.form-label{position: absolute;top: 0;bottom: 0;right: 0;width: 80px;text-align: center;line-height: 40px;}
.sublist li{margin: 3px 0;}
.add_btn{position: relative;margin-top: 10px;overflow: hidden;text-align: right;}
.add_btn span{width: 100px;display: inline-block;}
.box{position: relative;}
div.sublist li{position: relative;}
div.sublist li u{position: absolute;top: 0;bottom: 0;right: 10px;cursor: pointer;line-height: 32px;transition: all .3s;}
div.sublist li u:hover{color: red;}
div.sublist li.on{display: block;}
div.sublist li.off{display: none;}

table.sublist{width: 100%;font-size: 14px;width: 100%;border-spacing:0;border-collapse:collapse;overflow: hidden;border-radius: 4px;}
table.sublist thead{font-weight: bold;background: #eee;color: #333;}
table.sublist thead td{font-size: 14px;padding: 0;line-height: 1.2;}
table.sublist tbody tr td{line-height: 1;padding: 0px;border: solid 1px #eee !important;}
table.sublist tbody tr td:first-child{text-align: center;width: 45px;}
table.sublist tbody tr td:nth-child(2){width: 100px;}
table.sublist tbody tr td:nth-child(3){width: 100px;}

table.sublist tbody tr td:nth-child(4){width: 100px;}
table.sublist tbody tr td:last-child{text-align: center;width: 55px;}
table.sublist tbody td u{cursor: pointer;}
table.sublist tbody tr td span.id{padding: 0 3px;line-height: 1.4;font-size: 12px;}

.suggest_wrap{position:absolute;bottom: 100%;left: 0;right: 0;z-index: 10;padding: 5px 0;width: 100%;display: none;}
.suggest_wrap .close{display: inline-block;width: 29px;height: 29px;position: absolute;top: 0px;right: 0px;background: red;border-radius: 50%;}
.suggest_wrap .close .fa{width: 29px;height: 29px;text-align: center;color: #fff;line-height: 29px;cursor: pointer;font-size: 16px;}
.suggest_wrap .table{display: block;overflow-y: auto;margin-top: 10px;max-height: 300px;background: #ffe;border-radius: 4px;box-shadow: 0 0 5px #ddd;}
table.suggest{width: 100%;}
.suggest tr td{padding: 0 !important;cursor: pointer;line-height: 1;border-bottom: solid 1px #eee !important;font-size: 13px;}
.suggest tr:hover, table.sublist tbody tr:hover{background: #fee;}
.suggest td span.id, table.sublist tbody td span.id{display: inline-block;padding:0 3px;min-width: 35px;background: #ddd;color: #fff;border-radius: 4px;text-align: center;line-height: 1.5;}
.suggest td u{display: none;}
.sortable li{user-select: none; cursor:move;}
td.class_name u{float: right;cursor: pointer;margin-right: 10px;}
#analytisc_days span{margin-right: 10px;display: inline-block;}
#analytisc_days span[red]{font-weight: bold;color: red;}
.class_months_btn{font-weight: normal;}
@media all and (max-width: 600px)
{
    table.mytable{display: block;}
    table.mytable>tbody{display: block;}
    table.mytable>tbody>tr{display: block;}
    table.mytable>tbody>tr>td{display: block;width: auto !important;padding: 0;margin: 0;text-align: left;}

    .xtable{overflow-x: auto;}
    .xtable table{min-width:700px;}
    .exam_list li u{display: none;}

    td.rtable .xtable{margin-top:8px;}

}

</style>
<script>

window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        location.reload();
    }
});

function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}
$(function(){
    $(".add_exam span").click(function(event) {
        layer.open({type:1, title:false, content: $(".level")});
    });

    $(".class_months_btn").click(function(event) {
        layer.open({type:2,title:'增加月份',area:['360px','250px'],content: 'class_months.php?class_id=<?php echo $class_id; ?>'});
    });

    $(".add_date span.add").click(function(event) {
        $("ul.class_date").prepend('<li><input type="text" class="form-text datepicker" name="class_date[]" value="" readonly="readonly"/><u><i class="fa fa-close"></i></u></li>');
    });
    $("body").on('focus', '.class_date input.datepicker', function(event) {
        $(this).datepicker({
            multidate: true,
            format: 'yyyy-mm-dd'
        });
    });
    $("body").on('click', 'table.sublist i.fa-close', function(event) {
        // $(this).parent('li').remove();
        $(this).parent('u').parent('td').parent('tr').remove();
        $(".form-submit").click();
    });

    $("body").on('click', 'table.sublist i.fa-clock-o', function(event) {
        var id = $(this).parent('u').parent('td').parent('tr').find('td span.id').text();
        layer.open({type:2,title:'修改班級',area:['360px','500px'],content: 'user_days.php?class=<?php echo $class_id; ?>&month='+$("input[name=date_month]:checked").val()+'&user_id='+id});
    });

    $("body").on('click', '.exam_list u', function(event) {
        $(this).parent('li').remove();
        update_lv();
    });

    $(".add_user input").on('focus input propertychange', function(event) {
        $this = $(this);
        var kw = $(this).val();
        if (kw.length == 0){
            $this.parents('.box').find('.suggest_wrap').hide();
        }else{
            $.ajax({
                url: '#',
                type: 'POST',
                data: {
                    handle: 'get_user',
                    kw: kw
                },
                success: function(msg) {
                    $this.parents('.box').find('table.suggest').render(msg.msg);
                    $this.parents('.box').find('.suggest_wrap').show();
                }
            });
        }

    });
    $("body").on('click', '.suggest tr', function(event) {
        $this = $(this);
        var type = $this.parents(".suggest_wrap").attr('data-role');
        $ele = $('<tr>'+$this.html()+'</tr>');
        $ele.find('input').attr('name',type+'[]');
        $this.parents('.rtable').find('div.xtable').find('table.sublist tbody').append($ele);
        $(this).parents('.suggest_wrap').hide();
        $(".form-submit").click();
    });

    $("body").on('click', '.close', function(event) {
        $(this).parents('.suggest_wrap').hide();
    });

    $(".datelist").click(function(event) {
        $(".class_date li.off").toggle();
    });

    $(".form-submit").click(function(event) {
        $("form").submit();
    });

    $("body").on('click', 'input[name=date_month]', function(event) {
        $.load.show();
        var month = $(this).val();
        window.location.href= "class.php?class=<?php echo get('class'); ?>&month="+month+"&year="+$(this).attr('year');
    });

    if (!$("input[name=date_month]:checked").length)
    {
        $("input[name=date_month]:first").prop('checked', true);
    }

    $.ajax({
        url: '#',
        type: 'POST',
        data: {handle: 'get_month_data', month: $("input[name=date_month]:checked").val()},
        success:function(msg){
            $("#analytisc_days").html(msg.msg.analytisc_days);
            $(".date_txt").html(msg.msg.date_time);
            $("tbody.student_list").render(msg.msg);
            $("tbody.teacher_list").render(msg.msg);
            $.load.hide();
        }
    });

    function get_lv2()
    {
        var pid = $("select[name=lv1]").val();
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'get_lv2', pid: pid},
            success:function(msg){
                $("select[name=lv2]").render(msg.msg);
            }
        });
    }
    get_lv2();
    $("select[name=lv1]").change(function(event) {
        get_lv2();
    });

    function update_lv()
    {
        var id = [];
        $(".exam_list li").each(function(index, el) {
            var _id = $(this).attr('data-id');
            _id = Number(_id);
            id.push(_id);
        });


        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle: 'update_exam',id:id},
            success:function(msg){
                if (msg.code==0) {
                    $msg.error(msg.msg)
                }else{
                    $msg.success(msg.msg)
                }

            }
        });
    }

    $("button.add_exam").click(function(event) {
        var val = $("select[name=lv2]").val();
        var txt = $("select[name=lv2] option:selected").text();
        $(".exam_list").append('<li data-id="'+val+'">'+txt+'<u><i class="fa fa-close"></i></u></li>');
        update_lv();
    });

    if (!isMobileDevice()) {
        Sortable.create($(".sortable")[0], {onUpdate:function(){
            update_lv();
        }});
    }

    $("td.class_name u").click(function(event) {
        layer.open({type:2,title:'修改班級',area:['360px','300px'],content: 'class_time.php?class=<?php echo $class_id; ?>'});
    });

    $(".class_days u").click(function(event) {
        layer.open({type:2,title:'修改班級',area:['360px','500px'],content: 'class_days.php?class=<?php echo $class_id; ?>&month='+$("input[name=date_month]:checked").val()});
    });


    $(".order_btn").click(function(event) {
        layer.open({type:2,title:'報名記錄',area:['360px','500px'],content: 'class_order.php?class=<?php echo $class_id; ?>&month='+$("input[name=date_month]:checked").val()});
    });

    $(".get_prev_btn").click(function(event) {
        layer.confirm('確認獲取上月資料?', {
            btn: ['確認', '取消'] //按钮
        }, function() {
            $.ajax({
                url: '#',
                type: 'POST',
                dataType: 'json',
                data: {handle: 'get_prev_data'},
                success: function(msg)
                {
                    $.msg(msg);
                }
            });
            top.layer.closeAll();
        }, function() {

        });
    });








});
</script>