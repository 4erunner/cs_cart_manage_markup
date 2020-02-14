<?php
 /* CS-Cart Addon Manage Markup
 * @category   Add-ons
 * @copyright  Copyright (c) by Alexey Bituganov (ailands@ya.ru)
 * @license    MIT License
*/

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
$settings = Registry::get('addons.manage_markup');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($mode == "update_status"){
        $result = false;
        if (!preg_match("/^[a-z_]+$/", $_REQUEST['table'])) {
            return false;
        }
        if (!empty($_REQUEST['id']) && !empty($_REQUEST['status'])) {
            $result = db_query("UPDATE ?:".$_REQUEST['table']." SET status = ?s WHERE ?w", $_REQUEST['status'], array($_REQUEST['id_name'] => $_REQUEST['id']));
        }
        if ($result) {
            fn_set_notification('N', __('notice'), __('status_changed'));
            Tygh::$app['ajax']->assign('update_ids', $_REQUEST['id']);
            Tygh::$app['ajax']->assign('update_status', $_REQUEST['status']);
        }
        else {
            fn_set_notification('E', __('error'), __('error_status_not_changed'));
        }
    }
    elseif($mode == "update_c_status"){
        error_log(var_export($_REQUEST,true));
        if(!empty($_REQUEST['category_id']) && !empty($_REQUEST['c_status'])){
            $params = array(
                'category_id' => $_REQUEST['category_id'],
                'status' => $_REQUEST['c_status'] == "true" ? "Y" : "N",
            );
            $result = fn_manage_markup_change_c_status_groups($params);
            // if ($result) {
                // fn_set_notification('N', __('notice'), __('status_changed'));
            // }
            fn_manage_markup_get_categories($params, CART_LANGUAGE); 
            Tygh::$app['view']->display('design/backend/templates/addons/manage_markup/views/manage_markup/categories.tpl');            
        }
    }
}

if ($mode == 'categories') {
    $params_category = array(
        'category_id' => empty($_REQUEST['category_id']) ? 0 : $_REQUEST['category_id'],
    ); 
    fn_manage_markup_get_categories($params_category, CART_LANGUAGE);
}

function fn_manage_markup_get_categories($params, $lang_code = CART_LANGUAGE){
    $result = $child = false;
    Tygh::$app['view']->assign('categories_level', 0);
    $fields = array (
        '?:categories.category_id',
        '?:categories.parent_id',
        '?:categories.id_path',
        '?:category_descriptions.category',
        '?:categories.position',
        '?:categories.status',
        '?:manage_markup.id',
        '?:manage_markup_c_category.status as c_status'
    );

    $join = db_quote(" LEFT JOIN ?:category_descriptions ON ?:categories.category_id = ?:category_descriptions.category_id AND ?:category_descriptions.lang_code = ?s ", $lang_code);
    $join .= db_quote(" LEFT JOIN ?:manage_markup ON ?:categories.id_path = ?:manage_markup.category_path ");
    $join .= db_quote(" LEFT JOIN ?:manage_markup_c_category ON ?:categories.category_id = ?:manage_markup_c_category.category_id ");
    
    $where = db_quote('?:categories.parent_id = ?s', $params['category_id']);
    //$where = db_quote('?:categories.parent_id = ?s AND ?:categories.status = ?s', $params['category_id'],'A');    
    $result = db_get_hash_array('SELECT ' . implode(',', $fields) . " FROM ?:categories ?p WHERE ?p ORDER BY ?:categories.position", 'category_id', $join, $where);

    //fn_print_r($result);
    
    if($result){
        Tygh::$app['view']->assign('categories_level', count(explode('/',current($result)['id_path'])));
        $where = db_quote('?:categories.parent_id in (?a) and ?:categories.status = ?s',array_keys($result), 'A');

        $child = db_get_hash_array('SELECT ?:categories.parent_id, COUNT(*) as n FROM ?:categories WHERE ?p GROUP BY ?:categories.parent_id', 'parent_id', $where);             

        
    }
    if($child){
        foreach($child as $category_id => $category_data){
            $result[$category_id]['child'] = $category_data['n'];
        }        
    }
    else{
        foreach($result as $category_id => $category_data){
            $result[$category_id]['child'] = 0;
        } 
    }
    Tygh::$app['view']->assign('category_id', $params['category_id']);
    Tygh::$app['view']->assign('categories_tree', $result);
    if(!$result){
        fn_set_notification('N', __('notice'), __('api_merlion_errors.no_data'));
    }
    return $result;
}
