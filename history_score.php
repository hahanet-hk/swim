<?php
    include __DIR__.'/core/init.php';
    $id         = get('id');
    $edu_result = db_find_one('edu_result', array('id' => $id));
    $history    = decode_json($edu_result['exam_history']);
    $exam       = db_find_one('edu_level', array('id' => $edu_result['exam_id']));
    $exam       = decode_json($exam['data']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<table class="table">
    <?php foreach ($history as $key => $value): ?>
    <tr>
        <td><?php echo date('Y-m-d H:i:s', $key); ?></td>
        <td>
            <?php switch ($exam['type'])
                {
                    case 'radio':
                        $_     = explode("\n", $exam['item']);
                        $_sval = (int) $edu_result['exam_data'];

                        foreach ($_ as $k1 => $v1)
                        {
                            $checked = $_sval === $k1 ? 'checked="checked"' : '';
                            echo "<label><input type=\"radio\" value=\"{$k1}\" {$checked} disabled=\"disabled\">{$v1}</label>";
                        }
                        break;

                    case 'checkbox':
                        $_     = explode("\n", $exam['item']);
                        $_sval = decode_json($edu_result['exam_data']);
                        if (empty($_sval))
                        {
                            $_sval = array();
                        }

                        foreach ($_ as $k1 => $v1)
                        {
                            $checked = in_array($k1, $_sval) ? 'checked="checked"' : '';
                            echo "<label><input type=\"checkbox\" value=\"{$k1}\" {$checked} disabled=\"disabled\">{$v1}</label>";
                        }
                        break;
                    default:
                        echo $value;
                        break;
            }?>

        </td>
    </tr>
    <?php endforeach;?>

</table>

</body>
</html>