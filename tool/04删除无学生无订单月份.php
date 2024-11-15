<?php
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';
$list = db_find('edu_class_user');
foreach ($list as $key => $value)
{
    $student          = decode_json($value['student']);
    $student_transfer = decode_json($value['student_transfer']);
    $student_order    = decode_json($value['order_id']);

    if (empty($student) && empty($student_transfer) && empty($student_order)) {
        db_delete('edu_class_user', array('id'=>$value['id']));
    }
}

