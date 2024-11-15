<?php
define('DEBUG', 1);
include __DIR__.'/core/init.php';
    // filter classes
    $handle = post('handle');
    if ($handle == 'get_user')
    {
        $kw = post('kw');
        $rt = get_user_by_kw($kw);
        msg(1, array('list' => $rt));
    }
    $district_id  = get('district_id', 0);
    $district_id2 = empty($district_id) ? 0 : get('district_id2', 0);
    $lv3          = get('lv3');
    $class_id     = get('class_id');
    $classes_id = array();
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

    $teacher = get('teacher');
    if ($teacher) {
        $_class_user = db_find('edu_class_user', array('%teacher'=>'"'.$teacher.'"'), array('id' => -1));
        foreach ($_class_user as $key => $value)
        {
            $classes_id[] = $value['class_id'];
        }
        $classes_id = array_unique($classes_id);
        if (empty($classes_id)) {
            $classes_id = array('x');
        }
    }

    if (!empty($class_id))
    {
        $where['class_id'] = $class_id;
    }
    else if (!empty($classes_id))
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
    foreach ($classes as $key => $value)
    {
        $classes_id[] = $value['class_id'];
    }
    // 獲取當前班級
    $classes  = arrlist_change_key($classes, 'class_id');
    // 班級篩選结束
function stopwatch_milliseconds($time)
{
    if (strpos($time, ':')===false)
    {
        return 0;
    }
    $parts = explode(':', $time);
    $minutes = (int)$parts[0];
    $seconds = (int)$parts[1];
    $seconds_100 = (int)substr($parts[2], 0, 2);
    $milliseconds = ($minutes * 60 + $seconds) * 1000 + $seconds_100 * 10;
    return $milliseconds;
}
$exam_id = get('exam_id');
$class_month = get('class_month');
$level  = db_find('edu_level');
$exam_ids = array();
foreach ($level as $key => $value) {
    $d = decode_json($value['data']);
    if (empty($d['type']) || $d['type'] != 'time')
    {
        unset($level[$key]);
    }
    else
    {
        $exam_ids[] = $value['id'];
    }
}
$level  = arrlist_change_key($level, 'id');
$where = array();
$where['!exam_data']='';
$daterange = get('daterange');
if ($daterange) {
    $daterange = explode(' - ', $daterange);
    $where['>=created'] = strtotime(($daterange[0]));
    $where['<=created'] = strtotime(($daterange[1]));
}
if ($exam_id)
{
    $where['exam_id'] = $exam_id;
}
else
{
    $where['exam_id'] = $exam_ids;
}
if ($classes_id) {
    $where['class_id'] = $classes_id;
}
if ($class_month) {
    $where['class_month'] = $class_month;
}
$result = db_find('edu_result', $where);

$exam_date      = array();
$exam_type      = array();
$user_ids = array();
foreach ($result as $key => $value) {
    $result[$key]['exam_data'] = stopwatch_milliseconds($value['exam_data']);
}
$result = arrlist_search($result,array(), array('exam_data'=>1));
foreach ($result as $key => $value)
{
    $exam_date[] = $value['exam_date'];
    $exam_type[] = $value['exam_id'];
    $user_ids[]  = $value['user_id'];
}


$exam_date = array_unique($exam_date);
$exam_type = array_unique($exam_type);
$user_ids  = array_unique($user_ids);
$users = edu_get_user($user_ids);


$age = get('age');
if ($age) {
    foreach ($users as $key => $value)
    {
        if (empty($value['billing_birthdate']) || calculate_age($value['billing_birthdate']) != $age)
        {
            unset($users[$key]);
        }
    }
}



$result_last = array();
$result_boy = array();
$result_girl = array();
foreach($result as $key=>$value)
{
    if (empty($users[$value['user_id']]))
    {
        continue;
    }
    if (!isset($result_last[$value['user_id']]) && count($result_last)<10)
    {
        $result_last[$value['user_id']] = $value['exam_data'];
    }
    $gender = form_val('billing_gender', $users[$value['user_id']] && count($result_boy)<10);
    if ($gender=='男' && !isset($result_boy[$value['user_id']])) {
        $result_boy[$value['user_id']] = $value['exam_data'];
    }
    if ($gender=='女' && !isset($result_girl[$value['user_id']]) && count($result_girl)<10) {
        $result_girl[$value['user_id']] = $value['exam_data'];
    }
}



