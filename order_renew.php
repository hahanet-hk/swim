<?php
    define('DEBUG', 1);

    include __DIR__.'/core/init.php';
    $student_id = get('student_id');
    $user_id    = $student_id;
    $order_id   = get('order_id');
    $classes    = db_find('edu_class');
    $classes    = arrlist_change_key($classes, 'class_id');
    $handle     = post('handle');
    // 獲取班級
    if ($handle == 'select')
    {
        $where                              = array();
        $page                               = post('page');
        $kw                                 = post('kw');
        $table                              = 'edu_class';
        $pagesize                           = 10;
        empty($kw) or $where['%class_name'] = $kw;
        $list                               = db_list($table, $where, array('class_id' => -1), $page, $pagesize);

        $more = count($list) == $pagesize ? true : false;
        $rt   = array();
        $rt[] = array('id' => '', 'class_name' => '請選擇');
        foreach ($list as $key => $value)
        {
            $rt[] = array('id' => $value['class_id'], 'text' => $value['class_name']);
        }
        $out = array('results' => $rt, 'pagination' => array('more' => $more));
        header_json($out);
    }
    // 獲取月份
    if ($handle == 'get_month')
    {
        $where                         = array();
        $page                          = post('page');
        $kw                            = post('kw');
        $table                         = 'edu_class_user';
        $pagesize                      = 10;
        empty($kw) or $where['%month'] = $kw;
        $where['class_id']             = post('class_id');
        $list                          = db_list($table, $where, array('sort' => -1, 'id' => -1), $page, $pagesize);

        $more = count($list) == $pagesize ? true : false;
        $rt   = array();
        $rt[] = array('id' => '', 'text' => '請選擇');
        $last = reset($list);
        $last = get_string_next_month($last['month']);

        $rt[] = array('id' => $last, 'text' => $last);
        foreach ($list as $key => $value)
        {
            $rt[] = array('id' => $value['month'], 'text' => $value['month']);
        }

        $out = array('results' => $rt, 'pagination' => array('more' => $more));
        header_json($out);
    }

    if ($handle == 'order_add')
    {
        $data = post('data');

        if (empty($data['class_id']))
        {
            msg(0, '班級不能為空!');
        }
        if (empty($data['month']))
        {
            msg(0, '月份不能為空!');
        }
        if (empty($data['amount']))
        {
            msg(0, '學費不能為空!');
        }
        if (empty($data['order_date']))
        {
            msg(0, '繳費日期不能為空!');
        }
        if (empty($data['class_year']))
        {
            msg(0, '繳費年份不能為空!');
        }

        if (empty($data['gateway']))
        {
            msg(0, '付款方式不能為空!');
        }

        $data['amount'] = str_replace(',', '', $data['amount']);

        $data['order_date']   = strtotime($data['order_date']);
        $data['created']      = time();
        $data['order_source'] = 'manual';
        $where                = array();
        // 如果續費不存在, 則創建
        if (db_find_one('edu_order', array('class_id' => $data['class_id'], 'month' => $data['month'], 'class_year' => $data['class_year'],'user_id'=>$user_id)))
        {
            msg(0, '續費已存在!');
        }
        else
        {
            $data['user_id'] = $user_id;
            db_insert('edu_order', $data);
        }

        // 查看班级是否存在, 不存在则创建, 以及插入學生
        $where               = array();
        $where['class_id']   = $data['class_id'];
        $where['month']      = $data['month'];
        $where['class_year'] = date('Y');
        $_class_user         = db_find_one('edu_class_user', $where);
        if ($_class_user)
        {
            if (!in_class_user($user_id, $_class_user))
            {
                $student = decode_json($_class_user['student']);
                $student = array_merge(array($user_id), $student);
                $student = array_unique($student);
                db_update('edu_class_user', array('student' => $student), array('id' => $_class_user['id']));
            }
        }
        else
        {
            $prev = db_find_one('edu_class_user', array('class_id' => $data['class_id'], 'month' => $data['month'], 'class_year' => $where['class_year'], 'sort' => -1));
            if ($prev)
            {
                $where['teacher']    = $prev['teacher'];
                $where['class_exam'] = $prev['class_exam'];
            }
            $where['sort']    = year_month_sort($where['class_year'], $where['month']);
            $where['student'] = encode_json(array($student_id));
            db_insert('edu_class_user', $where);
        }

        msg(1, 'Success');
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@1.11.2/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.10.0/dist/css/bootstrap-datepicker.standalone.min.css">
    <script src="https://toms.cc/assets/msg.min.js"></script>
    <script src="https://toms.cc/assets/jquery.simphp.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="/edu2/assets/main.js"></script>
    <link rel="stylesheet" href="/edu2/assets/style.css">
</head>
<body>
<?php
if ($order_id) {
    $order               = db_find_one('edu_order', array('id' => $order_id));
    $order['class_name'] = $classes[$order['class_id']]['class_name'];
    $next_month          = get_string_next_month($order['month']);
}else{
    $order = array();
    $order['class_id'] = get('class_id');
    $order['class_name'] = $classes[$order['class_id']]['class_name'];
    $next_month = get_string_next_month(get('month'));
}

?>
<table class="table">
    <tbody>
        <tr>
            <td style="width: 50px;">班級</td>
            <td style="width: 290px;">
                <select name="class_id" class="form-select2-ajax" data-placeholder="請選擇">
                    <option value="<?php echo $order['class_id']; ?>"><?php echo $order['class_name']; ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td>年份</td>
            <td><?php echo form_text('class_year', date('Y')); ?></td>
        </tr>
        <tr>
            <td>月份</td>
            <td>
                <select name="month" data-placeholder="請選擇">
                    <option value="<?php echo $next_month; ?>"><?php echo $next_month; ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td>金額</td>
            <td><?php echo form_text('amount', get('order_renew')); ?></td>
        </tr>
        <tr>
            <td>繳費日期</td>
            <?php if (empty($data['order_date']))
                {
                    $data['order_date'] = date('Y-m-d');
            }?>
            <td><?php echo form_date('order_date', $data); ?></td>
        </tr>
        <tr>
            <td>付款方式</td>
            <td>
                <label><input type="radio" name="gateway" value="轉數快">轉數快</label>
                <label><input type="radio" name="gateway" value="銀行轉賬">銀行轉賬</label>
                <label><input type="radio" name="gateway" value="支付寶">支付寶</label>
                <label><input type="radio" name="gateway" value="PayMe">PayMe</label>
                <label><input type="radio" name="gateway" value="八達通">八達通</label>
            </td>
        </tr>
        <tr>
            <td colspan="2"><button class="form-button">保存</button></td>
        </tr>
    </tbody>
</table>
</body>
</html>
<style>
html,body{background: #fff;width: 100%;overflow-x: hidden;overflow-y: auto;}
table{background: #fff;width: 100%;table-layout:fixed;}
td select{min-width: 200px;width: 100% !important;}
.select2-container{font-size: 13px;}
.datepicker-dropdown{width: 230px;}
</style>
<script>
$(function(){
    $('select.form-select2-ajax').select2({
        allowClear: true,
        ajax: {
            url: $(this).attr('data-url') || '#',
            data: function(params) {
                return {handle: $(this).attr('data-handle') || 'select', kw: params.term, page: params.page || 1};
            },
            delay: 350,
            type: 'POST',
            dataType: 'json',
        }
    });

    $('select[name=month]').select2({
        allowClear: true,
        ajax: {
            url: $(this).attr('data-url') || '#',
            data: function(params) {
                return {handle: 'get_month',class_id:$("select[name=class_id]").val(), kw: params.term, page: params.page || 1};
            },
            delay: 350,
            type: 'POST',
            dataType: 'json',
        }
    });

    $('select.form-select2-ajax').on("change", function(e) {
        $('select[name=month]').val(null).trigger('change');
    });

    $("button").click(function(event) {
        var data = $(".table").serializeObject();
        $.ajax({
            url: '#',
            type: 'POST',
            data: {handle:'order_add', data: data},
            success:function(msg){
                if (msg.code==0) {
                    top.$msg.error(msg.msg);
                }else{
                    top.$msg.success('Success');
                    top.location.reload();
                }

            }
        });
    });
});
</script>