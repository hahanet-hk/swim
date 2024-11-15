<?php
define('DEBUG', 1);
header('Content-Type: text/javascript');
include __DIR__.'/core/include.php';
$chart_index = 3;
$chart_text = array('身高','體重','BMI','頭圍');
$chart_text_unit = array('cm','kg','kg/㎡','cm');
$type = empty($_GET['type']) ? '' : $_GET['type'];
$user_id = empty($_GET['uid']) ? '' : $_GET['uid'];

$user_info = get_user_one($user_id);
$birthday = empty($user_info['billing_birthdate']) ? 0 : $user_info['billing_birthdate'];
$age_month = calculate_age_month($birthday);
$user_gender = form_val('billing_gender',$user_info);

$recored = array();
$list = db_find('edu_bmi', array('user_id'=>$user_id));
$chart_data = array();
foreach ($list as $key => $value) {
    $m = calculate_age_month($birthday, date('Y-m-d', $value['date']));
    if ($age_month>24) {
        $x = $m/216*100;
    }else{
        $x = $m/24*100;
    }
    $y = $value[$type];
    $chart_data[] = array($x, $y);
}

switch ($type)
{
    case 'weight':
        $chart_index = 1;
        break;
    case 'bmi':
        $chart_index = 2;
        break;
    case 'hc':
        $chart_index = 3;
        break;
    default:
        $chart_index = 0;
        break;
}

