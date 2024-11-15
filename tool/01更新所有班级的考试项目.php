<?php
define('DEBUG', '1');

include_once __DIR__.'/../core/init.php';

$classes = db_find('edu_class');

foreach ($classes as $key => $value)
{
    db_update('edu_class_user', array('class_exam'=>$value['class_exam']), array('class_id' => $value['class_id']));
}

exit("\n...end...");
