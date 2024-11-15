<?php
    include __DIR__.'/core/init.php';
    include __DIR__.'/core/common_header.php';
?>
    <div class="h3">同步檢查</div>
    <div class="section pd">
        <div id="tools" style="widows: 100%;aspect-ratio: 4 / 3;">
            <ul>
                <li><a href="/edu2/tool/auto_update_class_user.php" target="_blank">點擊查看報名同步結果</a></li>
                <li><a href="/edu2/tool/class_list.php" target="_blank">點擊查看所有班級列表</a></li>
            </ul>
        </div>
    </div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
#tools ul li{padding: 8px;}
#tools ul li a{display: block;line-height: 36px;cursor: pointer;background: #ddd;border-radius: 6px;text-align: center;font-weight: bold;transition: all .3s;}
#tools ul li a:hover{background: #258671;color: #fff;}
</style>
