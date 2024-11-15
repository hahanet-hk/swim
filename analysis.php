<?php
    include __DIR__.'/core/init.php';

function timeToSeconds($time) {
    $timeParts = explode(':', $time);
    $minutes = isset($timeParts[0]) ? intval($timeParts[0]) : 0;
    $seconds = isset($timeParts[1]) ? intval($timeParts[1]) : 0;
    $hundredths = isset($timeParts[2]) ? intval($timeParts[2]) : 0;

    return ($minutes * 60) + $seconds + ($hundredths / 100);
}


    $classes = db_find('edu_class');
    $classes = arrlist_change_key($classes, 'class_id');
    $history = db_find('edu_result', array(), array('id'=>1));

    $users = array();
    foreach ($history as $key => $value) {
        $users[] = $value['user_id'];
    }
    $users = edu_get_user($users);

    $times = db_find('edu_level', array('%data'=>'"type":"time"'));

    $chart_type = '';
    $chart_data = array();
    foreach ($times as $key => $value) {
        $exam_id = $value['id'];
        $value = decode_json($value['data']);
        $chart_type .= "'".$value['type']."',";

    }
    $chart_type = substr($chart_type, 0, -1);

    $chart_x = '';
    foreach ($users as $key => $value) {
        $chart_x .= "'".$value['first_name']."',";
    }
    $chart_x = substr($chart_x, 0, -1);
?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3">系統分析</div>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <div class="section pd">
        <div id="container" style="widows: 100%;aspect-ratio: 4 / 3;"></div>
    </div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>


<script>
$(function(){

    var dom = document.getElementById('container');
    var myChart = echarts.init(dom, null, {
        renderer: 'canvas',
        useDirtyRect: false
    });
    var app = {};
    var option;
    option = {
        title: {
            text: '游泳項目分析'
        },
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: [<?php echo $chart_type ?>]
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        toolbox: {
            feature: {
                saveAsImage: {}
            }
        },
        xAxis: {
            type: 'category',
            name: '會員',
            boundaryGap: false,
            data: [<?php echo $chart_x ?>]
        },
        yAxis: {
            type: 'value',
            name:'時間(秒)'
        },
        series: [
        <?php foreach ($times as $key => $value): ?>
        {
            name: '<?php echo $value['name'] ?>',
            type: 'line',
            stack: 'Total',
            data: [
            <?php foreach ($users as $user_id => $user_data): ?>
            <?php
            $data = arrlist_search($history, array('exam_id'=>$value['id'],'user_id'=>$user_id));
            if (empty($data)) {
                $data = array();
            }
            $data = reset($data);
            $val = form_val('exam_data', $data);
            echo "'".timeToSeconds($val)."',";
            ?>
            <?php endforeach ?>
            ]
        },
        <?php endforeach ?>

        ]
    };
    if (option && typeof option === 'object') {
        myChart.setOption(option);
    }
    window.addEventListener('resize', myChart.resize);

});
</script>