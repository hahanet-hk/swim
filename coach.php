<?php
define('DEBUG', 1);
    include __DIR__.'/core/init.php';

    if (is_ajax())
    {
        if (db_find_one('edu_user', array('user_id'=>post('user_id')))) {
            $r = db_update('edu_user',array('hourly_wage'=>post('hourly_wage')), array('user_id'=>post('user_id')) );
        }else{
            $r = db_insert('edu_user',array('hourly_wage'=>post('hourly_wage'), 'user_id'=>post('user_id')) );
        }

        msg(1, 'Success');
    }


    $list = db_find('usermeta', array('%meta_key'=>'capabilities','%meta_value'=>'instructor'), array('user_id'=>1));
    $user_id = array();
    foreach ($list as $key => $value) {
        $user_id[] = $value['user_id'];
    }
    $user_id = array_unique($user_id);
    $users = edu_get_user($user_id);
    $hourly_wage = db_find('edu_user', array('user_id'=>$user_id));
    $hourly_wage = arrlist_change_key($hourly_wage,'user_id');
?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3">教練列表</div>
    <div class="section pd">
        <ul>
            <?php foreach ($users as $key => $value): ?>
                <?php
                if (!$is_admin)
                {
                    continue;
                }
                ?>
                <li>
                    <a href="student.php?user_id=<?php echo $value['ID'] ?>" target="_blank">
                        <?php echo $value['first_name'] ?> &nbsp; <?php echo $value['last_name'] ?>
                    </a>
                    <?php
                    $fee = 200.00;
                    $u = form_val($value['ID'], $hourly_wage);
                    if (!empty($u['hourly_wage']))
                    {
                        $fee = $u['hourly_wage'];
                    }
                    ?>
                    <span data-id="<?php echo $value['ID'] ?>"><label>時薪</label><?php echo form_text('hourly_wage', $fee) ?></span>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
.section ul{width: 100%;overflow: hidden;}
.section ul li{display: block;width: 100%;overflow: hidden;line-height: 45px;background: rgba(0, 0, 0, 0.02);margin: 6px 0;border-radius: 4px;}
.section ul li a{display: block;padding: 0 10px;float: left;}
.section ul li span{position:relative;float: right;}
.section ul li span label{display: block;float: left;position: absolute;bottom: 0px;}
.section ul li span input{margin-left: 35px;width: auto;width: 70px;}
</style>
<script>
$(function(){
    $("input[name=hourly_wage]").each(function(index, el) {
        $(this).data('data-val', $(this).val());
    });
    $("input[name=hourly_wage]").focusout(function(event) {
        $this = $(this);
        var v = $(this).val();
        var dv = $(this).data('data-val');
        if(v != dv){
            var d = {};
            d['user_id'] = $(this).parent('span').attr('data-id');
            d['hourly_wage'] = v;
            $.ajax({
                url: '#',
                type: 'POST',
                dataType: 'json',
                data: d,
                success: function(msg)
                {
                    $this.data('data-val', $this.val());
                    $.msg(msg);
                }
            })
        }
    });
});
</script>
