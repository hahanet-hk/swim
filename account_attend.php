<?php
    $user_id      = get_current_user_id();
    $classes      = db_find('edu_class');
    $classes      = arrlist_change_key($classes, 'class_id');


    $attendance   = db_find('edu_attendance', array('user_id' => $user_id));
    $attend       = array();

    $class_months = db_find('edu_class_user', array(array('%student' => '"'.$user_id.'"', '|%student_transfer' => '"'.$user_id.'"')));

    foreach ($class_months as $key => $mon)
    {
        $class_key = $classes[$mon['class_id']]['class_name'].' ('.$mon['month'].')';


        $user_days = db_find_one('edu_class_user_days', array('class_id' => $mon['class_id'], 'month' => $mon['month'], 'user_id' => $user_id));
        if (!empty($user_days) && !empty($user_days['days']))
        {
            $days = $user_days['days'];
        }
        else
        {
            $student_transfer = decode_json($mon['student_transfer']);
            if (in_array($student_id, $student_transfer))
            {
                $days = '';
            }
            else
            {
                $days = empty($mon['days']) ? get_days($classes[$mon['class_id']]['date_time'], $mon['month']) : $mon['days'];
            }

        }

        $days = explode(',', $days);

        $days_2 = arrlist_search($attendance, array('class_id' => $mon['class_id'], 'month' => $mon['month'], 'user_id'=>$user_id));

        if (!empty($days_2))
        {
            foreach ($days_2 as $days_2_v)
            {
                if (!empty($days_2_v['date']))
                {
                    $days[] = $days_2_v['date'];
                }
            }
        }

        // present late absent
        $attends = array();
        $days    = days_sort($days);
        foreach ($days as $day)
        {
            $attend = arrlist_search_one($attendance, array('class_id' => $mon['class_id'], 'month' => $mon['month'], 'date' => $day,'user_id'=>$user_id));
            if ($attend)
            {
                $attend = $attend['attendance'];
            }
            else
            {
                $attend = 'present';
            }

            $timestrape = strtotime($day);
            $m          = date('n', $timestrape);
            $d          = date('j', $timestrape);
            if (isset($attends[$attend][$m]))
            {
                $attends[$attend][$m] .= $d.', ';
            }
            else
            {
                $attends[$attend][$m] = $d.', ';
            }
        }
        $my[$class_key]['present'] = '';
        $my[$class_key]['late']    = '';
        $my[$class_key]['absent']  = '';
        foreach ($attends as $att => $ymd)
        {
            $a = '';
            foreach ($ymd as $m => $d)
            {
                $a .= $m.'月: '.substr($d, 0, -2).'&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            $my[$class_key][$att] = $a;
        }
        unset($my[$class_key]['clear']);
    }
?>
<?php if (!empty($my)): ?>
    <?php foreach ($my as $key => $value): ?>
        <div class="tit"><?php echo $key ?></div>
        <div class="txt">
            <?php foreach ($value as $k => $v): ?>
                <?php echo attend_text($k) ?>: <?php echo $v ?><br>
            <?php endforeach ?>
        </div>
    <?php endforeach ?>
<?php else: ?>
    <p tc>沒有任何記錄</p>
<?php endif ?>

<style>
.tit{margin-bottom: 0;font-weight: bold;}
.txt{margin-bottom: 15px;}
</style>