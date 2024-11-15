<?php
$user_id = get_current_user_id();
$list = db_find('edu_bmi', array('user_id'=>$user_id));

function bmi_result($bmi)
{
    if (empty($bmi))
    {
        return '';
    }

    if ($bmi<18.5)
    {
        return '<b>體重過輕</b>';
    }

    if ($bmi>=18.5 && $bmi <25)
    {
        return '正常體重';
    }

    if ($bmi>=25 && $bmi <30)
    {
        return '<b>超重</b>';
    }

    if ($bmi>=30)
    {
        return '<b>肥胖</b>';
    }

    return '其他';
}
?>
<script src="https://cdn.jsdelivr.net/npm/layui-laydate@5.3.1/src/laydate.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/layui-laydate@5.3.1/src/theme/default/laydate.min.css" rel="stylesheet">
<script src="https://libs.simphp.com/layer/3.2.0/layer.js"></script>
<script src="https://toms.cc/assets/msg.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
<table class="table">
    <thead>
        <tr>
            <td>身高(cm)</td>
            <td>體重(kg)</td>
            <td>頭圍(cm)</td>
            <td>測量日期</td>
            <td>BMI值</td>
            <td>健康</td>
            <td>修改</td>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($list as $key => $value): ?>
            <tr>
                <td><?php echo $value['height'] ?></td>
                <td><?php echo $value['weight'] ?></td>
                <td><?php echo $value['hc'] ?></td>
                <td><?php echo date('Y-m-d', $value['date']) ?></td>
                <td><?php echo $value['bmi'] ?></td>
                <td><?php echo bmi_result($value['bmi']); ?></td>
                <td>
                    <span data-id="<?php echo $value['id'] ?>" class="modify">修改</span>
                    <span data-id="<?php echo $value['id'] ?>" class="delete">刪除</span>
                </td>
            </tr>
        <?php endforeach ?>

    </tbody>
</table>
<div><div class="add" style="text-align: right;"><button class="myadd">新增</button></div></div>
<style>
thead{font-weight: bold;}
tbody{font-size:14px;}
span.delete, span.modify{cursor: pointer;}
.table b{font-weight: bold;color: red;}
</style>
<div class="chart">
    <div id="side">
        <h3>生長圖表</h3>
        <ul id="menu">
            <li type="height" class="on">身高</li>
            <li type="weight" class="weight">體重</li>
            <li type="bmi" class="bmi">BMI</li>
            <li type="hc" class="hc">頭圍</li>
        </ul>
    </div>
    <div class="chart_wrap">
        <div id="chart" style="width:100%;height:100%;position: absolute;"></div>
    </div>
</div>
<div style="color:red;">請注意:</div>
<ul style="font-size: 14px;line-height: 1.2">
    <li>性別、年齡影響結果的準確性。請先核實<a href="https://www.swim.hk/my-account/edit-account/">個人帳戶</a>的性別、出生日期。學員年齡由系統按出生日期自動計算，可不用填寫。</li>
    <li>0-18歲的生長圖，根據香港衞生署2024年下半年採用的<a href="https://www.dh.gov.hk/english/useful/useful_PP_Growth_Chart/files/growth_charts.pdf" target="_blank">新生長圖表</a>而製作，方便家長保存兒童的成長記錄。</li>
    <li>生長圖表(兒童學員使用)及BMI(成人學員使用)是個人健康的參考，遇有問題應向醫生或相關專業人士查詢。</li>
</ul>
<style>
.chart{overflow: hidden;}
.chart #side{width: 80px;float: left;}
.chart #side h3{font-weight: normal;font-size: 17px;margin-bottom:8px;}
.chart #menu li{cursor: pointer;line-height: 1.2;}
.chart #menu li.on{color: #009dd8;font-weight: bold;}
.chart .chart_wrap{width: calc(100% - 80px);max-width: 600px;;float: left;position: relative;aspect-ratio:3/3.8;}
@media all and (max-width:680px)
{
    .chart #side h3{font-weight: bold}
    .chart #side{width: 100%;}
    .chart #menu{width: 100%;margin: 0;padding:0;}
    .chart #menu li{float: left;list-style: none;margin: 0;;margin-right:15px;}
    .chart .chart_wrap{width: 100%;margin-top:20px;}
}
</style>
<script>
jQuery(function($){
    $(".myadd").click(function(event) {
        layer.open({title:'新增', type:2, content: '/edu2/add_bmi.php', area:['360px','520px']});
    });
    $("span.modify").click(function(event) {
        var id = $(this).attr('data-id');
        layer.open({title:'修改', type:2, content: '/edu2/add_bmi.php?id='+id, area:['360px','520px']});
    });
    $("span.delete").click(function(event) {
        var id = $(this).attr('data-id');
        layer.confirm('確認刪除?', {
            btn: ['確認', '取消'] //按鈕
        }, function() {
            $.ajax({
                url: '/edu2/add_bmi.php?id='+id,
                type: 'POST',
                data: {
                    handle: 'del'
                },
                success: function(msg) {
                    window.location.reload();
                }
            });

        }, function() {

        });
    });
<?php
$user_info = get_user_one($user_id);
$birthday = empty($user_info['billing_birthdate']) ? 0 : $user_info['billing_birthdate'];
$age_month = calculate_age_month($birthday);
$chartjs = $age_month>24 ? 'chart18.php' : 'chart2.php';
?>
    $("#side #menu li").click(function(){
        $(this).addClass('on').siblings().removeClass('on');
        var type = $(this).attr('type');
        $.getScript('/edu2/chart2.php?uid=<?php echo $user_id;?>&type='+type);
    });
    $("#side #menu li:eq(0)").click();
});
</script>