$chart_type = get('chart_type');
?>
<?php if($chart_type): ?>
<?php header('Content-Type: text/javascript');?>
jQuery(function($){
    var myChart = echarts.init(document.getElementById('main'));
    var  option = {
            title: {text: '<?php echo empty($exam_id) ? '' : $level[$exam_id]['name'];?>'},
            tooltip: {
                trigger: 'axis'
            },
            //grid: {left: '30px', right: '50px', top: '65px', bottom: 0, containLabel: true},
            xAxis:
            {
                //name:"日期",
                type: 'value',
                axisLine: {show:false},
                axisTick: {show:false},
                axisLabel: {rotate: -45},
                boundaryGap: false,
                axisLabel: {
                    formatter: function (value, index) {
                        var time    = value;
                        var minutes = Math.floor(time / 60000);
                        var seconds = Math.floor((time % 60000)/1000);
                        var milliseconds = Math.floor((time % 1000)/10);
                        var formattedTime = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0') + ':' + milliseconds.toString().padStart(2, '0');
                        return formattedTime;
                    }
                }
            },
            yAxis:
            {
                // name:"時間(min)",
                type: 'category',
                // inverse: true,
                data: [
                    <?php foreach($result_last as $user_id => $user_data){
                        $user = $users[$user_id];
                        echo '"';
                        echo empty($user['billing_first_name']) ? $user['display_name'] : $user['billing_first_name'];
                        echo '",';
                    }?>
                ]
            },
            series:
            [
                {
                    type: 'bar',
                    showSymbol: true,
                    connectNulls: true,
                    label: {
                        show: true,
                        formatter: function (params) {
                            var time    = params.value;
                            var minutes = Math.floor(time / 60000);
                            var seconds = Math.floor((time % 60000)/1000);
                            var milliseconds = Math.floor((time % 1000)/10);
                            var formattedTime = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0') + ':' + milliseconds.toString().padStart(2, '0');
                            return formattedTime;
                        }
                    },
                    data: [
                        <?php foreach($result_last as $user_id => $user_data){
                            echo $user_data.',';
                        }?>
                    ]
                }
            ]
        };
    option && myChart.setOption(option, true);
    window.addEventListener('resize', function() {
        myChart.resize();
    });

    var myChart2 = echarts.init(document.getElementById('main_boy'));
    var  option = {
            title: {text: '<?php echo empty($exam_id) ? '' : $level[$exam_id]['name'];?>'},
            tooltip: {
                trigger: 'axis'
            },
            //grid: {left: '30px', right: '50px', top: '65px', bottom: 0, containLabel: true},
            xAxis:
            {
                //name:"日期",
                type: 'value',
                axisLine: {show:false},
                axisTick: {show:false},
                axisLabel: {rotate: -45},
                boundaryGap: false,
                axisLabel: {
                    formatter: function (value, index) {
                        var time    = value;
                        var minutes = Math.floor(time / 60000);
                        var seconds = Math.floor((time % 60000)/1000);
                        var milliseconds = Math.floor((time % 1000)/10);
                        var formattedTime = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0') + ':' + milliseconds.toString().padStart(2, '0');
                        return formattedTime;
                    }
                }
            },
            yAxis:
            {
                // name:"時間(min)",
                type: 'category',
                // inverse: true,
                data: [
                    <?php foreach($result_boy as $user_id => $user_data){
                        $user = $users[$user_id];
                        echo '"';
                        echo empty($user['billing_first_name']) ? $user['display_name'] : $user['billing_first_name'];
                        echo '",';
                    }?>
                ]
            },
            series:
            [
                {
                    type: 'bar',
                    showSymbol: true,
                    connectNulls: true,
                    label: {
                        show: true,
                        formatter: function (params) {
                            var time    = params.value;
                            var minutes = Math.floor(time / 60000);
                            var seconds = Math.floor((time % 60000)/1000);
                            var milliseconds = Math.floor((time % 1000)/10);
                            var formattedTime = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0') + ':' + milliseconds.toString().padStart(2, '0');
                            return formattedTime;
                        }
                    },
                    data: [
                        <?php foreach($result_boy as $user_id => $user_data){
                            echo $user_data.',';
                        }?>
                    ]
                }
            ]
        };
    option && myChart2.setOption(option, true);
    window.addEventListener('resize', function() {
        myChart2.resize();
    });


    var myChart3 = echarts.init(document.getElementById('main_girl'));
    var  option = {
            title: {text: '<?php echo empty($exam_id) ? '' : $level[$exam_id]['name'];?>'},
            tooltip: {
                trigger: 'axis'
            },
            //grid: {left: '30px', right: '50px', top: '65px', bottom: 0, containLabel: true},
            xAxis:
            {
                //name:"日期",
                type: 'value',
                axisLine: {show:false},
                axisTick: {show:false},
                axisLabel: {rotate: -45},
                boundaryGap: false,
                axisLabel: {
                    formatter: function (value, index) {
                        var time    = value;
                        var minutes = Math.floor(time / 60000);
                        var seconds = Math.floor((time % 60000)/1000);
                        var milliseconds = Math.floor((time % 1000)/10);
                        var formattedTime = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0') + ':' + milliseconds.toString().padStart(2, '0');
                        return formattedTime;
                    }
                }
            },
            yAxis:
            {
                // name:"時間(min)",
                type: 'category',
                // inverse: true,
                data: [
                    <?php foreach($result_girl as $user_id => $user_data){
                        $user = $users[$user_id];
                        echo '"';
                        echo empty($user['billing_first_name']) ? $user['display_name'] : $user['billing_first_name'];
                        echo '",';
                    }?>
                ]
            },
            series:
            [
                {
                    type: 'bar',
                    showSymbol: true,
                    connectNulls: true,
                    label: {
                        show: true,
                        formatter: function (params) {
                            var time    = params.value;
                            var minutes = Math.floor(time / 60000);
                            var seconds = Math.floor((time % 60000)/1000);
                            var milliseconds = Math.floor((time % 1000)/10);
                            var formattedTime = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0') + ':' + milliseconds.toString().padStart(2, '0');
                            return formattedTime;
                        }
                    },
                    data: [
                        <?php foreach($result_girl as $user_id => $user_data){
                            echo $user_data.',';
                        }?>
                    ]
                }
            ]
        };
    option && myChart3.setOption(option, true);
    window.addEventListener('resize', function() {
        myChart3.resize();
    });
});
<?php exit();?>
<?php endif; ?>
<?php include __DIR__.'/core/common_header.php';?>
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <div class="h3">游泳統計Top10</div>
<!-- 地区筛选 -->
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
<div class="section">
    <select name="class_id" class="form-select">
        <option value="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $district_id2; ?>&lv3=<?php echo $lv3; ?>&class_id=0">請選擇班級</option>
        <?php foreach ($classes as $key => $class): ?>
            <option value="?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $district_id2; ?>&lv3=<?php echo $lv3; ?>&class_id=<?php echo $class['class_id']; ?>"<?php if ($class['class_id'] == get('class_id')): ?>selected="selected"<?php endif;?>><?php echo $class['class_name']; ?></option>
        <?php endforeach;?>
    </select>
