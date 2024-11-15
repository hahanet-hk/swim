<?php
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';

$classes        = db_find('edu_class');
$edu_class_user = db_find('edu_class_user');
foreach ($edu_class_user as $key => $value)
{
    $sort = year_month_sort($value['class_year'], $value['month']);
    db_update('edu_class_user', array('sort' => $sort), array('id' => $value['id']));
}

echo 'end';
