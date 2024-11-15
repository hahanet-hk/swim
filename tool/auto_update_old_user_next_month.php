<?php
define('DEBUG', 1);

include_once __DIR__.'/../core/init.php';
$classes = db_find('edu_class');

$month_now = date('n');
$year_now  = date('Y');

if ($month_now == 1)
{
    $month_prev = 12;
    $year_prev  = $year_now - 1;
}
else
{
    $month_prev = $month_now - 1;
    $year_prev  = $year_now;
}

$month_now = $month_now.'月';

db_delete('edu_class_user', array('>id' => 1108));
exit('xx');

$classes = db_find('edu_class_user', array('class_year' => $year_now, array('%>month' => $month_now.'-', '|%<month' => '-'.$month_now, '|month' => $month_now, 'history_students_status' => 0)), array('id' => -1));

foreach ($classes as $key => $class)
{

    $prev = db_find('edu_class_user', array('class_year' => $year_prev, 'class_id' => $class['class_id']), array('id' => -1));


    $old = array();
    foreach ($prev as $k2 => $v2)
    {
        if (!empty($v2['student']))
        {
            $old = $v2;
        }
    }

    if ($old)
    {
        $new_students  = decode_json($class['student']);
        $old_students  = decode_json($old['student']);
        $last_students = array_merge($new_students, $old_students);
        $last_students = array_unique($last_students);
        $last_students = encode_json($last_students);
        db_update('edu_class_user', array('student' => $last_students, 'history_students_status' => 1), array('id' => $class['id']));
    }


}

echo 'end';