</div>
<input type="hidden" teacher value="<?php echo get("teacher");?>">
<div class="section user box pd">
        <div class="form-label-input">
            <label class="form-label" style="width:70px;font-weight:bold;">教練</label>
            <div class="form-input"><input type="text" class="form-text" user-suggest></div>
        </div>
        <div class="user_show" style="padding-left:70px;padding-top:8px;">
            <?php
            $get_user_id = get('teacher');
            if ($get_user_id) {
                $_u = get_user_one($get_user_id);
                if (!empty($_u)) {
                    echo form_val('billing_first_name', $_u).' | '.form_val('billing_last_name', $_u).' | '.form_val('billing_gender', $_u).' | '.form_val('billing_email', $_u).' | '.form_val('billing_phone', $_u).'<i class="fa fa-close"></i>';
                }
            }
            ?>
        </div>
        <div class="suggest_wrap">
            <span class="close"><i class="fa fa-close"></i></span>
            <div class="table">
                <table class="suggest">
                    <script type="text/html">
                    {{each list v i}}
                    <tr>
                        <td><span class="id">{{v.ID}}</span><input type="hidden" value="{{v.ID}}"></td>
                        <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.last_name}}</a></td>
                        <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.first_name}}</a></td>
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


<div class="section">
    <table class="table">
        <tr><td>年齡</td><td><input type="text" age value="<?php echo(get('age'))?>" class="form-text"></td></tr>
    </table>
</div>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<div class="section">
    <table class="table">
        <tr><td>日期</td><td><input type="text" daterange value="<?php echo(get('daterange'))?>" class="form-text" daterange></td></tr>
    </table>
</div>
<div class="title" style="margin-top: 15px;">
    <?php foreach ($level as $key => $val): ?>
    <span exam-id="<?php echo $val['id']?>"><?php echo $val['name']; ?></span>
    <?php endforeach;?>
