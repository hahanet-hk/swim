<?php
include __DIR__.'/core/init.php';
$user_id = get('user_id');
$result = db_find('edu_result', array('user_id'=>$user_id,'!=exam_data'=>''), array('exam_date'=>-1, 'id'=>-1));
$exams = db_find('edu_level');
$exams = arrlist_change_key($exams, 'id');
?>


<?php if (!empty($result)): ?>
<table class="xtable">
    <thead>
        <tr>
            <td>評估日期</td>
            <td>分類</td>
            <td>項目</td>
            <td>成績</td>
        </tr>
    </thead>
    <?php foreach ($result as $key => $item): ?>
        <?php
        $exam = $exams[$item['exam_id']];
        $pid = $exam['pid'];
        $exam = decode_json($exam['data']);
        $option = explode("\n", $exam['item']);
        $data = $item['exam_data'];
        ?>
        <tr>
            <td><?php echo $item['exam_date'] ?></td>
            <td>
                <?php echo $exams[$pid]['name']; ?>
            </td>
            <td><?php echo $exam['name'] ?></td>
            <td>
<?php
    switch ($exam['type']) {
        case 'text':
        case 'number':
            echo $data.'(米)';
            break;

        case 'time':
            echo $data.'(分:秒:毫秒)';
            break;

        case 'radio':
            echo empty($option[$data]) ? '' : $option[$data];
            break;

        case 'checkbox':
            $data = decode_json($data);
            if ($data) {
                foreach ($data as $k => $v) {
                    echo  $option[$v].' ';
                }
            }

            break;

        default:
            # code...
            break;
    }
?>
            </td>

        </tr>
    <?php endforeach ?>
</table>

<?php else: ?>
    <p style="text-align: center;">-- 暫無 --</p>
<?php endif ?>
<style>
html,body{margin:0; padding:0;}
table.xtable thead{font-weight: bold;background: #eee;}
table{width: 100%;border-spacing:0;border-collapse:collapse;overflow: hidden;}
td{padding: 3px 5px;border: 1px solid #ddd;}
</style>