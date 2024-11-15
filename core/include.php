<?php
date_default_timezone_set('PRC');
$config_text = file_get_contents(__DIR__.'/../../wp-config.php');
preg_match_all('/define\s*\(\s*\'([A-Z_]+)\'\s*,\s*\'((?:[^\']|\'\')+)\'\s*\)\s*;/', $config_text, $matches);
$constants = array_combine($matches[1], $matches[2]);
foreach ($constants as $key => $value)
{
    defined($key) or define($key, $value);
}
preg_match('/\$table_prefix.*?=.*?\'(?<prefix>.*?)\';/', $config_text, $matches);
$table_prefix = $matches['prefix'];
include __DIR__.'/db.php';
function edu_get_user($id)
{
    if (empty($id))
    {
        return array();
    }

    if (!is_array($id))
    {
        $id = array($id);
    }
    $id = array_unique($id);

    $list = db_find('users', array('@usermeta.user_id' => 'users.ID', 'users.ID' => $id));
    $rt   = array();
    foreach ($list as $key => $value)
    {
        if (!isset($rt[$value['ID']]))
        {
            $rt[$value['ID']] = $value;
        }
        $rt[$value['ID']][$value['meta_key']] = $value['meta_value'];
    }
    foreach ($rt as $key => $value)
    {
        $first_name                  = empty($value['billing_first_name']) ? $value['first_name'] : $value['billing_first_name'];
        $last_name                   = empty($value['billing_last_name']) ? $value['last_name'] : $value['billing_last_name'];
        $value['billing_first_name'] = $first_name;
        $value['first_name']         = $first_name;
        $value['billing_last_name']  = $last_name;
        $value['billing_last_name']  = $last_name;
        $rt[$key]                    = $value;
    }
    return $rt;
}

function arrlist_sort($array, $condition = array())
{
    usort($array, function ($a, $b) use ($condition)
    {
        foreach ($condition as $field => $order)
        {
            if (!isset($a[$field]) || !isset($b[$field]))
            {
                return isset($a[$field]) ? 1 : -1;
            }
            $comparison = (is_numeric($a[$field]) && is_numeric($b[$field]))
            ? $a[$field] - $b[$field]
            : strcmp((string) $a[$field], (string) $b[$field]);
            if ($comparison !== 0)
            {
                return $order === 1 ? $comparison : -$comparison;
            }
        }
        return 0;
    });
    return $array;
}

function get_user_one($id)
{
    $user = edu_get_user($id);
    return empty($user) ? array() : reset($user);
}

function get_user_by_name($username)
{
    if (empty($username))
    {
        return array();
    }
    $list = db_find('users', array('@usermeta.user_id' => 'users.ID', 'users.user_login' => $username));
    $rt   = array();
    foreach ($list as $key => $value)
    {
        if (!isset($rt[$value['ID']]))
        {
            $rt[$value['ID']] = $value;
        }
        $rt[$value['ID']][$value['meta_key']] = $value['meta_value'];
    }
    return empty($rt) ? array() : reset($rt);
}

function days_sort($days = array())
{
    if (empty($days))
    {
        return array();
    }
    foreach ($days as $key => $value)
    {
        $days[$key] = trim($value);
    }
    $days = array_unique($days);
    foreach ($days as $key => &$value)
    {
        if (!empty($value))
        {
            $value = strtotime($value);
        }
        else
        {
            unset($days[$key]);
        }
    }
    sort($days);
    foreach ($days as $key => &$value)
    {
        $value = date('Y-m-d', $value);
    }
    return $days;
}

function classes_sort($classes)
{
    if (empty($classes))
    {
        return array();
    }
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
    $today    = date('w');
    $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
    $weekdays = array_merge(
        array_slice($weekdays, $today),
        array_slice($weekdays, 0, $today)
    );
    $times = array();
    foreach ($classes as $key => $value)
    {
        $time = get_time_24h($value['class_name']);
        $pos  = strpos($time, '-');
        if ($pos)
        {
            $time = substr($time, 0, $pos);
        }
        $time                  = str_replace(':', '', $time);
        $times[]               = $time;
        $classes[$key]['time'] = $time;
    }
    $times = array_unique($times);
    sort($times);
    $now_time   = date('Hi');
    $time_index = 0;
    foreach ($times as $key => $value)
    {
        if ($now_time >= $value)
        {
            $time_index = $key;
        }
    }
    $times = array_merge(array_slice($times, $time_index), array_slice($times, 0, $time_index));

    foreach ($classes as $key => $value)
    {
        $_class_name = $value['class_name'];
        $_pos        = strpos($_class_name, '星期');
        $_class_name = substr($_class_name, $_pos);
        $_class_week = mb_substr($_class_name, 2, 1);
        // 如果包含當前日期則排序0, 反之, 則按照規則排序
        if (strpos($_class_name, $week, 0) !== false)
        {
            $value['sort1'] = 0;
        }
        else
        {
            $value['sort1'] = array_search($_class_week, $weekdays);
        }
        $value['sort2'] = array_search($value['time'], $times);

        if (!is_array($value['student']))
        {
            $value['student'] = decode_json($value['student']);
        }
        if (!is_array($value['student_transfer']))
        {
            $value['student_transfer'] = decode_json($value['student_transfer']);
        }

        if (empty($value['student']) && empty($value['student_transfer']))
        {
            $value['sort1'] = 10;
        }

        $classes[$key] = $value;
    }

    $classes = arrlist_sort($classes, array('sort1' => 1, 'sort2' => 1));

    return $classes;
}

function attend_text($str)
{
    switch ($str)
    {
        case 'late':
            echo '請假';
            break;
        case 'absent':
            echo '取消';
            break;
        case 'clear':
            echo '清除';
            break;
        default:
            echo '出席';
            break;
    }
}

function get_days($input_week, $input_month, $input_year = '')
{
    $name         = $input_week.$input_month;
    $days         = get_array_class_name_days($name, $input_year);
    $_date_output = '';
    foreach ($days as $key => $value)
    {
        $_date_output .= $value.', ';
    }
    $_date_output = substr($_date_output, 0, -2);
    return $_date_output;
}