</div>
<div class="section">
    <button class="form-button search_btn">提交查詢</button>
</div>
<!-- 地区筛选结束 -->
    <div id="main" style="width: 100%;aspect-ratio: 4 / 3;"></div>
    <div>男</div>
    <div id="main_boy" style="width: 100%;aspect-ratio: 4 / 3;"></div>
    <div>女</div>
    <div id="main_girl" style="width: 100%;aspect-ratio: 4 / 3;"></div>
</body>
</html>
<style>
.title{margin-top: 15px;}
.title span{display: inline-block;margin: 0 3px;background: #a1dccf;padding: 2px 5px;border-radius: 4px;cursor: pointer;box-shadow: 0 0 3px #ddd;font-size: 14px;font-weight: bold;color: #666;margin-bottom: 5px;user-select: none;}
.title span.on{background: #258872;color: #fff;}
.district ul li.on{font-weight: bold;}
ul li{line-height:32px;float: left;margin-right: 10px;line-height: 1.2;}
ul li h3{padding: 10px;}
ul li h3 span{float: right;color: #fff;}
ul li h3 span i{margin-left: 2px;}
li.class{transition: all .3s;border-radius: 4px;background: #258872;overflow: hidden;margin-top: 15px;}
li.class h3{color: #fff;}
li.class ul{background: #d1dddd;}
li.class ul li{padding: 0 10px;}

.suggest_wrap{position:absolute;top: 100%;left: 0;right: 0;z-index: 10;padding: 5px 0;width: 100%;display: none;}
.suggest_wrap .close{display: inline-block;width: 29px;height: 29px;position: absolute;top: 0px;right: 0px;background: red;border-radius: 50%;}
.suggest_wrap .close .fa{width: 29px;height: 29px;text-align: center;color: #fff;line-height: 29px;cursor: pointer;font-size: 16px;}
.suggest_wrap .table{display: block;overflow-y: auto;margin-top: 10px;max-height: 300px;background: #ffe;border-radius: 4px;box-shadow: 0 0 5px #ddd;}
table.suggest{width: 100%;}
.suggest tr td{padding: 0 !important;cursor: pointer;line-height: 1;border-bottom: solid 1px #eee !important;font-size: 13px;}
.suggest tr:hover, table.sublist tbody tr:hover{background: #fee;}
.suggest td span.id, table.sublist tbody td span.id{display: inline-block;padding:0 3px;min-width: 35px;background: #ddd;color: #fff;border-radius: 4px;text-align: center;line-height: 1.5;}
.suggest td u{display: none;}
.box{position:relative;overflow: visible;;}
.user_show i.fa{cursor: pointer;float: right;color:red;}
</style>
<script>
function updateQueryParams(params) {
    const url = new URL(window.location.href);
    for (const [key, value] of Object.entries(params)) {
        url.searchParams.set(key, value);
    }
    return url.toString();
}

jQuery(function($){
    $("span[exam-id]").click(function(){
        if($(this).hasClass('on'))
        {
            return false;
        }
        $(this).addClass('on').siblings().removeClass('on');
        var exam_id = $(this).attr('exam-id');
        var type = $(this).attr('data-type');
        $.getScript(updateQueryParams({ chart_type: 'bar', exam_id: exam_id}));
    });
    $("span[exam-id]:eq(0)").click();
    $("select[name=group]").on('change', function(event) {
        window.location.href='?group='+$(this).val();
    });
    $("select[name=class_id]").on('change', function(event) {
        window.location.href = $(this).val();
    });
    $('input[daterange]').daterangepicker({locale: {format: 'YYYY-MM-DD'}});
    $(".search_btn").click(function(){
        var data = {};
        data.age = $('input[age]').val();
        data.teacher = $('input[teacher]').val();
        data.daterange = $('input[daterange]').val();
        var url = updateQueryParams(data);
        window.location.href=url;
    });
    // 處理用戶ajax搜索
    $("[user-suggest]").on('focus input propertychange', function(event) {
        $this = $(this);
        var kw = $(this).val();
        if (kw.length == 0){
            $this.parents('.box').find('.suggest_wrap').hide();
        }else{
            $.ajax({
                url: '',
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
        user_id = $(this).find("span.id").text();
        var data = {};
        data.teacher = user_id;
        var url = updateQueryParams(data);
        window.location.href=url;

    });
    $("body").on('click', '.close', function(event) {
        $(this).parents('.suggest_wrap').hide();
    });


});
</script>
