<?php
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';
$list = db_find('edu_class_user');
foreach ($list as $key => $value)
{
    if ($value['sort'] > 20241000)
    {
        db_delete('edu_class_user', array('id' => $value['id']));
    }
}
