<?php
include_once __DIR__.'/core/include.php';
$user_id    = get_current_user_id();
$is_teacher = db_find_one('edu_class_user', array('%teacher' => '"'.$user_id.'"'));
// modify download
// function custom_my_account_menu_items($items)
// {
//     global $is_teacher;
//     if (empty($is_teacher))
//     {
//         $items['downloads'] = '評估結果';
//     }
//     else
//     {
//         $items['downloads'] = '評估系統';
//     }
//     return $items;
// }
// add_filter('woocommerce_account_menu_items', 'custom_my_account_menu_items');

add_action('init', function ()
{
    add_filter('woocommerce_account_menu_items', function ($menu)
    {
        unset($menu['orders']);
        unset($menu['edit-address']);
        $menu['downloads'] = '學員手冊';
        $menu['edit-account'] = '賬戶資料';
        return $menu;
    });
});

if ($is_teacher)
{
    // add link to the menu
    add_filter('woocommerce_account_menu_items', function ($menu)
    {
        $menu['teacher'] = '評估系統';
        return $menu;
    });

    // hook the external URL
    add_filter('woocommerce_get_endpoint_url', function ($url, $endpoint, $value, $permalink)
    {
        if ('teacher' === $endpoint)
        {
            $url = '/edu2/';
        }
        return $url;
    }, 10, 4);
}
else
{

// 評估結果頁面
    add_action('init', function ()
    {
        add_filter('woocommerce_account_menu_items', function ($menu)
        {
            unset($menu['orders']);
            unset($menu['edit-address']);
            $menu['test-result'] = '評估結果';
            return $menu;
        });
        add_rewrite_endpoint('test-result', EP_PAGES);
    });
    add_action('woocommerce_account_test-result_endpoint', function ()
    {
        include 'account_test_result.php';
    });

// 出席記錄頁面
    add_action('init', function ()
    {
        add_filter('woocommerce_account_menu_items', function ($menu)
        {
            $menu['attend'] = '出席記錄';
            return $menu;
        });
        add_rewrite_endpoint('attend', EP_PAGES);
    });
    add_action('woocommerce_account_attend_endpoint', function ()
    {
        include 'account_attend.php';
    });

// 報名記錄頁面
    add_action('init', function ()
    {
        add_filter('woocommerce_account_menu_items', function ($menu)
        {
            $menu['myorder'] = '報名記錄';
            return $menu;
        });
        add_rewrite_endpoint('myorder', EP_PAGES);
    });
    add_action('woocommerce_account_myorder_endpoint', function ()
    {
        include 'account_myorder.php';
    });
}


// BMI记录
    add_action('init', function ()
    {
        add_filter('woocommerce_account_menu_items', function ($menu)
        {
            $menu['mybmi'] = '健康/成長記錄';
            return $menu;
        });
        add_rewrite_endpoint('mybmi', EP_PAGES);
    });
    add_action('woocommerce_account_mybmi_endpoint', function ()
    {
        include 'account_mybmi.php';
    });