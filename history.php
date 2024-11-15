<?php
    include __DIR__.'/core/init.php';

    $handle = post('handle');
    if ($handle == 'get_user')
    {
        $kw = post('kw');
        $rt = get_user_by_kw($kw);
        msg(1, array('list' => $rt));
    }

    $class_ids = array();
    if (!$is_admin) {
        $class_ids = [];
        foreach ($class_user as $key => $value)
        {
            $class_ids[] = $value['class_id'];
        }
    }

    $where = array();
    if ($class_ids)
    {
        $where['class_id'] = $class_ids;
    }


    $classes = db_find('edu_class', $where);
    $classes = arrlist_change_key($classes, 'class_id');
    $history = db_find('edu_result', array('@GROUP'=>array('class_id','class_month','exam_date')), array('id'=>-1));

    $handle = get('handle');

    if ($handle=='delete' && $is_admin)
    {
        $class_id = get('class_id');
        $class_month = get('class_month');
        $exam_date = get('exam_date');
        if (empty($class_id) || empty($class_month) || empty($exam_date))
        {
            exit('班級/月份/日期缺少!');
        }
        db_delete('edu_result', array("class_id"=>$class_id, 'class_month'=>$class_month, 'exam_date'=>$exam_date));
        msg(1, 'Success');
    }


?>
<?php include __DIR__.'/core/common_header.php';?>
    <div class="h3">評估記錄</div>
    <div class="section user box pd">
        <div class="form-label-input">
            <label class="form-label" style="width:70px;font-weight:bold;">學生姓名</label>
            <div class="form-input"><input type="text" class="form-text" user-suggest></div>
        </div>
        <div class="suggest_wrap">
            <span class="close"><i class="fa fa-close"></i></span>
            <div class="table">
                <table class="suggest">
                    <script type="text/html">
                    {{each list v i}}
                    <tr>
                        <td><span class="id">{{v.ID}}</span><input type="hidden" value="{{v.ID}}"></td>
                        <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.last_name}}</a></td>
                        <td><a target="_blank" href="student.php?user_id={{v.ID}}">{{v.first_name}}</a></td>
                        <td>{{v.billing_gender}}</td>
                        <td>{{v.user_email}}</td>
                        <td><a target="_blank" href="https://wa.me/{{v.billing_phone}}">{{v.billing_phone}}</a></td>
                        <td><u><i class="fa fa-close"></i></u></td>
                    </tr>
                    {{/each}}
                    </script>
                </table>
            </div>
        </div>
    </div>

    <div class="section pd">
        <ul>
            <?php foreach ($history as $key => $value): ?>
                <?php
                if (!$is_admin && !in_array($value['class_id'], $class_ids ))
                {
                    continue;
                }
                ?>
                <li>
                    <span>
                        <?php if ($is_admin): ?>
                            <a data-href="?class_id=<?php echo $value['class_id'] ?>&class_month=<?php echo $value['class_month'] ?>&exam_date=<?php echo $value['exam_date'] ?>&handle=delete">刪除</a>
                        <?php endif ?>

                    </span>
                    <a href="history_show.php?class_id=<?php echo $value['class_id'] ?>&class_month=<?php echo $value['class_month'] ?>&exam_date=<?php echo $value['exam_date'] ?>"><?php echo $classes[$value['class_id']]['class_name'] ?>(<?php echo $value['class_month'] ?>) - <?php echo $value['exam_date'] ?></a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
    <a href="javascript:history.back();" class="form-button mgt15" style="display: block;">返回上一頁</a>
</div>
</body>
</html>
<style>
.section ul li{line-height: 35px;}
.section ul li span{float: right;}

.suggest_wrap{position:absolute;top: 100%;left: 0;right: 0;z-index: 10;padding: 5px 0;width: 100%;display: none;}
.suggest_wrap .close{display: inline-block;width: 29px;height: 29px;position: absolute;top: 0px;right: 0px;background: red;border-radius: 50%;}
.suggest_wrap .close .fa{width: 29px;height: 29px;text-align: center;color: #fff;line-height: 29px;cursor: pointer;font-size: 16px;}
.suggest_wrap .table{display: block;overflow-y: auto;margin-top: 10px;max-height: 300px;background: #ffe;border-radius: 4px;box-shadow: 0 0 5px #ddd;}
table.suggest{width: 100%;}
.suggest tr td{padding: 0 !important;cursor: pointer;line-height: 1;border-bottom: solid 1px #eee !important;font-size: 13px;}
.suggest tr:hover, table.sublist tbody tr:hover{background: #fee;}
.suggest td span.id, table.sublist tbody td span.id{display: inline-block;padding:0 3px;min-width: 35px;background: #ddd;color: #fff;border-radius: 4px;text-align: center;line-height: 1.5;}
.suggest td u{display: none;}
.box{position:relative;overflow: visible;;}
.user_show i.fa{cursor: pointer;float: right;color:red;}
</style>
<script>
$(function(){
    $("a[data-href]").click(function(event) {
        var url = $(this).attr('data-href');
        layer.confirm('確認刪除?', {
            btn: ['確認', '取消'] //按钮
        }, function() {
            $.get(url, function(data) {
                layer.msg('刪除成功', {
                    icon: 1
                }, function(){
                    window.location.reload();
                });
            });

        }, function() {

        });
    });

    // 處理用戶ajax搜索
    $("[user-suggest]").on('focus input propertychange', function(event) {
        $this = $(this);
        var kw = $(this).val();
        if (kw.length == 0){
            $this.parents('.box').find('.suggest_wrap').hide();
        }else{
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    handle: 'get_user',
                    kw: kw
                },
                success: function(msg) {
                    $this.parents('.box').find('table.suggest').render(msg.msg);
                    $this.parents('.box').find('.suggest_wrap').show();
                }
            });
        }
    });
    $("body").on('click', '.suggest tr', function(event) {
        var user_id = $(this).find("span.id").text();
        $(this).parents('.suggest_wrap').hide();
        layer.open({type:2, area: ['350px', '400px'], content: "history_score_user.php?user_id="+user_id, title: '歷史成績' });
    });
    $("body").on('click', '.close', function(event) {
        $(this).parents('.suggest_wrap').hide();
    });
    $(".user_show i.fa").click(function(){
        window.location.href="history.php";
    });


});
</script>