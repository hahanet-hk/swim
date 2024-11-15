<?php
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';
$classes = db_find('edu_class');
foreach ($classes as $key => $value)
{
    $delete = true;
    $list   = db_find('edu_class_user', array('class_id' => $value['class_id']));
    if ($list)
    {
        foreach ($list as $k2 => $v2)
        {
            $student          = $v2['student'];
            $student_transfer = $v2['student_transfer'];
            if (!empty($student))
            {
                $student = decode_json($student);
            }
            if (!empty($student))
            {
                $delete = false;
            }
            if (!empty($student_transfer))
            {
                $student_transfer = decode_json($student_transfer);
            }
            if (!empty($student_transfer))
            {
                $delete = false;
            }
        }
    }
    if ($delete)
    {
        $where             = array();
        $where['class_id'] = $value['class_id'];
        db_delete('edu_class', $where);
        db_delete('edu_class_user', $where);
    }
}
exit("\n<BR>...end...");
