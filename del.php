<?php
include __DIR__.'/core/init.php';

$list = db_find('edu_class', array('%class_name'=>'兒童游泳班-黃大仙'));

foreach ($list as $key => $value) {
    $class_id = $value['class_id'];
    var_dump($class_id);
    $class_users = db_find('edu_class_user', array('class_id'=>$class_id,'month'=>'9月','class_year'=>2024));
    foreach ($class_users as $k2 => $v2) {
        $student = decode_json($v2['student']);
        if (empty($student)) {
            db_delete('edu_class_user', array('class_id'=>$class_id, 'month'=>'9月', 'class_year'=>2024));
        }
    }
}