function get_user_by_kw($kw)
{
    $cond = array();
    if ($kw)
    {
        $cond['@usermeta.user_id']  = 'users.ID';
        $cond['|%users.user_email'] = $kw;
        $cond[]                     = array('|usermeta.meta_key' => 'first_name', '%usermeta.meta_value' => $kw);
        $cond[]                     = array('|usermeta.meta_key' => 'last_name', '%usermeta.meta_value' => $kw);
        $cond[]                     = array('|usermeta.meta_key' => 'telephone', '%usermeta.meta_value' => $kw);
        $cond[]                     = array('|usermeta.meta_key' => 'billing_phone', '%usermeta.meta_value' => $kw);
        $cond[]                     = array('|usermeta.meta_key' => 'billing_first_name', '%usermeta.meta_value' => $kw);
        $cond[]                     = array('|usermeta.meta_key' => 'billing_last_name', '%usermeta.meta_value' => $kw);
        $cond[]                     = array('|usermeta.meta_key' => 'nickname', '%usermeta.meta_value' => $kw);
    }
    $ids = db_find('users', $cond, array(), array(300), array('@users.ID'));
    $rt  = array();
    if ($ids)
    {
        foreach ($ids as $key => $value)
        {
            unset($ids[$key]);
            $ids[] = $value['ID'];
        }
        $ids = array_values($ids);
        $rt  = edu_get_user($ids);
    }
    return $rt;
}

function is_current_month($month, $now_month = '')
{
    if (empty($now_month))
    {
        $now_month = time();
    }
    if (!is_numeric($now_month))
    {
        $now_month = strtotime($now_month);
    }
    if (strlen($now_month) > 2)
    {
        $now_month = date('n', $now_month);
    }

    $month = str_replace('月', '', $month);
    $month = explode('-', $month);
    if (in_array($now_month, $month))
    {
        return true;
    }
    else
    {
        return false;
    }
}

function is_current_year($year = '', $now_year = '')
{
    if (empty($year))
    {
        return true;
    }
    if (empty($now_year))
    {
        $now_year = date('Y');
    }
    return $year == $now_year;
}

function calculate_age($birthday, $ymd = null)
{
    if (empty($birthday))
    {
        return 0;
    }
    if (!$ymd)
    {
        $ymd = date('Y-m-d');
    }
    $birthday_date = new DateTime($birthday);
    $ymd_date      = new DateTime($ymd);
    $age           = $ymd_date->format('Y') - $birthday_date->format('Y');
    // 校正年龄，如果指定日期还没有到达出生年月日
    if ($ymd_date->format('md') < $birthday_date->format('md'))
    {
        $age--;
    }
    return $age;
}

function calculate_age_month($birthday, $ymd = null)
{
    if (empty($birthday))
    {
        return 0;
    }
    if (!$ymd)
    {
        $ymd = date('Y-m-d');
    }
    $birthday_date = new DateTime($birthday);
    $ymd_date      = new DateTime($ymd);
    // 计算两个日期之间的月份差异
    $months = $ymd_date->diff($birthday_date)->y * 12;
    $months += $ymd_date->diff($birthday_date)->m;
    // 校正月份，如果指定日期的日还没有到达出生月份的日
    if ($ymd_date->diff($birthday_date)->d < $birthday_date->format('d'))
    {
        $months--;
    }
    return $months;
}

function get_class_every_day_attend($class_user)
{
    $teacher          = decode_json($class_user['teacher']);
    $student          = decode_json($class_user['student']);
    $student_transfer = decode_json($class_user['student_transfer']);
    $student_all      = array_merge($student, $student_transfer);
    $class_id         = $class_user['class_id'];
    $month            = $class_user['month'];
    $class_year       = $class_user['class_year'];
    $analytisc_days   = array();
    $analytisc_days   = get_class_user_days_array($class_user);
    foreach ($analytisc_days as $key => $value)
    {
        unset($analytisc_days[$key]);
        $analytisc_days[$value] = 0;
    }
    if ($student_all)
    {
        foreach ($student_all as $key => $user_id)
        {
            $days = get_user_attend($user_id, $class_id, $month, $class_year);
            foreach ($days as $day)
            {
                if (!isset($analytisc_days[$day]))
                {
                    $analytisc_days[$day] = 1;
                }
                else
                {
                    $analytisc_days[$day]++;
                }
            }
        }
    }

    foreach ($analytisc_days as $key => $value)
    {
        unset($analytisc_days[$key]);
        $date             = $key;
        $count            = empty($value) ? 0 : $value;
        $analytisc_days[] = array('date' => $date, 'count' => $count, 'sort' => strtotime($date));
    }
    $analytisc_days     = arrlist_sort($analytisc_days, array('sort' => 1));
    $analytisc_days_out = '';
    $class_name         = get_edu_class($class_id, 'class_name');
    if (strpos($class_name, '幼兒') !== false)
    {
        $max = 4;
    }
    else
    {
        $max = 6;
    }
    foreach ($analytisc_days as $key => $value)
    {
        $date  = $value['date'];
        $count = $value['count'];
        $style = '';
        if ($count > $max)
        {
            $style = 'red';
        }
        $analytisc_days_out .= "<span {$style}>{$date}({$count})</span>";
    }
    return $analytisc_days_out;
}

