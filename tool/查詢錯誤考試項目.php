<?php
include_once __DIR__.'/../core/init.php';
echo "<pre>";
$list = db_find('edu_class');

$error_class_id = array();
$level_all      = db_find('edu_level');
$level_all      = arrlist_change_key($level_all, 'id');

foreach ($list as $key => $class)
{
    $level = $class['class_exam'];
    $level = decode_json($level);

    foreach ($level as $k => $v)
    {
        $child = db_find('edu_level', array('pid' => $v));
        if (empty($child))
        {
            if (!isset($error_class_id[$class['class_id']]))
            {
                $error_class_id[$class['class_id']] = array();
            }
            $error_class_id[$class['class_id']][] = $level_all[$v]['name'];
        }
    }
}

foreach ($error_class_id as $key => $value)
{
    echo "\n".'class_id: '.$key."\n";
    foreach ($value as $k2 => $v2)
    {
        echo $v2."\n";
    }
}
echo "</pre>";
