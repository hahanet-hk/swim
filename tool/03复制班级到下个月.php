<?php
define('DEBUG', 1);

include_once __DIR__.'/../core/init.php';
$classes        = db_find('edu_class');
$edu_class_user = db_find('edu_class_user');

$month_now  = date('n');
$month_prev = $month_now == 1 ? 12 : ($month_now - 1);
$month_next = ($month_now % 12) + 1;

$year_now  = date('Y');
$year_prev = date('Y') - 1;
$year_next = date('Y') + 1;

foreach ($classes as $key => $value)
{
    $class_id = $value['class_id'];
    // 判断是否有 两个月
    $list = arrlist_search($edu_class_user, array('class_id' => $class_id), array('sort' => 1));
    $_    = false;
    if ($list)
    {
        foreach ($list as $k2 => $v2)
        {
            if (strpos($v2['month'], '-', 0) !== false)
            {
                $_ = true;
            }
        }
    }
    if ($_)
    {
        // 查找最新一个月的数据
        $class_user = db_find_one('edu_class_user', array('class_id' => $class_id, array('%>month' => $month_now.'月-', '|%<month' => '-'.$month_now.'月')), array('sort' => -1));
        if (empty($class_user))
        {
            continue;
        }
        // 如果下個月存在則跳過
        $class_user_next = db_find_one('edu_class_user', array('class_id' => $class_id, array('%>month' => $month_next.'月-', '|%<month' => '-'.$month_next.'月')), array('sort' => -1));
        if ($class_user_next)
        {
            continue;
        }

        $month  = trim($class_user['month']);
        $month  = str_replace('月', '', $month);
        $month  = explode('-', $month);
        $month0 = $month[0];
        $month1 = $month[1];

        $month_new0 = get_next_month($month0);
        $month_new1 = get_next_month($month1);

        if ($month_new0 < $month0)
        {
            $year_insert = $year_now.'-'.$year_next;
        }
        else
        {
            $year_insert = $year_now;
        }
        $month_insert = $month_new0.'月-'.$month_new1.'月';
        $sort_insert  = year_month_sort($year_insert, $month_insert);
    }
    else
    {
        // 查找当前月是否存在, 如果存在则处理, 不存在则跳过
        $class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $month_now.'月', '%class_year' => $year_now));
        if (empty($class_user))
        {
            continue;
        }

        $month        = str_replace('月', '', $class_user['month']);
        $month_insert = get_next_month($month);
        $year_insert  = $month_insert < $month ? $year_next : $year_now;
        $month_insert = $month_insert.'月';
        $sort_insert  = year_month_sort($year_insert, $month_insert);
    }
    // 獲取要插入的數據
    $data               = $class_user;
    $data['class_year'] = $year_insert;
    $data['month']      = $month_insert;
    $data['sort']       = $sort_insert;
    $data['class_exam'] = $class_user['class_exam'];
    unset($data['id']);
    unset($data['order_id']);

    $where               = array();
    $where['class_id']   = $class_id;
    $where['month']      = $month_insert;
    $where['class_year'] = $year_insert;
    // 檢查是否存在
    $exists = db_find_one('edu_class_user', $where);
    if (!$exists)
    {
        db_insert('edu_class_user', $data);
    }
    else
    {
        // 學生
        $new_students  = decode_json($class_user['student']);
        $old_students  = decode_json($exists['student']);
        $last_students = array_merge($new_students, $old_students);
        $last_students = array_unique($last_students);
        $last_students = encode_json($last_students);
        // 插班
        $new_students_transfer  = decode_json($class_user['student_transfer']);
        $old_students_transfer  = decode_json($exists['student_transfer']);
        $last_students_transfer = array_merge($new_students_transfer, $old_students_transfer);
        $last_students_transfer = array_unique($last_students_transfer);
        $last_students_transfer = encode_json($last_students_transfer);

        db_update('edu_class_user', array('student' => $last_students, 'student_transfer' => $last_students_transfer, 'teacher'=>$class_user['teacher'], 'class_exam'=>$class_user['class_exam']), array('id' => $exists['id']));
    }
}
echo 'end';