function get_class_every_day2($class_user)
{
    $teacher          = decode_json($class_user['teacher']);
    $student          = decode_json($class_user['student']);
    $student_transfer = decode_json($class_user['student_transfer']);
    $month            = $class_user['month'];
    $class_year       = $class_user['class_year'];

    $class_id = $class_user['class_id'];
    $class    = db_find_one('edu_class', array('class_id' => $class_user['class_id']));

    // 獲取所有人的出勤日期
    $_date_output   = get_class_user_days_array($class_user);
    $analytisc_days = $_date_output;
    $_date_output   = implode(',', $_date_output);
    foreach ($analytisc_days as $key => $value)
    {
        unset($analytisc_days[$key]);
        $value                  = trim($value);
        $analytisc_days[$value] = array();
    }
    // 把出席的每一個學生ID添加到班級, 統計一下日期中學生ID的數量即每日班級人數
    $user_days_all = db_find('edu_class_user_days', array('class_id' => $class_id, 'month' => $month));
    $user_days_all = arrlist_change_key($user_days_all, 'user_id');
    if (!is_array($student))
    {
        return ' ';
    }
    if (!is_array($student_transfer))
    {
        return ' ';
    }
    foreach ($student as $key => $_uid)
    {
        $days = '';
        if (isset($user_days_all[$_uid]))
        {
            $days = $user_days_all[$_uid]['days'];
        }
        $days = empty($days) ? $_date_output : $days;
        $days = explode(',', $days);
        foreach ($days as $day)
        {
            $day = trim($day);
            if ($day && isset($analytisc_days[$day]))
            {
                $analytisc_days[$day][] = $_uid;
            }
        }
    }
    foreach ($student_transfer as $key => $_uid)
    {
        $days = '';
        if (isset($user_days_all[$_uid]))
        {
            $days = $user_days_all[$_uid]['days'];
        }
        $days = empty($days) ? '' : $days;
        $days = explode(',', $days);
        foreach ($days as $day)
        {
            $day = trim($day);
            if ($day && isset($analytisc_days[$day]))
            {
                $analytisc_days[$day][] = $_uid;
            }
        }
    }
    foreach ($analytisc_days as $key => $value)
    {
        $analytisc_days[$key] = array_unique($analytisc_days[$key]);
    }
    // 排除請假/缺席/刪除日期, 如果有學生, 則查詢實際出勤情況
    $user_id = array_merge($student, $student_transfer);
    if (!empty($user_id))
    {
        $db_where         = array();
        $db_where['date'] = array();
        foreach ($analytisc_days as $key => $value)
        {
            $db_where['date'][] = $key;
        }
        $db_where['user_id'] = array_unique($user_id);
        $db_attend           = db_find('edu_attendance', $db_where);
        if (empty($db_attend))
        {
            $db_attend = array();
        }
        foreach ($db_attend as $key => $value)
        {
            if ($value['attendance'] != 'present')
            {
                foreach ($analytisc_days[$value['date']] as $k2 => $v2)
                {
                    if ($value['user_id'] == $v2)
                    {
                        unset($analytisc_days[$value['date']][$k2]);
                    }
                }
            }
            else
            {
                $analytisc_days[$value['date']][] = $value['user_id'];
            }
        }
    }
    // 排除請假/缺席/刪除日期結束
    // 統計每日學生人數
    foreach ($analytisc_days as $key => $value)
    {
        $analytisc_days[$key] = count(array_unique($analytisc_days[$key]));
    }
    $analytisc_days_out = '';
    $class_name         = $class['class_name'];
    if (strpos($class_name, '幼兒') !== false)
    {
        $max = 4;
    }
    else
    {
        $max = 6;
    }
    foreach ($analytisc_days as $key => $value)
    {
        $style = '';
        if ($value > $max)
        {
            $style = 'red';
        }
        $analytisc_days_out .= "<span {$style}>{$key}({$value})</span>";
    }
    return $analytisc_days_out;
}

// 合并订单用户和历史用户
function get_class_order($class, $month)
{
    $project = $class['product_name'].' - '.$month.', '.$class['date_time'];
    $student = array();
    $orders  = db_find('woocommerce_order_items', array('@postmeta.post_id' => 'woocommerce_order_items.order_id', 'woocommerce_order_items.order_item_name' => $project));
    if (!empty($orders))
    {
        foreach ($orders as $key => $value)
        {
            unset($orders[$key]);
            $orders[$value['order_item_id']][$value['meta_key']] = $value['meta_value'];
        }
        foreach ($orders as $key => $value)
        {
            if (!empty($value['_date_completed']) && strtotime('-3 months') <= $value['_date_completed'])
            {
                $student[] = $value['_customer_user'];
            }
        }
    }
    $student = array_unique($student);
    return $student;
}

function arrlist_search_one($arrlist, $where = array(), $sort = array())
{
    $arr = arrlist_search($arrlist, $where, $sort);
    if (empty($arr))
    {
        return array();
    }
    return reset($arr);
}

function get_class_exam($class_name)
{
    if (strpos($class_name, '幼兒游泳班') !== false)
    {
        $pid = 67;
    }
    elseif (strpos($class_name, '兒童游泳班') !== false)
    {
        $pid = 74;
    }
    elseif (strpos($class_name, '成人游泳班') !== false)
    {
        $pid = 81;
    }
    return array($pid);
}

function year_month_sort($year, $month)
{
    $month  = str_replace('月', '', $month);
    $_m     = explode('-', $month);
    $month0 = isset($_m[0]) ? $_m[0] : '00';
    $month1 = isset($_m[1]) ? $_m[1] : '00';
    $month0 = strlen($month0) == 1 ? '0'.$month0 : $month0;
    $month1 = strlen($month1) == 1 ? '0'.$month1 : $month1;
    $sort   = intval($year.$month0.$month1);
    return $sort;
}

function get_prev_month($month)
{
    $month_prev = $month == 1 ? 12 : ($month - 1);
    return $month_prev;
}

function get_next_month($month)
{
    $month_next = ($month % 12) + 1;
    return $month_next;
}

function get_next_year_month($class_year, $month)
{
    $rt          = array();
    $next_month  = get_string_next_month($month);
    $rt['month'] = $next_month;
    $next_month  = get_array_month($next_month);
    $month1      = isset($next_month[0]) ? $next_month[0] : 0;
    $month2      = isset($next_month[1]) ? $next_month[1] : 0;
    if ($month1 == 1 || $month2 == 1)
    {
        $class_year = $class_year + 1;
    }
    $rt['class_year'] = $class_year;
    return $rt;
}

function get_prev_next_month($ym = '')
{
    if (empty($ym))
    {
        $ym_timestamp = time();
    }
    else
    {
        $ym           = $ym.'-01';
        $ym_timestamp = strtotime($ym);
    }
    $rt = array();
    for ($i = -3; $i < 4; $i++)
    {
        $rt[] = date('Y-m', strtotime($i.' month', $ym_timestamp));
    }
    return $rt;
}

function get_string_next_month($input)
{
    $month_mapping = [
        '1月'  => 1,
        '2月'  => 2,
        '3月'  => 3,
        '4月'  => 4,
        '5月'  => 5,
        '6月'  => 6,
        '7月'  => 7,
        '8月'  => 8,
        '9月'  => 9,
        '10月' => 10,
        '11月' => 11,
        '12月' => 12,
    ];
    $reverse_mapping = array_flip($month_mapping);
    $months          = preg_split('/[-、]/', $input);
    $next_months     = [];
    foreach ($months as $month)
    {
        if (isset($month_mapping[$month]))
        {
            $current_month = $month_mapping[$month];
            $next_month    = ($current_month % 12) + 1;
            $next_months[] = $next_month;
        }
    }
    if (count($next_months) > 1)
    {
        // 兩個月的需要再加一次月份, 單月不用
        $startMonth = ($next_months[0] % 12) + 1;
        $endMonth   = ($next_months[1] % 12) + 1;
        return $reverse_mapping[$startMonth].'-'.$reverse_mapping[$endMonth];
    }
    else
    {
        return $reverse_mapping[$next_months[0]];
    }
}

