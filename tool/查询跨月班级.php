<?php
define('DEBUG', '1');
include_once __DIR__.'/../core/init.php';

$classes = db_find('edu_class');
$edu_class_user = db_find('edu_class_user');
foreach ($edu_class_user as $key => $value) {
    $month = str_replace('月', '', $value['month']);
    $pos = strpos($month, '-', 0);
    if ($pos) {
        $month = substr($month, $pos+1);
    }
    if (strlen($month)==1) {
        $month = '0'.$month;
    }
    $sort = intval($value['class_year'].$month);
    $edu_class_user[$key]['sort'] = $sort;
}

echo "<table>";
foreach ($classes as $key => $value)
{


    $list = arrlist_search($edu_class_user, array('class_id' => $value['class_id']), array('sort' => 1));
    $_    = false;
    if ($list)
    {
        foreach ($list as $k2 => $v2)
        {
            if (strpos($v2['month'], '-', 0) !== false)
            {
                $_ = true;
            }
        }
    }

    if ($_)
    {
        echo "<tr><td><a target=\"_blank\" href=\"class.php?class={$value['class_id']}\">[classID: ".$value['class_id'].'][productID: '.$value['product_id'].'] * '.$value['class_name'].'</a></td><td>';
        foreach ($list as $k2 => $v2)
        {
            if (strpos($v2['month'], '-', 0) == false && empty(decode_json($v2['order_id']))) {
                db_delete('edu_class_user', array('id'=>$v2['id']));
                echo '<span red>'.$v2['month'].'</span> ';
            }elseif(strpos($v2['month'], '-', 0) == false)
            {
                echo '<span green>'.$v2['month'].'</span> ';
            }
            else{
                echo '<span>'.$v2['month'].'</span> ';
            }


        }
    }
    echo "</td></tr>";
}
echo "</table>";
echo "<style>span{background: #ccc;margin-left: 10px;padding:0 3px;display: inline-block;border-radius: 4px;} tr:nth-child(2n){background:yellow}span[red]{background:red;}span[green]{background:green;}</style>";
exit("\n...end...");
