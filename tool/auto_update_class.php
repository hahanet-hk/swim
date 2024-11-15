<?php
include_once __DIR__.'/../core/init.php';
$db_prefix = DB_PREFIX;
// db_truncate('edu_class');
db_exec("CREATE TABLE IF NOT EXISTS `{$db_prefix}edu_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `month` char(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` char(12) NOT NULL,
  `attendance` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

db_exec("CREATE TABLE IF NOT EXISTS `{$db_prefix}edu_class` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` text NOT NULL,
  `district_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` char(255) NOT NULL,
  `date_time` char(255) NOT NULL DEFAULT '',
  `date_month` char(255) NOT NULL DEFAULT '',
  `class_date` text NOT NULL DEFAULT '',
  `class_exam` text NOT NULL DEFAULT '',
  `lv3` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

db_exec("CREATE TABLE IF NOT EXISTS `{$db_prefix}edu_class_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `month` char(255) NOT NULL,
  `student` text NOT NULL DEFAULT '',
  `teacher` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");


db_exec("CREATE TABLE IF NOT EXISTS `{$db_prefix}edu_level` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `name` char(255) NOT NULL DEFAULT '',
  `data` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

db_exec("CREATE TABLE IF NOT EXISTS `{$db_prefix}edu_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `level_id` int(11) NOT NULL,
  `month` char(255) NOT NULL,
  `data` longtext NOT NULL,
  `exam_student` longtext NOT NULL DEFAULT '',
  `exam_item` text NOT NULL DEFAULT '',
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");



$district       = array();
$term_taxonomy  = db_find('term_taxonomy', array('@terms.term_id' => 'term_taxonomy.term_id', 'term_taxonomy.taxonomy' => 'product_cat'));
$products_attrs = db_find('wc_product_attributes_lookup', array('@terms.term_id' => 'wc_product_attributes_lookup.term_id', 'wc_product_attributes_lookup.taxonomy' => array('pa_time', 'pa_month')));

// 獲取一級分類
$terms = arrlist_search($term_taxonomy, array('parent' => 0));
foreach ($terms as $key => $value)
{
    if (strpos($value['name'], '游泳班', 0) !== false)
    {
        $district[$value['term_id']] = $value['name'];
    }
}
foreach ($district as $district_id => $district_name)
{
    // 獲取二級分類
    $terms2 = arrlist_search($term_taxonomy, array('parent' => $district_id));

    foreach ($terms2 as $key => $value)
    {
        $lv2 = $value['term_id'];
        // 獲取二級分類下產品
        $product_ids = array();
        $products    = db_find('term_relationships', array('term_taxonomy_id' => $value['term_id']));
        foreach ($products as $key => $value)
        {
            $product_ids[] = $value['object_id'];
        }

        $posts = db_find('posts', array('ID' => $product_ids));

        foreach ($posts as $k => $product)
        {
            $pa_time    = arrlist_search($products_attrs, array('product_or_parent_id' => $product['ID'], 'taxonomy' => 'pa_time'));
            $time_month = array();
            foreach ($pa_time as $k1 => $v1)
            {
                if (!isset($time_month[$v1['name']]))
                {
                    $time_month[$v1['name']] = array();
                }
                array_push($time_month[$v1['name']], $v1['product_id']);
            }

            foreach ($time_month as $k1 => $v1)
            {
                $_month = array();
                foreach ($products_attrs as $k2 => $v2)
                {
                    if (in_array($v2['product_id'], $v1) && $v2['taxonomy'] == 'pa_month')
                    {
                        $_month[] = $v2['name'];
                    }
                }
                $_month          = array_unique($_month);
                $time_month[$k1] = $_month;
            }

            foreach ($time_month as $ptime => $pmonth)
            {
                $class                 = array();
                $class['class_name']   = $product['post_title'].mb_substr($ptime, 1);
                $class['district_id']  = $lv2;
                $class['date_time']    = $ptime;
                $class['product_id']   = $product['ID'];
                $class['product_name'] = $product['post_title'];
                $lv3                   = explode('-', $product['post_title']);
                $class['lv3']          = empty($lv3[1]) ? '' : trim($lv3[1]);

                $update               = $class;
                $update['date_month'] = encode_json($pmonth);

                if (db_find_one('edu_class', $class))
                {
                    db_update('edu_class', $update, $class);
                }
                else
                {
                    db_insert('edu_class', $update);
                }
            }
        }
    }
}
echo '---end---';