function get_string_prev_month($input)
{
    $month_mapping = [
        '1月'  => 1,
        '2月'  => 2,
        '3月'  => 3,
        '4月'  => 4,
        '5月'  => 5,
        '6月'  => 6,
        '7月'  => 7,
        '8月'  => 8,
        '9月'  => 9,
        '10月' => 10,
        '11月' => 11,
        '12月' => 12,
    ];
    $reverse_mapping = array_flip($month_mapping);
    $months          = preg_split('/[-、]/', $input);
    $prev_months     = [];
    foreach ($months as $month)
    {
        if (isset($month_mapping[$month]))
        {
            $prev_months[] = get_prev_month($month_mapping[$month]);
        }
    }
    if (count($prev_months) > 1)
    {
        // 兩個月的需要再加一次月份, 單月不用
        $startMonth = get_prev_month($prev_months[0]);
        $endMonth   = get_prev_month($prev_months[1]);
        return $reverse_mapping[$startMonth].'-'.$reverse_mapping[$endMonth];
    }
    else
    {
        return $reverse_mapping[$prev_months[0]];
    }
}

function in_class_user($student_id, $class_user)
{
    $student          = decode_json($class_user['student']);
    $student_transfer = decode_json($class_user['student_transfer']);
    if (in_array($student_id, $student))
    {
        return 'student';
    }
    elseif (in_array($student_id, $student_transfer))
    {
        return 'student_transfer';
    }
    else
    {
        return false;
    }
}

function meger_class_user($student_id, $class_user_old, $class_user_new)
{
    $role = in_class_user($student_id, $class_user_old);
    if ($role)
    {
        $arr                   = decode_json($class_user_new[$role]);
        $arr                   = array_merge(array((String) $student_id), $arr);
        $arr                   = array_unique($arr);
        $arr                   = encode_json($arr);
        $class_user_new[$role] = $arr;
    }
    return $class_user_new;
}

