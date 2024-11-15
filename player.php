<?php
    include __DIR__.'/core/init.php';
    $id         = get('id');
    $edu_result = db_find_one('edu_result', array('id' => $id));
    $file = form_val('exam_file',$edu_result);
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
<?php if(empty($file)): ?>
    <div style="text-align:center;padding:15px;">無文件可查看!</div>
<?php elseif(substr($file, -4)=='.mp4'): ?>
<video src="<?php echo $file; ?>"></video>
<?php else: ?>
<img src="<?php echo $file; ?>" >
<?php endif; ?>
</body>
</html>
<style>
img{width: 100%;}
</style>