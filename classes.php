<?php
define('DEBUG', 1);
include __DIR__.'/core/init.php';
$handle = post('handle');
if ($handle == 'get_user')
{
    $kw = post('kw');
    $rt = get_user_by_kw($kw);
    msg(1, array('list' => $rt));
}
$get_user_id = get('user_id');
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
    $where['%class_name'] = $lv3;
}
$classes = db_find('edu_class', $where);
$classes = arrlist_change_key($classes, 'class_id');


$edu_class_user_where = array();
if ($get_user_id) {
    $edu_class_user_where = array('%teacher'=>'"'.$get_user_id.'"','|%student'=>'"'.$get_user_id.'"','|%student_transfer'=>'"'.$get_user_id.'"');
}
$classes_user = db_find('edu_class_user', $edu_class_user_where);



foreach ($classes_user as $key => $value)
{
    if (!isset($classes[$value['class_id']]))
    {
        continue;
    }

    if (!is_array($classes[$value['class_id']]['date_month']))
    {
        $classes[$value['class_id']]['date_month'] = array();
    }
    $classes[$value['class_id']]['date_month'][] = $value['month'];
}



// 班級排序, 獲取班級信息
$no_teacher_num = 0;
$no_days_num = 0;

foreach ($classes as $key => $value) {
    // month
    $months = $value['date_month'];
    if (!is_array($months)) {
        // var_dump($months);
        // exit;
        $months = array();
    }
    $month = '';
    foreach ($months as $_month)
    {
        $__m = get('m', date('Y-m'));
        $__m = strtotime($__m);
        $class_year = date('Y', $__m);
        if (is_current_month($_month, $__m)) {
            $month = $_month;
            break;
        }
    }
    if (empty($month))
    {
        $month = 'x';
    }

    $_user = arrlist_search_one($classes_user, array('class_id'=>$value['class_id'], 'month'=>$month, 'class_year'=>$class_year));

    if (empty($_user)) {
        unset($classes[$key]);
        continue;
    }
    $value['student']  = decode_json($_user['student']);
    $value['student_transfer']  = decode_json($_user['student_transfer']);
    $value['teacher']  = decode_json($_user['teacher']);
    $value['sort'] = empty($value['student']) ? 1 : 0;
    $value['days'] = empty($_user['days']) ? get_days($value['date_time'], $month) : $_user['days'];

    $value['class_every_day'] = get_class_every_day_attend($_user);
    $style='';
    $style2 = '';
    if (!empty($value['student']) && empty($value['teacher'])) {
        $style = 'red';
        $no_teacher_num++;
    }
    if (strpos($value['class_every_day'], '<span red>') !== false)
    {
        $style2 = 'blue';
        $no_days_num++;
    }
    $value['style'] = $style;
    $value['style2'] = $style2;
    $classes[$key] = $value;

    // if (empty($value['student']) && empty($value['student_transfer']))
    // {
    //     unset($classes[$key]);
    // }
}
// $classes = arrlist_multisort($classes, 'sort', true);

// 当前星期排到最前
$classes = classes_sort($classes);