function log_request($log_path = '')
{
    if (empty($log_path))
    {
        $log_path = __DIR__.'/#logs/';
    }
    if (!is_dir($log_path) && !mkdir($log_path, 0755, true))
    {
        trigger_error('Unable to create log directory '.$log_path);
    }
    $request_time   = date('Y-m-d H:i:s');
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    $request_url    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $client_ip      = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $user_agent     = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $request_server = json_encode($_SERVER, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $request_body   = '';
    if (!empty($_SERVER['SHELL']) || empty($_SERVER['REMOTE_ADDR']))
    {
        $request_body = json_encode($_SERVER['argv'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    else
    {
        $request_body = file_get_contents('php://input');
    }
    $request_post = isset($_POST) ? json_encode($_POST, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
    $log_message  = "######################### {$request_time} #########################\n";
    $log_message .= "# Time: {$request_time}\n";
    $log_message .= "# Method: {$request_method}\n";
    $log_message .= "# URL: {$request_url}\n";
    $log_message .= "# IP: {$client_ip}\n";
    // $log_message .= "# UserAgent: {$user_agent}\n";
    // $log_message .= "# Server: {$request_server}\n";
    // $log_message .= "# Body: {$request_body}\n";
    $log_message .= "# Post: {$request_post}\n";
    $file_name = 'request_'.date('Ymd').'.log';
    file_put_contents($log_path.$file_name, $log_message, FILE_APPEND);
}

$request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
$request_url    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
if (strpos($request_url, 'edu2') !== false)
{
    if ($request_method == 'POST' || $request_method == 'GET')
    {
        log_request();
    }
}
function time_range($timeRange)
{
    list($startTime, $endTime) = explode('-', $timeRange);
    $start                     = new DateTime($startTime);
    $end                       = new DateTime($endTime);
    $now                       = new DateTime();
    if ($end <= new DateTime('12:00pm'))
    {
        return 'morning';
    }
    elseif ($start >= new DateTime('1:00pm') && $end <= new DateTime('6:00pm'))
    {
        return 'afternoon';
    }
    elseif ($start >= new DateTime('6:00pm'))
    {
        return 'evening';
    }
    else
    {
        return '';
    }
}

// $date = 2024-05
function calc_salary($user_id, $date, $hour_fee = 200)
{
    // 工作日入场费, 周末入场费, 时薪
    $workday_fee = 17;
    $weekend_fee = 19;
    // $hour_fee    = 200;
    // 开始计算
    $timestamp  = strtotime($date);
    $date_ym    = date('Y-m', $timestamp);
    $month_now  = date('n', $timestamp);
    $class_id   = array();
    $classes    = db_find('edu_class');
    $classes    = arrlist_change_key($classes, 'class_id');
    $class_user = db_find('edu_class_user', array('%teacher' => '"'.$user_id.'"', '%month' => $month_now.'月'));
    foreach ($class_user as $key => $value)
    {
        $v['class_id']    = $value['class_id'];
        $v['month']       = $value['month'];
        $v['days']        = $value['days'];
        $v['class_name']  = $classes[$value['class_id']]['class_name'];
        $v['date_time']   = $classes[$value['class_id']]['date_time'];
        $class_user[$key] = $v;
    }
    $attend_days = array();
    $attend_fee  = 0;
    $class_num   = 0;
    foreach ($class_user as $key => $value)
    {
        $days = empty($value['days']) ? get_days($value['date_time'], $value['month']) : $value['days'];
        $days = explode(',', $days);
        $d    = array();
        foreach ($days as $k2 => $v2)
        {
            $k     = trim($v2);
            $d[$k] = '';
        }
        $days    = $d;
        $attends = db_find('edu_attendance', array('class_id' => $value['class_id'], 'month' => $value['month'], 'user_id' => $user_id));
        foreach ($attends as $k2 => $v2)
        {
            if ($v2['attendance'] == 'present')
            {
                $days[$v2['date']] = '';
            }
            else
            {
                unset($days[$v2['date']]);
            }
        }
        $class_user[$key]['days'] = $days;
        $pos                      = strpos($value['date_time'], '|');
        $time                     = substr($value['date_time'], $pos + 1);
        $time_range               = time_range($time);
        // 判断是否当月日期

        foreach ($days as $k2 => $v2)
        {
            // 如果当月, 则计算

            if (date('Y-m', strtotime($k2)) == $date_ym)
            {
                $class_num++;
                $attend_days[$k2][] = $time_range;
            }
            else
            {
                unset($days[$k2]);
            }
        }

        $class_user[$key]['days2'] = $days;
    }

    foreach ($class_user as $key => $value)
    {
        $_week = get_array_week($value['class_name'].$value['month']);
        $_time = get_time_24h($value['class_name'].$value['month']);
        $_week = reset($_week);
        $_week = (int) $_week;

        $_time = str_replace(array('-', ':'), '', $_time);
        $_time = (int) $_time;

        $value['sort1']   = $_week;
        $value['sort2']   = $_time;
        $class_user[$key] = $value;
    }

    usort($class_user, function ($a, $b)
    {
        if ($a['sort1'] === $b['sort1'])
        {
            return $a['sort2'] <=> $b['sort2'];
        }
        return $a['sort1'] <=> $b['sort1'];
    });

    $workday_num = 0;
    $weekend_num = 0;

    foreach ($attend_days as $key => $value)
    {
        $value = array_unique($value);
        $num   = count($value);
        $week  = date('w', strtotime($key));
        // 星期一至五入場費$17 星期六、日入場費$19
        if ($week == 6 || $week == 0)
        {
            $weekend_num += $num;
            // $attend_fee += $weekend_fee * $num;
        }
        else
        {
            $workday_num += $num;
            // $attend_fee += $workday_fee * $num;
        }
    }

    $workday_fee = $workday_fee * $workday_num;
    $weekend_fee = $weekend_fee * $weekend_num;
    $attend_fee  = $workday_fee + $weekend_fee;
    // 计算课时工资 上课次数*时薪
    $class_fee             = $class_num * $hour_fee;
    $return                = array();
    $return['workday_num'] = $workday_num;
    $return['weekend_num'] = $weekend_num;
    $return['workday_fee'] = $workday_fee;
    $return['weekend_fee'] = $weekend_fee;
    $return['classes']     = $class_user;
    $return['attend_fee']  = $attend_fee;
    $return['class_fee']   = $class_fee;
    $return['class_num']   = $class_num;
    $return['amount']      = $attend_fee + $class_fee;
    return $return;
}

function get_time_24h($text)
{
    $pattern = '/(\d{1,2}:\d{2}(?:am|pm)-\d{1,2}:\d{2}(?:am|pm))/';
    preg_match($pattern, $text, $matches);
    if (empty($matches[0]))
    {
        return '';
    }
    $time              = $matches[0];
    list($start, $end) = explode('-', $time);
    $converted_start   = convert_time_to_24h($start);
    $converted_end     = convert_time_to_24h($end);
    $converted_times   = $converted_start.'-'.$converted_end;
    return $converted_times;
}

function convert_time_to_24h($time)
{
    $date_time = DateTime::createFromFormat('g:ia', $time);
    return $date_time->format('H:i');
}

function get_array_week($input)
{
    preg_match('/星期([一二三四五六天日、]+)/u', $input, $matches);
    $week         = $matches[1];
    $week         = explode('、', $week);
    $week_mapping = array(
        '一' => 1,
        '二' => 2,
        '三' => 3,
        '四' => 4,
        '五' => 5,
        '六' => 6,
        '日' => 7,
        '天' => 7,
    );
    $week_numbers = array();
    foreach ($week as $day)
    {
        if (isset($week_mapping[$day]))
        {
            $week_numbers[] = $week_mapping[$day];
        }
    }
    return array_unique($week_numbers);
}

function get_array_month($input)
{
    preg_match_all('/(\d{1,2})月/', $input, $matches);
    $rt = $matches[1];
    return array_unique($rt);
}

function get_days_in_month($month, $year)
{
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

// 获取当月的天
function get_array_days($months, $weeks, $year = '')
{
    if (empty($year))
    {
        $year = date('Y');
    }
    if (!is_array($months))
    {
        $months = array($months);
    }
    if (!is_array($weeks))
    {
        $weeks = array($weeks);
    }
    $dates = array();
    foreach ($months as $month)
    {
        $day_count = get_days_in_month($month, $year);
        for ($day = 1; $day <= $day_count; $day++)
        {
            $day_week = date('N', strtotime("$year-$month-$day"));
            if (in_array($day_week, $weeks))
            {
                $dates[] = date('Y-m-d', strtotime("$year-$month-$day"));
            }
        }
    }
    return $dates;
}

function get_array_class_name_days($class_name, $year = '')
{
    $week  = get_array_week($class_name);
    $month = get_array_month($class_name);
    $days  = get_array_days($month, $week, $year);
    return $days;
}

function get_edu_class($class_id = '', $field = '')
{
    static $classes;
    if (!isset($classes))
    {
        $classes = db_find('edu_class');
        $classes = arrlist_change_key($classes, 'class_id');
    }
    if ($field)
    {
        return isset($classes[$class_id][$field]) ? $classes[$class_id][$field] : '';
    }
    elseif ($class_id)
    {
        return isset($classes[$class_id]) ? $classes[$class_id] : array();
    }
    else
    {
        return $classes;
    }
}

function get_class_name_days_array($class_name, $month, $class_year = '')
{
    if (empty($class_year))
    {
        $class_year = date('Y');
    }
    $class_user = arrlist_search_one(get_edu_class(), array('class_name' => $class_name, 'month' => $month, 'class_year' => $class_year));
    if ($class_user)
    {
        $days = get_class_user_days_array($class_user);
    }
    else
    {
        $week  = get_array_week($class_name);
        $month = get_array_month($month);
        $days  = get_array_days($month, $week, $class_year);
    }

    return $days;
}

function get_class_user_days_array($class_user)
{
    $class_name = get_edu_class($class_user['class_id'], 'class_name');
    $week       = get_array_week($class_name);
    $month      = get_array_month($class_user['month']);
    if ($class_user['days'])
    {
        $days = explode(',', $class_user['days']);
    }
    else
    {
        $days = get_array_days($month, $week, $class_user['class_year']);
    }
    $days = exclude_other_month_days($days, $month);
    return $days;
}

function get_class_user_days_string($class_user)
{
    $days = get_class_user_days_array($class_user);

    $_date_output = '';
    foreach ($days as $key => $value)
    {
        $_date_output .= $value.', ';
    }
    $_date_output = substr($_date_output, 0, -2);
    return $_date_output;
}

function exclude_other_month_days($days, $month)
{
    if (!is_array($days))
    {
        $days = explode(',', $days);
    }
    if (!is_array($month))
    {
        $month = get_array_month($month);
    }

    foreach ($days as $key => $value)
    {
        $n = date('n', strtotime($value));
        if (!in_array($n, $month))
        {
            unset($days[$key]);
        }
    }
    $days = days_sort($days);
    $days = array_unique($days);
    return $days;
}

// 獲取用户預設上堂日期
function get_user_days($user_id, $class_id, $month, $year = '', $exclude_other_month_days = true)
{
    if (empty($year))
    {
        $year = date('Y');
    }
    $days = '';
    $user = db_find_one('edu_class_user_days', array('class_id' => $class_id, 'month' => $month, 'class_year' => $year, 'user_id' => $user_id));

    if (!empty($user) && !empty($user['days']) && $user['days'] != '[]')
    {
        $days = explode(',', $user['days']);
    }
    else
    {
        $class      = db_find_one('edu_class', array('class_id' => $class_id));
        $class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $month, 'class_year' => $year));
        if (empty($class))
        {
            return 'class_id not found!';
        }
        // 如果是插班生, 則返回空, 之前已經判斷過用戶days了
        if (isset($class_user['student_transfer']))
        {
            $student_transfer = decode_json($class_user['student_transfer']);
            if (in_array($user_id, $student_transfer))
            {
                return array();
            }
        }
        $days = get_class_user_days_array($class_user);
    }
    if ($exclude_other_month_days)
    {
        $days = exclude_other_month_days($days, $month);
    }
    $days = array_unique($days);
    return $days;
}

// 获取用户实际上堂日期(包含上堂请假等等)
function get_user_days_real($user_id, $class_id, $month, $year = '', $exclude_other_month_days = true)
{
    $days       = get_user_days($user_id, $class_id, $month, $year, $exclude_other_month_days = true);
    $attendance = db_find('edu_attendance', array('class_id' => $class_id, 'class_year' => $year, 'user_id' => $user_id));
    if ($attendance)
    {
        foreach ($attendance as $key => $value)
        {
            $days[] = $value['date'];
        }
    }
    if ($exclude_other_month_days)
    {
        $days = exclude_other_month_days($days, $month);
    }
    else
    {
        $days = days_sort($days);
    }
    return $days;
}

// 用户实际上堂日期(只计算上堂)
function get_user_attend($user_id, $class_id, $month, $class_year = '')
{
    $days    = get_user_days($user_id, $class_id, $month, $class_year);
    $attends = db_find('edu_attendance', array('class_id' => $class_id, 'month' => $month, 'class_year' => $class_year, 'user_id' => $user_id));
    if ($attends)
    {
        foreach ($attends as $key => $value)
        {
            if ($value['attendance'] == 'present')
            {
                $days[] = $value['date'];
                $days   = array_unique($days);
            }
            else
            {
                $days = array_diff($days, array($value['date']));
                $days = array_values($days);
            }
        }
    }
    // 排除其他月份的日期
    $days = exclude_other_month_days($days, $month);
    return $days;
}

// 用户实际上堂日期(只计算上堂) 根据月份计算(考虑插班情况, 班级ID会改变)
function get_user_attend2($user_id, $class_id, $month, $class_year = '')
{
    $days    = get_user_days($user_id, $class_id, $month, $class_year);
    $attends = db_find('edu_attendance', array('class_year' => $class_year, 'user_id' => $user_id));
    if ($attends)
    {
        foreach ($attends as $key => $value)
        {
            if ($value['attendance'] == 'present')
            {
                $days[] = $value['date'];
                $days   = array_unique($days);
            }
            else
            {
                $days = array_diff($days, array($value['date']));
                $days = array_values($days);
            }
        }
    }
    // 排除其他月份的日期
    $days = exclude_other_month_days($days, $month);
    return $days;
}

// 获取用户实际上堂状态(包含上堂请假等等)
function get_user_class_month_attend($user_id, $class_id, $month, $year)
{
    $days_plan = get_user_days($user_id, $class_id, $month, $year);
    $days_real = get_user_days_real($user_id, $class_id, $month, $year);
    $db_attend = db_find('edu_attendance', array('class_id' => $class_id, 'class_year' => $year, 'user_id' => $user_id));
    $db_attend = arrlist_change_key($db_attend, 'date');
    $attends   = array();
    foreach ($days_real as $key => $days)
    {
        $attends[$days] = empty($db_attend[$days]) ? 'present' : $db_attend[$days]['attendance'];
    }
    return $attends;
}

function attend_array_text($attends)
{
    $rt['present'] = '';
    $rt['late']    = '';
    $rt['absent']  = '';
    $rt['clear']   = '';
    $_attend       = array();
    foreach ($attends as $ymd => $attend)
    {
        $timestrape = strtotime($ymd);
        $m          = date('n', $timestrape);
        $d          = date('j', $timestrape);
        if (!isset($_attend[$attend]))
        {
            $_attend[$attend] = array();
        }
        if (isset($_attend[$attend][$m]))
        {
            $_attend[$attend][$m] .= $d.', ';
        }
        else
        {
            $_attend[$attend][$m] = $d.', ';
        }
    }
    foreach ($_attend as $attend => $ymd)
    {
        $a = '';
        foreach ($ymd as $m => $d)
        {
            $a .= $m.'月: '.substr($d, 0, -2).'&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        $rt[$attend] = $a;
    }
    return $rt;
}

function month_num_string($num)
{
    $num = str_replace(array('月', ' '), '', $num);
    $num = explode('-', $num);
    $rt  = '';
    foreach ($num as $key => $value)
    {
        $rt .= $value.'月-';
    }
    $rt = substr($rt, 0, '-1');
    return $rt;
}

function get_renew_price2($user_id, $class_name, $pa_month, $class_year = '', $amount = 0, $class_fee = 0)
{
    $class = db_find_one('edu_class', array('class_name' => $class_name));

    if (empty($class))
    {
        return array();
    }
    $class_id   = $class['class_id'];
    $class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $pa_month, 'class_year' => $class_year));
    // 獲取當前班級的学费
    $renew = 0;
    // 當前月份出勤天数
    $now_month            = $pa_month;
    $now_month_array      = get_array_month($now_month);
    $now_month_days       = get_class_user_days_array($class_user);
    $now_month_days_count = empty($now_month_days) ? 0 : count($now_month_days);

    // 费用计算2
    // 兒童游泳班 $140 幼兒游泳班 $160 女子成人游泳班 $150 成人游泳班 $145  泳隊訓練班 $180

    if (strpos($class_name, '兒童游泳班') !== false)
    {
        $class_fee = 140;
    }
    elseif (strpos($class_name, '幼兒游泳班') !== false)
    {
        $class_fee = 160;
    }
    elseif (strpos($class_name, '女子成人游泳班') !== false)
    {
        $class_fee = 150;
    }
    elseif (strpos($class_name, '改良班') !== false)
    {
        $class_fee = 150;
    }
    elseif (strpos($class_name, '成人游泳班') !== false)
    {
        $class_fee = 145;
    }
    elseif (strpos($class_name, '泳隊訓練') !== false)
    {
        $class_fee = 180;
    }
    // 费用计算2结束
    // 获取kv中每节课费用
    $k = 'user_class_fee_'.$user_id.'#'.$class['class_id'];
    $v = kv_get($k);
    if ($v)
    {
        $class_fee = $v;
    }

    if ($class_fee)
    {
        $price = $class_fee;
    }
    else
    {
        $price = $now_month_days_count ? number_format($amount / $now_month_days_count, 2) : 0;
    }

    // 本月设置上堂天数, 不排除管理员添加下月的天数 1.本月设置的上堂日期(包含下月), 2. 本月实际上堂日期
    $now_month_attend_days_plan = get_user_days($user_id, $class_id, $now_month, $class_year, false);
    // $now_month_attend_days_real = get_user_attend($user_id, $class_id, $now_month, $class_year);
    $now_month_attend_days_real = array();
    $class_users                = db_find('edu_class_user', array('month' => $now_month, 'class_year' => $class_year, array('%student' => '"'.$user_id.'"', '|%student_transfer' => '"'.$user_id.'"')));
    foreach ($class_users as $key => $_class_user)
    {
        $_days                      = get_user_attend($user_id, $_class_user['class_id'], $now_month, $class_year);
        $now_month_attend_days_real = array_merge($now_month_attend_days_real, $_days);
    }

    $now_month_attend_days_plan_count = count($now_month_attend_days_plan);
    $now_month_attend_days_real_count = count($now_month_attend_days_real);

    // 獲取未上課的日期以及數量
    $missed_lesson_count = $now_month_attend_days_plan_count - $now_month_attend_days_real_count;
    $missed_lesson_days  = array();
    $added_lesson_days   = array();
    $next_lesson_days    = array();

    // 獲取請假/取消的日期
    foreach ($now_month_attend_days_plan as $key => $value)
    {
        $n = date('n', strtotime($value));
        if (in_array($n, $now_month_array) && !in_array($value, $now_month_attend_days_real))
        {
            $missed_lesson_days[] = $value;
        }

        if (!in_array($n, $now_month_array))
        {
            $next_lesson_days[] = $value;
        }
    }
    // 獲额外出勤的日期
    foreach ($now_month_attend_days_real as $key => $value)
    {
        $n = date('n', strtotime($value));
        if (in_array($n, $now_month_array) && !in_array($value, $now_month_attend_days_plan))
        {
            $added_lesson_days[] = $value;
        }
    }

    // 下月要出勤天数
    $next_month      = get_string_next_month($now_month);
    $next_class_user = db_find_one('edu_class_user', array('class_id' => $class_id, 'month' => $next_month, 'class_year' => $class_year));
    if ($next_class_user)
    {
        $next_month_days = get_class_user_days_array($next_class_user);
    }
    else
    {
        $next_month_days = get_class_name_days_array($class_name, $next_month, $class_year);
    }
    $next_month_days_count = count($next_month_days);

    // 獲取下月收費
    $renew             = ($next_month_days_count - $missed_lesson_count) * $price;
    $renew             = number_format($renew, 2);
    $real_lesson_count = $next_month_days_count - $missed_lesson_count;

    $text = "<p>如想繼續報讀下一期游泳班，請交以下學費以安排學位。如不打算繼續上堂，請回覆不繼續上課，謝謝！</p><p>歡迎繼續參加 {$class_name} <b>{$next_month}</b> 游泳班</p>";
    $text .= "<p>上堂日期: ";
    $text .= date_to_month_days($next_month_days);
    // foreach ($next_month_days as $key => $value)
    // {
    //     $text .= date('m-d, ', strtotime($value));
    // }
    $text .= "(共 <b>{$next_month_days_count}</b> 堂)</p>";

    if ($missed_lesson_days || $added_lesson_days || $next_lesson_days)
    {
        $text .= '<p>請假補課: ';
        if (!empty($missed_lesson_days))
        {
            $text .= '請假/取消: ';
            $text .= date_to_month_days($missed_lesson_days);
            // foreach ($missed_lesson_days as $key => $value)
            // {
            //     $text .= date('m-d, ', strtotime($value));
            // }
        }

        if (!empty($added_lesson_days))
        {
            $text .= '補課: ';
            $text .= date_to_month_days($added_lesson_days);
            // foreach ($added_lesson_days as $key => $value)
            // {
            //     $text .= date('m-d, ', strtotime($value));
            // }
        }

        if (!empty($next_lesson_days))
        {
            $text .= '待上課: ';
            $text .= date_to_month_days($next_lesson_days);
            // foreach ($next_lesson_days as $key => $value)
            // {
            //     $text .= date('m-d, ', strtotime($value));
            // }
        }

        if ($missed_lesson_count)
        {
            if ($missed_lesson_count < 0)
            {
                $text .= "(共多上 <b>".abs($missed_lesson_count)."</b> 堂)";
            }
            else
            {
                $text .= "(共 <b>{$missed_lesson_count}</b> 堂未上)";
            }
        }
        $text .= '</p>';
    }

    if (0 && $next_lesson_days)
    {
        $text .= '<p>下月課堂:';
        foreach ($next_lesson_days as $key => $value)
        {
            $text .= date('m-d, ', strtotime($value));
        }
        $text .= '</p>';
    }

    $text .= "<p class=\"_rm\">每堂學費: $<b><input class_fee class_id=\"{$class_id}\" value=\"$price\"></b></p>";
    if ($missed_lesson_count)
    {
        if ($missed_lesson_count < 0)
        {
            $text .= "<p>今期學費: <b>\$$price x {$real_lesson_count}堂 ({$next_month_days_count}+".abs($missed_lesson_count).")</b></p>";
        }
        else
        {
            $text .= "<p>今期學費: <b>\$$price x {$real_lesson_count}堂 ({$next_month_days_count}-{$missed_lesson_count})</b></p>";
        }
    }
    else
    {
        $text .= "<p>今期學費: <b>\$$price x {$next_month_days_count}堂</b></p>";
    }
    $text .= "<p>= <b>\$$renew</b></p>";
    $text .= '<button class="form-button">Copy Text</button>';
    $rt               = array();
    $rt['text']       = $text;
    $rt['renew']      = $renew;
    $rt['price']      = $price;
    $rt['next_month'] = $next_month;
    $rt['class_days'] = $next_month_days;
    return $rt;
}

function get_order_list($date_start = '', $order_ids = array())
{
    $remove_fields = array('order_item_id', 'order_item_type', 'post_author', 'post_content', 'post_title', 'post_excerpt', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_mime_type', 'comment_count', 'meta_id', '_customer_ip_address', '_customer_user_agent', '_created_via', '_cart_hash', '_download_permissions_granted', '_recorded_sales', '_recorded_coupon_usage_counts', '_new_order_email_sent', '_order_stock_reduced', '_cart_discount', '_cart_discount_tax', '_order_shipping', '_order_shipping_tax', '_order_tax', '_order_version', '_prices_include_tax', '_shipping_address_index', '_billing_note', 'is_vat_exempt', 'whatsapp_notifications', 'sms_notifications', 'sms_notifications_time', 'whatsapp_notifications_time', '_wc_order_attribution_source_type', '_wc_order_attribution_utm_source', '_wc_order_attribution_session_entry', '_wc_order_attribution_session_start_time', '_wc_order_attribution_session_pages', '_wc_order_attribution_session_count', '_wc_order_attribution_user_agent', '_wc_order_attribution_device_type', '_ga_tracked', '_edit_lock', '_edit_last', '_billing_birthdate', '_billing_gender', '_billing_age', '_billing_school', '_billing_swimlevel', '_billing_swimtype1', '_billing_swimtype2', '_billing_swimtype3', '_billing_contactname', '_billing_contactphone', '_order_key', '_billing_address_index');
    if (empty($date_start))
    {
        $date_start = time() - 86400 * 90;
    }
    else
    {
        $date_start = strtotime($date_start);
    }
    $date_start = date('Y-m-d H:i:s', $date_start);
    $where      = array(
        '@posts.ID'         => 'woocommerce_order_items.order_id',
        '@postmeta.post_id' => 'woocommerce_order_items.order_id',
        '>posts.post_date'  => $date_start,
        'posts.post_status' => array('wc-completed', 'wc-refunded'),
    );
    if ($order_ids)
    {
        $where['postmeta.post_id'] = $order_ids;
    }
    $orders = db_find('woocommerce_order_items', $where,
        array('postmeta.post_id' => -1),
    );
    $orders_2 = array();
    if (!empty($orders))
    {
        foreach ($orders as $key => $value)
        {
            $mk = $value['meta_key'];
            $mv = $value['meta_value'];
            unset($value['meta_key'], $value['meta_value']);

            if (isset($orders_2[$value['order_id']]))
            {
                $orders_2[$value['order_id']][$mk] = $mv;
            }
            else
            {
                $orders_2[$value['order_id']]      = $value;
                $orders_2[$value['order_id']][$mk] = $mv;
            }
        }
        $orders = $orders_2;
    }
    foreach ($orders as $key => $order)
    {
        foreach ($order as $field => $val)
        {
            if (in_array($field, $remove_fields))
            {
                unset($orders[$key][$field]);
            }
        }
    }

    foreach ($orders as $key => &$order)
    {
        // $order_item_name = $order['order_item_name'];
        // if (strpos($order_item_name, ' - ') === false || strpos($order_item_name, ', 逢') == false)
        // {
        //     continue;
        // }
        // $pos                      = strpos($order_item_name, ' - ');
        // $product_name             = substr($order_item_name, 0, $pos);
        // $pa                       = substr($order_item_name, $pos + 3);
        // list($pa_month, $pa_time) = explode(', 逢', $pa);
        // $class_name               = $product_name.$pa_time;
        $order_item_name = $order['order_item_name'];
        if (strpos($order_item_name, ' - ') === false || strpos($order_item_name, '逢') == false)
        {
            continue;
        }
        $pos          = strpos($order_item_name, ' - ');
        $product_name = substr($order_item_name, 0, $pos);
        $pa           = substr($order_item_name, $pos + 3);
        $pos          = mb_strpos($pa, '逢');
        $pa_month     = mb_substr($pa, 0, $pos);
        $pa_time      = mb_substr($pa, $pos + 1);
        $pa_month     = trim($pa_month, "\t\n\r\v\f ,");
        $pa_time      = trim($pa_time, "\t\n\r\v\f ,");
        $class_name   = $product_name.$pa_time;
        $class_year   = get_class_year($order['post_date'], $pa_month);

        $order['month']      = $pa_month;
        $order['class_name'] = $class_name;
        $order['class_year'] = get_class_year($order['post_date'], $pa_month);
        $order['user_id']    = $order['_customer_user'];
        $order['month']      = $pa_month;
        $order['class_year'] = get_class_year($order['post_date'], $pa_month);
        $order['user_id']    = $order['_customer_user'];
    }
    return $orders;
}

function get_class_year($order_date, $class_month)
{
    if (!is_numeric($order_date))
    {
        $order_date = strtotime($order_date);
    }
    $order_year  = date('Y', $order_date);
    $order_month = date('n', $order_date);
    $class_month = get_array_month($class_month);
    $class_month = $class_month[0];
    if ($order_month > 7 && $class_month < 5)
    {
        $order_year++;
    }
    return $order_year;
}

function date_to_month_days($days)
{
    if (!is_array($days))
    {
        $days = explode(',', $days);
    }
    $out_array = array();
    foreach ($days as $day)
    {
        if (empty($day))
        {
            continue;
        }
        $stamp           = strtotime($day);
        $m               = date('m', $stamp);
        $d               = date('d', $stamp);
        $out_array[$m][] = $d;
    }

    $out_string = '';
    foreach ($out_array as $m => $days)
    {
        $out_string .= '#'.$m.'月: ';
        $_temp = '';
        foreach ($days as $day)
        {
            $_temp .= $day.', ';
        }
        $_temp = substr($_temp, 0, -2);
        $out_string .= $_temp.' ';
    }
    return $out_string;
}

// added
$cookies          = $_COOKIE;
$logged_in_cookie = null;
$username         = '';
foreach ($cookies as $key => $value)
{
    if (strpos($key, 'wordpress_logged_in_') === 0)
    {
        $logged_in_cookie = $key;
        break;
    }
}
if ($logged_in_cookie)
{
    // 解密cookie值
    $cookie_value = $cookies[$logged_in_cookie];
    $cookie_parts = explode('|', $cookie_value);
    // 确保cookie格式正确
    if (count($cookie_parts) === 4)
    {
        list($username, $expiration, $token, $hmac) = $cookie_parts;
    }
}
if (!empty($username))
{
    $user = get_user_by_name($username);
    define('EDU_UID', $user['ID']);
}
else
{
    define('EDU_UID', 0);
}
