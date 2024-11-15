<?php
include __DIR__.'/core/init.php';
include __DIR__.'/core/common_header.php';
$daterange = get('daterange');
if ($daterange) {
    $daterange = explode(' - ', $daterange);
    $where['>=order_date'] = strtotime(($daterange[0]));
    $where['<=order_date'] = strtotime(($daterange[1]));
}else{
    $where['>=order_date'] = strtotime(date('Y-m-01'));
    $where['<=order_date'] = strtotime(date('Y-m-d'));
}

$all = 0;
$amount = 0;
$refund = 0;
$list = db_find('edu_order', $where);
$users = array();
foreach ($list as $key => $value) {
    $users[] = $value['user_id'];
    $all = bcadd($all, $value['amount'], 2);
    if ($value['woo_status']=='wc-completed') {
        $amount = bcadd($amount, $value['amount'], 2);
    }
    if ($value['woo_status']=='	wc-refunded') {
        $refund = bcadd($refund, $value['amount'], 2);
    }
}
$users = edu_get_user($users);

?>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <div class="h3">訂單</div>
    <div class="section">
        <table class="table">
            <tr><td>日期</td><td><input type="text" daterange value="<?php echo(get('daterange', date('Y-m-01').' - '.date('Y-m-d')))?>" class="form-text" daterange></td></tr>
        </table>
    </div>
    <div class="section pd">
        統計: &nbsp;
        #收款: <?php echo $amount;?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        #退款: <?php echo $refund;?>
    </div>
    <table class="table mgt">
        <thead>
        <tr>
            <td>訂單ID</td>
            <td>姓名</td>
            <td>班級</td>
            <td>學費</td>
            <td>繳費日期</td>
            <td>來源</td>
            <td>狀態</td>
        </tr>
        </thead>
        <tbody>
        <?php foreach( $list as $key => $val): ?>
            <tr>
                <td><?php echo $val['woo_order_id'];?></td>
                <td><?php echo $users[$val['user_id']]['first_name'];?></td>
                <td tl><?php echo $val['woo_class_name'];?></td>
                <td><?php echo $val['amount'];?></td>
                <td><?php echo date('Y-m-d', $val['order_date'])?></td>
                <td><?php echo $val['order_source'];?></td>
                <td><?php echo $val['woo_status'];?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>
</body>
</html>
<script>
$(function(){
    $('input[daterange]').daterangepicker({locale: {format: 'YYYY-MM-DD'}});
    $('input[daterange]').on('input change', function() {
        window.location.href = '?daterange='+$(this).val();
    });
});
</script>
<style>
thead{background: #eee;}
table.table{background: #fff;}
</style>