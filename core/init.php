<?php
defined('DEBUG') or define('DEBUG', 0);
include __DIR__.'/include.php';
if (!cookie_get(date('Ymd')))
{
    // include 'auto_update.php';
    cookie_set(date('Ymd'), date('Ymd'));
}

if (empty($user))
{
    exit('沒有訪問權限001!');
}

$role = array();

foreach ($user as $key => $value)
{
    if (strpos($key, '_capabilities', 0) !== false)
    {
        $role = unserialize($value);
    }
}

$is_admin   = false;
$is_teacher = false;

if (!empty($role['administrator']))
{
    $is_teacher = true;
    $is_admin   = true;
}

$class_user = db_find('edu_class_user', array('%teacher' => '"'.$user['ID'].'"'));

if (!empty($class_user))
{
    $is_teacher = true;
}

if (!$is_teacher)
{
    exit('沒有訪問權限!');
}

$url = strtolower($_SERVER['REQUEST_URI']);

if (strpos($url, 'classes.php') !== false || strpos($url, 'field.php') !== false || strpos($url, 'analysis.php') !== false || strpos($url, 'student.php') !== false)
{
    if (!$is_admin)
    {
        exit('沒有管理員權限!');
    }
}
