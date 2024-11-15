<?php
function upload($type = 'all')
{
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    @set_time_limit(5 * 60);
    $allowed = array('png', 'svg', 'bmp', 'jpg', 'jpeg', 'gif', 'psd', 'pdf', 'zip', 'rar', 'tmp', '7z', 'gz', 'tar', 'mp4', 'flv', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx');
    $guid    = empty($_REQUEST['guid']) ? uniqid('file_') : substr($_REQUEST['guid'], 3);
    empty($_REQUEST['id']) or $guid .= substr($_REQUEST['id'], 8);
    $file_name = '';
    $file_temp = '';
    if (!empty($_FILES))
    {
        foreach ($_FILES as $key => $value)
        {
            if (!empty($value['name']))
            {
                $file_name = $_FILES[$key]['name'];
                $file_temp = empty($_FILES[$key]['tmp_name']) ? '' : $_FILES[$key]['tmp_name'];
            }
        }
    }
    empty($_REQUEST['name']) or $file_name = $_REQUEST['name'];
    empty($file_name) and $file_name       = uniqid('file_');
    $file_name                             = str_replace(' ', '_', $file_name);
    $file_name                             = strtolower($file_name);
    $ext                                   = 'tmp';
    $pos                                   = strrpos($file_name, '.') and $ext  = substr($file_name, $pos + 1);
    in_array($ext, $allowed) or exit("{\"error\": 1, \"message\": \"file ext not allowed\"}");
    preg_match('/[\x{4e00}-\x{9fa5}]/u', $file_name) and $file_name = md5($file_name).'.'.$ext;
    $target                                                         = 'edu2/upload/'.date('Y/m/d/');
    defined('ROOT_PATH') or define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR);
    $target_path = ROOT_PATH.$target;
    file_exists($target_path) or mkdir($target_path, 0755, true);
    $target_url = '/'.$target.$file_name;
    $file_path  = $target_path.$file_name;
    if (file_exists($file_path))
    {
        $file_path  = $target_path.$guid.'.'.$ext;
        $target_url = '/'.$target.$guid.'.'.$ext;
    }
    $chunk  = isset($_REQUEST['chunk']) ? intval($_REQUEST['chunk']) : 0;
    $chunks = isset($_REQUEST['chunks']) ? intval($_REQUEST['chunks']) : 0;
    $in     = empty($file_temp) ? @fopen("php://input", "rb") : @fopen($file_temp, "rb");
    $in or exit("{\"error\": 1, \"message\": \"Upload file not found.\"}");
    $out = @fopen("{$file_path}.part", $chunks ? "ab" : "wb");
    $out or exit("{\"error\": 1, \"message\": \"Failed to open out stream.\"}");
    while ($buff = fread($in, 4096))
    {
        fwrite($out, $buff);
    }
    @fclose($out);
    @fclose($in);
    // Check if file has been uploaded
    if (!$chunks || $chunk == $chunks - 1)
    {
        rename("{$file_path}.part", $file_path);
        exit("{\"error\": 0, \"message\": \"Upload Success.\",\"url\":\"$target_url\"}");
    }
}

upload();
