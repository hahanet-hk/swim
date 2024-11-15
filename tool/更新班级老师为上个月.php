<?php
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';
$classes = db_find('edu_class');
foreach ($classes as $key => $value)
{
    $delete = true;
    $now    = db_find_one('edu_class_user', array('class_id' => $value['class_id']), array('id' => -1));
    if ($now)
    {
        $teacher = decode_json($now['teacher']);
        if (empty($teacher))
        {
            $prev = db_find_one('edu_class_user', array('class_id' => $value['class_id'], '<id' => $now['id']), array('id' => -1));
            if ($prev)
            {
                db_update('edu_class_user', array('teacher' => $prev['teacher']), array('id' => $now['id']));
            }
        }
    }
}
exit("\n...end...");
