<?php
    include __DIR__.'/core/init.php';
    $log   = db_find('edu_admin_log');
    $users = array();
    $exams = array();
    foreach ($log as $key => $value)
    {
        $users[] = $value['admin_user_id'];
        $exams[] = $value['edu_result_id'];
    }

    $exams = array_unique($exams);
    $exams = db_find('edu_result', array('@edu_class.class_id' => 'edu_result.class_id', 'edu_result.id' => $exams));
    $exams = arrlist_change_key($exams, 'id');

    $levels = array();
    foreach ($exams as $key => $value)
    {
        $levels[] = $value['exam_id'];
        $users[]  = $value['user_id'];
    }

    $users  = edu_get_user($users);
    $levels = array_unique($levels);
    $levels = db_find('edu_level', array('id' => $levels));
    $levels = arrlist_change_key($levels, 'id');

    function get_exam($exam_id, $exam_val)
    {
        if ($exam_val=='')
        {
            return '';
        }
        global $levels;
        $rt   = '';
        $exam = $levels[$exam_id];
        $exam = $exam['data'];
        $exam = decode_json($exam);
        switch ($exam['type'])
        {
            case 'radio':
                $_ = explode("\n", $exam['item']);
                foreach ($_ as $k1 => $v1)
                {
                    $rt .= (int)$exam_val === $k1 ? $v1.', ' : '';
                }
                return substr($rt, 0, -2);
                break;
            case 'checkbox':
                $_        = explode("\n", $exam['item']);
                $exam_val = decode_json($exam_val);

                if (!is_array($exam_val))
                {
                    $exam_val = array();
                }
                foreach ($_ as $k1 => $v1)
                {
                    $rt .= in_array($k1, $exam_val) ? $v1.', ' : '';
                }
                return substr($rt, 0, -2);
                break;
            default:
                return $exam_val;
                break;
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<div class="wrapper">
    <div class="section list">
        <table class="table">
            <thead>
                <tr>
                    <td>ID</td>
                    <td>管理員</td>
                    <td>操作</td>
                    <td>班級</td>
                    <td>時間</td>
                    <td>考試項目</td>
                    <td>學生</td>
                    <td>修改前</td>
                    <td>修改後</td>
                    <td>時間</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($log as $key => $value): ?>
<?php $exam = $exams[$value['edu_result_id']];?>
                <tr>
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo $users[$value['admin_user_id']]['billing_first_name'].' '.$users[$value['admin_user_id']]['billing_last_name']; ?></td>
                    <td><?php echo $value['handle']; ?></td>
                    <td>
                        <a href="history_show.php?class_id=<?php echo $exam['class_id']; ?>&class_month=<?php echo $exam['class_month']; ?>&exam_date=<?php echo $exam['exam_date']; ?>">
                            <?php echo $exam['class_name'].' '.$exam['class_month']; ?>
                        </a>
                    </td>
                    <td><?php echo $exam['exam_date']; ?></td>
                    <td><?php echo $levels[$exam['exam_id']]['name']; ?></td>
                    <td><?php echo $users[$exam['user_id']]['billing_first_name'].' '.$users[$exam['user_id']]['billing_last_name']; ?></td>


                    <td><?php echo get_exam($exam['exam_id'], $value['before']) ?></td>
                    <td><?php echo get_exam($exam['exam_id'], $value['after']) ?></td>
                    <td><?php echo date('Y-m-d H:i:s', $value['created']); ?></td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>