$gender = $user_gender=='女' ? '女' : '男';
// $unit = 年/月
$unit = $age_month>24 ? '年' : '月';
$xlsx = SimpleXLSX::parse(__DIR__.'/xlsx/HK-2020-StandardTables.xlsx');
$xlsx = $xlsx->rows($chart_index);
$cents = array();
foreach ($xlsx as $key => $value) {
    if (trim($value[0]=='months'))
    {
        $cents = $value;
        break;
    }
}
?>
jQuery(function($){
    var chartDom = document.getElementById('chart');
    var myChart = echarts.init(chartDom);
    <?php if($age_month>24): ?>
            // X轴数据
            var data_x = [];
            var x_min = 2;
            var x_increment = 1;
            var x_max = 18;
            // Y軸2,3刻度數量
            var x_num2 = 5;
            var x_num3 = 0;
            // Y轴数据
            var data_y = [];
            // '身高','體重','BMI','頭圍';
            <?php if($chart_index==0): ?>
                var y_increment = 10;
                var y_min = 70;
                var y_max = 200;
                // Y軸2,3刻度數量
                var y_num2 = 5;
                var y_num3 = 0;
            <?php elseif($chart_index==1): ?>
                var y_increment = 10;
                var y_min = 0;
                var y_max = 130;
                // Y軸2,3刻度數量
                var y_num2 = 5;
                var y_num3 = 0;
            <?php elseif($chart_index==2): ?>
                var y_increment = 5;
                var y_min = 10;
                var y_max = 45;
                // Y軸2,3刻度數量
                var y_num2 = 4;
                var y_num3 = 0;
            <?php else: ?>
                var y_increment = 2;
                var y_min = 42;
                var y_max = 62;
                var y_num2 = 4;
                var y_num3 = 0;
            <?php endif; ?>
    <?php else: ?>
            // X轴数据
            var data_x = [];
            var x_min = 0;
            var x_increment = 2;
            var x_max = 24;
            // Y軸2,3刻度數量
            var x_num2 = 6;
            var x_num3 = 2;
            // Y轴数据
            var data_y = [];
            <?php if($chart_index==0): ?>
                var y_increment = 5;
                var y_min = 40;
                var y_max = 100;
                // Y軸2,3刻度數量
                var y_num2 = 5;
                var y_num3 = 0;
            <?php elseif($chart_index==1): ?>
                var y_increment = 1;
                var y_min = 1;
                var y_max = 18;
                // Y軸2,3刻度數量
                var y_num2 = 5;
                var y_num3 = 0;
            <?php elseif($chart_index==2): ?>
                var y_increment = 5;
                var y_min = 10;
                var y_max = 25;
                // Y軸2,3刻度數量
                var y_num2 = 10;
                var y_num3 = 0;
            <?php else: ?>
                var y_increment = 2;
                var y_min = 30;
                var y_max = 54;
                // Y軸2,3刻度數量
                var y_num2 = 5;
                var y_num3 = 0;
            <?php endif; ?>
    <?php endif; ?>
    // X/Y轴刻度
    for (var index = x_min; index <= x_max; index = index+x_increment) {
        data_x.push(index);
    }
    var data_x2 = [];
    for (let index = 0; index < data_x.length*x_num2-x_num2+1; index++) {
        data_x2.push('');
    }
    var data_x3 = [];
    for (let index = 0; index < data_x.length*x_num3-x_num3+1; index++) {
        data_x3.push('');
    }
    for (var index = y_min; index <= y_max; index = index+y_increment) {
        data_y.push(index);
    }
    var data_y2 = [];
    for (let index = 0; index < data_y.length*y_num2-y_num2+1; index++) {
        data_y2.push('');
    }
    var data_y3 = [];
    for (let index = 0; index < data_y.length*y_num3-y_num3+1; index++) {
        data_y3.push('');
    }
    var option;
        option = {title: {text: '<?php echo $gender;?>孩生長圖(<?php echo $chart_text[$chart_index]?>)'},
        grid: {
            show: true,
            backgroundColor: 'rgba(0,0,0,0)', // 设置背景颜色为透明
            borderWidth: 1, // 设置边框宽度
            borderColor: '#ccc', // 设置边框颜色
            left: '10px', // 设置grid组件左边距
            right: '50px', // 设置grid组件右边距
            top: '70px', // 设置grid组件上边距
            bottom: 0, // 设置grid组件下边距
            containLabel: true // 自动计算grid区域以适应坐标轴标签
        },
        tooltip: {trigger: 'axis'},
        xAxis:
        [
            {
                name:'年齡(月)',
                nameRotate:90,
                boundaryGap: false,
                position: 'bottom',
                axisTick: {length: -8,interval: 0, lineStyle: {color: '#888', fontSize: '14px'}},
                splitLine: {show: true,lineStyle:{type:'solid'}},
                data: data_x
            },
            {
                boundaryGap: false,
                position: 'bottom',
                axisTick: {length: -3,interval: 0, lineStyle: {color: '#888', fontSize: '14px'}},
                axisPointer: {show: false},
                data: data_x2
            },
            {
                boundaryGap: false,
                position: 'bottom',
                axisTick: {length: -5,interval: 0, lineStyle: {color: '#888', fontSize: '14px'}},
                axisPointer: {show: false},
                data: data_x3
            },
            {
                type: 'category',
                min: 1,
                max: 100,
                data:[0,100],
                show: false,
            },
        ],
        yAxis: [
        {
            name:"<?php echo $chart_text[$chart_index]?>(<?php echo $chart_text_unit[$chart_index]?>)",
            boundaryGap: false,
            position: 'left',
            axisTick: {length: -8,interval: 0, lineStyle: {color: '#333', fontSize: '14px'}},
            splitLine: {show: true,lineStyle:{type:'solid'}},
            type: "value",
            min: y_min,
            max: y_max,
            interval: y_increment,
            axisLabel: {formatter: '{value}'},
            },
            {
            boundaryGap: false,
            position: 'left',
            axisTick: {length: -5,interval: 0, lineStyle: {color: '#aaa', fontSize: '14px'}},
            axisLabel: {show: false},
            data: data_y2
            },
            {
            boundaryGap: false,
            position: 'left',
            axisTick: {length: -8,interval: 0, lineStyle: {color: '#aaa', fontSize: '14px'}},
            axisLabel: {show: false},
            data: data_y3
            },
        ],
    series:
    [
        <?php
        $border=0;
        ?>
        <?php foreach ($cents as $key => $cent): ?>
        <?php
        // Y的数值范围
        // 女: 5 13
        // 男 17 -27
            if ($gender == '男' && ($key<17 || $key>27) )
            {
                continue;
            }
            if ($gender == '女' && ($key<5 || $key>13) )
            {
                continue;
            }
            $border++;
        ?>
        {
            type: 'line',
            smooth: true,
            showSymbol: false,
            itemStyle: {normal: {color: "#aaa", lineStyle:{width: 1,type: '<?php echo $border%2!=0 ? 'dashed' : 'solid'?>'}}},
            data: [
                <?php
                    foreach ($xlsx as $k => $v) {
                        if ($age_month>24) {
                            // 排除年齡範圍
                            if (!is_int($v[1]) || $v[1]<2) {
                                continue;
                            }
                        }else{
                            // 排除>24個月的
                            if (!is_numeric($v[0]) || $v[0]%2 != 0 || $v[0]>24) {
                                continue;
                            }
                        }
                        echo $v[$key].',';
                    }
                    ?>
                ]
        },
        <?php endforeach ?>
        {
            xAxisIndex: 3,
            type: 'scatter',
            showSymbol: true,
            data: <?php echo encode_json($chart_data);?>
        }
    ]
};
option && myChart.setOption(option);
window.addEventListener('resize', function() {
    myChart.resize();
});

});
