<?php
define('DEBUG', 1);

include_once 'init.php';
$classes = db_find('edu_class');

$month_now = date('n');
$year_now = date('Y');

if ($month_now==12)
{
    $month_next = 1;
    $year_next = $year_now+1;
}else{
    $month_next = $month_now+1;
    $year_next = $year_now;
}

if ($month_next==12)
{
    $month_next2 = 1;
    $year_next2 = $year_next+1;
}else{
    $month_next2 = $month_next+1;
    $year_next2 = $year_next;
}

$month_now = $month_now.'月';



foreach ($classes as $key => $value) {
    $class = db_find_one('edu_class_user', array('class_id'=>$value['class_id'],'class_year'=>$year_now,array('%>month'=>$month_now.'-', '|%<month'=>'-'.$month_now, '|month'=>$month_now)), array('id'=>-1));
    if ($class)
    {
        $month = trim($class['month']);
        if (strpos($month, '-', 0) !== false)
        {
            $month = explode('-', $month);
            $month = trim($month[1]);
            // 判断最后班级月份的最后月是否和当前月相同. 如果相同则新增加
            if ($month == $month_now)
            {
                $insert_month = $month_next.'月'.'-'.$month_next2.'月';
                if ($year_next !== $year_next2)
                {
                    $insert_year = $year_next.'-'.$year_next2;
                }else{
                    $insert_year = $year_next;
                }
            }
        }
        else
        {
            if ($month == $month_now)
            {
                $insert_month = $month_next.'月';
                $insert_year = $year_next;
            }
        }

        $exists = db_find_one('edu_class_user', array('class_id'=>$value['class_id'], 'class_year'=>$insert_year,'month'=>$insert_month));
        if (!$exists)
        {
            $class['month'] = $insert_month;
            $class['class_year'] = $insert_year;
            unset($class['id']);
            db_insert('edu_class_user', $class);
        }else{
            echo '-';
        }
    }
    // var_dump($class);

}

