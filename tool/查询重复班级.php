<?php
// 查询重复的edu_class的class_name, 如果edu_class_user有重复使用class_id, 则更新class_id为第一个class_id, 移除edu_class中的重复class_name
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';
$classes     = db_find('edu_class');
$new_classes = array();
foreach ($classes as $key => $value)
{
    if (!isset($new_classes[$value['class_name']]))
    {
        $new_classes[$value['class_name']] = array();
    }
    $new_classes[$value['class_name']][] = $value['class_id'];
}

foreach ($new_classes as $key => $value)
{
    if (count($value) > 1)
    {
        $first_class_id = reset($value);
        foreach ($value as $class_id)
        {
            if ($first_class_id == $class_id)
            {
                continue;
            }
            db_update('edu_class_user', array('class_id' => $first_class_id), array('class_id' => $class_id));
            db_delete('edu_class', array('class_id' => $class_id));
            echo $class_id.',';
        }
        echo "\n";
    }
}

exit("\n...end...");