?>
<?php include __DIR__.'/core/common_header.php'; ?>
    <div class="h3">班級管理
        <?php if($no_teacher_num): ?><span class="no_teacher_num" title="無教練班級"><?php echo $no_teacher_num;?></span><?php endif; ?>
        <?php if($no_days_num): ?><span class="no_days_num" title="人數不合規班級"><?php echo $no_days_num;?></span><?php endif; ?>
    </div>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($district_id)): ?>class="on"<?php endif ?>><a href="classes.php?district_id=<?php echo 0; ?>&district_id2=<?php echo $district_id2 ?>&lv3=<?php echo $lv3 ?>">所有</a></li>
        <?php foreach ($district as $_district_id => $_district_name): ?>
            <li class="<?php echo $_district_id==$district_id ? 'on' : '';?>"><a href="classes.php?district_id=<?php echo $_district_id ?>"><?php echo $_district_name ?></a></li>
        <?php endforeach ?>
        </ul>
    </div>
    <?php if (!empty($district2)): ?>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($district_id2)): ?>class="on"<?php endif ?>><a href="classes.php?district_id=<?php echo $district_id; ?>&district_id2=<?php echo 0; ?>&lv3=<?php echo $lv3 ?>">所有</a></li>
        <?php foreach ($district2 as $_district_id => $_district_name): ?>
            <li class="<?php echo $_district_id==$district_id2 ? 'on' : '';?>"><a href="classes.php?district_id=<?php echo $district_id ?>&district_id2=<?php echo $_district_id ?>"><?php echo $_district_name ?></a></li>
        <?php endforeach ?>
        </ul>
    </div>
    <?php endif ?>
    <div class="section district pd">
        <ul>
            <li <?php if (empty($lv3)): ?>class="on"<?php endif ?>><a href="classes.php?district_id=<?php echo $district_id; ?>&district_id2=<?php echo $district_id2 ?>&lv3=<?php echo 0; ?>">所有</a></li>
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
            <li class="<?php echo $class['lv3']==$lv3 ? 'on' : '';?>"><a href="classes.php?district_id=<?php echo $district_id ?>&district_id2=<?php echo $district_id2 ?>&lv3=<?php echo $class['lv3'] ?>"><?php echo $class['lv3'] ?></a></li>
        <?php endforeach ?>
        </ul>
    </div>
    <div class="section user box pd">
        <div class="form-label-input">
            <label class="form-label" style="width:45px;font-weight:bold;">用戶</label>
            <div class="form-input"><input type="text" class="form-text" user-suggest></div>
        </div>
        <div class="user_show">
            <?php
            if ($get_user_id) {
                $_u = get_user_one($get_user_id);
                if (!empty($_u)) {
                    echo '<a target="_blank" href="student.php?user_id='.$_u['ID'].'">';
                    echo form_val('billing_first_name', $_u).' | '.form_val('billing_last_name', $_u).' | '.form_val('billing_gender', $_u).' | '.form_val('billing_email', $_u).' | '.form_val('billing_phone', $_u).'<i class="fa fa-close"></i>';
                    echo '</a>';
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
    <div class="section pd ul_month">
        <div><a href="?m=<?php echo date('Y-m'); ?>">點擊進入<b>本月</b>班級</a></div>
        <ul>
            <?php
                $prev_next = get_prev_next_month(get('m', date('Y-m')));
            ?>
            <?php foreach ($prev_next as $key => $value): ?>
            <li><a href="?district_id=<?php echo get('district_id') ?>&district_id2=<?php echo get('district_id2') ?>&lv3=<?php echo get('lv3') ?>&m=<?php echo $value; ?>"<?php echo $key == 3 ? 'on' : ''; ?>><?php echo $value; ?></a></li>
            <?php endforeach;?>
        </ul>
    </div>
    <div class="filter form condition page_classes_list">
        <div class="">
            <ul>
            <?php foreach ($classes as $key => $class): ?>
                <?php
                    $teacher = edu_get_user($class['teacher']);
                    $teacher_txt = '';
                    foreach ($teacher as $key => $value) {
                        $value['billing_first_name'] = empty($value['billing_first_name']) ? $value['first_name'] : $value['billing_first_name'];
                        $value['billing_last_name'] = empty($value['billing_last_name']) ? $value['last_name'] : $value['billing_last_name'];
                        $teacher_txt .= '<a teacher href="teacher.php?user_id='.$value['ID'].'&month='.get('m', date('Y-m')).'">'.$value['billing_last_name'].$value['billing_first_name'].'</a>';
                    }

                    $student = edu_get_user($class['student']);
                    $student_txt = '';
                    foreach ($student as $key => $value) {
                        $value['billing_first_name'] = empty($value['billing_first_name']) ? $value['first_name'] : $value['billing_first_name'];
                        $value['billing_last_name'] = empty($value['billing_last_name']) ? $value['last_name'] : $value['billing_last_name'];
                        $student_txt .= '<a href="student.php?user_id='.$value['ID'].'">'.$value['billing_last_name'].$value['billing_first_name'].'</a>';
                    }

                    $student_transfer = edu_get_user($class['student_transfer']);
                    $student_transfer_txt = '';
                    foreach ($student_transfer as $key => $value) {
                        $student_transfer_txt .= '<a href="student.php?user_id='.$value['ID'].'">'.$value['billing_last_name'].$value['billing_first_name'].'</a>';
                    }

                ?>
                <li class="class <?php echo $class['style'] ?> <?php echo $class['style2'] ?>">
                    <h3><a href="class.php?class=<?php echo $class['class_id'] ?>"><?php echo $class['class_name'] ?></a> <span><a class="edit" href="attendance.php?class_id=<?php echo $class['class_id'] ?>">點名管理 <i class="fa fa-edit"></i></a></span></h3>
                    <ul>
                        <li>老師姓名: <?php echo $teacher_txt ?></li>
                        <li>學生姓名: <?php echo $student_txt ?></li>
                        <li>插班補堂: <?php echo $student_transfer_txt ?></li>
                        <li id="analytisc_days">上堂日期: <?php echo empty($class['class_every_day']) ? '暫無' : $class['class_every_day']; ?></li>
                    </ul>
                </li>
            <?php endforeach ?>
            </ul>
        </div>
    </div>
    <div class="section"><a href="class.php?class=0"><button class="form-button">新增班級</button></a></div>
</div>
</body>
</html>
<style>
.form, .msgbox{padding: 0 15px 15px;}
.district ul li.on{font-weight: bold;}
ul ul li{border-bottom: dashed 1px #eee;line-height:32px;}
ul li h3{padding: 10px;}
ul li h3 span{float: right;color: #fff;}
ul li h3 span i{margin-left: 2px;}
li.class{transition: all .3s;border-radius: 4px;background: #258872;overflow: hidden;margin-top: 15px;border: solid 2px #258872;}
li.class h3{color: #fff;}
li.class ul{background: #d1dddd;padding-left: 5px;}
li.class ul li{padding: 0 10px;padding-left:66px;text-indent:-66px;}
li.class ul li span{text-indent:0;}
.page_classes_list ul ul li a{background: rgba(37, 136, 114,0.2);display: inline-block;margin:0 3px;text-indent: 0;padding: 0 10px;border-radius: 8px;line-height: 1.5;font-size: 14px;transition: all .3s;color: #333;}
.page_classes_list ul ul li a:hover{background: rgba(37, 136, 114,0.8);color: #fff;}

.add_subject{overflow-y: hidden;position: relative;margin-top: 10px;}
.add_subject input{width: 100%;}
.add_subject button{position: absolute;right: 0;top: 0;bottom: 0;z-index: 1;padding: 0 20px;color: #fff;background: #333;cursor: pointer;border-radius: 0 6px 6px 0;}
.district {overflow: hidden;}
.district ul li{float: left;margin: 0 5px;}
.red{border: solid red 2px !important;}
.blue{border:solid blue 2px !important;}
.red.blue{border:solid purple 5px;}
.no_teacher_num{display: inline-block;width: 20px;height:20px;text-align:center;line-height:20px;background:red;color:#fff;border-radius:50%;font-size:12px;position: relative;top:-15px;}
.no_days_num{display: inline-block;width: 20px;height:20px;text-align:center;line-height:20px;background:blue;color:#fff;border-radius:50%;font-size:12px;position: relative;top:-15px;}
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
.user_show{background: yellow;border-radius: 4px;margin-top: 5px;padding: 6px 8px;}
.user_show i.fa{cursor: pointer;float: right;color:red;}
#analytisc_days span{margin-right: 10px;display: inline-block;}
#analytisc_days span[red]{font-weight: bold;color: red;}

.ul_month ul{text-align: center;}
.ul_month ul li{display: inline-block;margin: 0 5px;}
.ul_month ul li a{display: block;padding: 8px 5px;color: #888;}
.ul_month ul li a[on]{color: #258872;font-weight: bold;}
.ul_month div{text-align: center;}
.ul_month div b{color: red;font-size: 18px;}
</style>
<script>
$(function(){
function updateCurrentUrl(param, value) {
    const currentUrl = window.location.href;
    const urlObj = new URL(currentUrl);
    const params = urlObj.searchParams;
    params.set(param, value);
    return urlObj.toString();
}

$(".ul_month li").click(function(){
    var url = $(this).text();
   window.location.href = updateCurrentUrl('m', url)
});
    $("body").on('click', 'a[teacher]', function(event) {
        var url = $(this).attr('href');
        event.preventDefault();
        layer.open({type:2, content: url, area:['350px','400px'],'title':'班級'});
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
        $user_id = $(this).find("span.id").text();
        window.location.href="?user_id="+$user_id;
    });
    $("body").on('click', '.close', function(event) {
        $(this).parents('.suggest_wrap').hide();
    });
    $(".user_show i.fa").click(function(){
        window.location.href="classes.php";
    });
});
</script>