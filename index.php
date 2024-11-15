<?php
include __DIR__.'/core/init.php';
if ($is_admin)
{
    http_redirect('classes.php');
}
else
{
    http_redirect('result.php');
}
