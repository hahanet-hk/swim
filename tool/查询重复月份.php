<?php
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';
$class_uesrs = db_find('edu_class_user');
$out         = array();
foreach ($class_uesrs as $key => $value)
{
    $k = 'class_id='.$value['class_id'].'_month='.$value['month'];
    if (!isset($out[$k]))
    {
        $out[$k] = 1;
    }
    else
    {
        $out[$k]++;
    }
}

foreach ($out as $key => $value)
{
    if ($value > 1)
    {
        echo $key."<BR>\n";
    }
}

exit("\n...end...");
