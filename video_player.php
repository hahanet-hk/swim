<!DOCTYPE html>
<html lang="en">
    <head>
        <title></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head><body><?php include __DIR__.'/core/init.php'; $v = db_find_one('edu_level', array('id'=>get('id')));?><script>var _0x1a2b = { _0x3c4d: atob('<?php echo base64_encode($v['file_level']);?>')};var _0x5e6f = document.createElement('video');_0x5e6f.src = _0x1a2b._0x3c4d;_0x5e6f.setAttribute('playsinline', '');_0x5e6f.autoplay = false;_0x5e6f.controls = true;_0x5e6f.setAttribute('controlslist', 'nodownload');document.body.appendChild(_0x5e6f);</script></body>
</html>
<style>
*{margin: 0;padding: 0;}
html,body{background: #000;}
video{width: 100%;aspect-ratio: 4/3;}
</style>
<script>
document.oncontextmenu = function () {
	return false;
}
</script>