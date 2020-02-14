<?php
 /* CS-Cart Addon Manage Markup
 * @category   Add-ons
 * @copyright  Copyright (c) by Alexey Bituganov (ailands@ya.ru)
 * @license    MIT License
*/

use Tygh\Settings;
// Подключение класса Registry
use Tygh\Registry;
use Tygh\ManageMarkupLogger;

if ( !defined('AREA') ) { die('Access denied'); }

class ManageMarkupLocalValues{
    
    protected static $values = array();
    
    public function __get($name){
        if(array_key_exists($name, self::$values)){
            return self::$values[$name];
        }
        else{
            return NULL;
        }
    }
    
    public function __set($name, $value){
        self::$values[$name] = $value;
    }
}
$GLOBALS['manage_markup_local_values']  = new ManageMarkupLocalValues();
if(($manage_markup_status = $GLOBALS['manage_markup_local_values']->manage_markup_status) === NULL){
    $GLOBALS['manage_markup_local_values']->manage_markup_status = $manage_markup_status = Registry::get('addons.manage_markup.status') == "A" ? true : false ;
}
if($manage_markup_status){
    $GLOBALS['manage_markup_logger_func'] = $logger = new ManageMarkupLogger('manage_markup_func');
}


// $category_data	array	Category data
// $category_id	int	Category identifier
// $lang_code	string	Two-letter language code (e.g. 'en', 'ru', etc.)
function fn_manage_markup_update_category_post(&$category_data, &$category_id, &$lang_code){
    $logger = $GLOBALS['manage_markup_logger_func']->instance('fn_manage_markup_update_category_post');
    $runtime = Registry::get('runtime');
    if($runtime['controller'] == 'categories' && $runtime['mode'] == 'update'){
        if(!empty($category_data['manage_markup'])){
            $logger->message("Update category: ".$category_id);
            $logger->message("Get rules", $category_data['manage_markup']);
            $category_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $category_id);
            $check_rules = array();
            $new_rules = array();
            $update = array();
            foreach ($category_data['manage_markup'] as $markup){
                if($markup['id']){
                    $check_rules[] = (int) $markup['id'];
                    $update[] = $markup;
                }
                else{
                    $new_rules[] = $markup;
                }
            }
            if($check_rules){
                db_query('DELETE FROM ?:manage_markup WHERE id NOT IN (?n) AND category_path = ?s', $check_rules, $category_path);
            }
            if($update){
                $logger->message("Update rules", $update);
                foreach($update as $rule){
                    if(($rule['price_from']<$rule['price_to']) && ($rule['procent']>0 || $rule['margin']>0)){
                        db_query('UPDATE ?:manage_markup SET ?u WHERE id = ?i', $rule, $rule['id']);
                    }
                    else{
                        if(!($rule['price_from']<$rule['price_to'])){
                             $logger->message("Error on rule! Wrong price range", $rule);
                             fn_set_notification('E', __('error'), __("manage_markup.error.range"));
                        }
                        elseif(!($rule['procent']>0 || $rule['margin']>0)){
                            $logger->message("Error on rule! Wrong margin", $rule);
                            fn_set_notification('E', __('error'), __("manage_markup.error.margin"));
                        } 
                    }
                }
            }
            if($new_rules){
                $logger->message("New rules", $new_rules);
                foreach($new_rules as $rule){
                    $rule['category_path']=$category_path ;
                    if(($rule['price_from']<$rule['price_to']) && ($rule['procent']>0 || $rule['margin']>0)){
                        db_query('INSERT INTO ?:manage_markup ?e', $rule);
                    }
                    else{
                        if(!($rule['price_from']<$rule['price_to'])){
                             $logger->message("Error on rule! Wrong price range", $rule);
                             fn_set_notification('E', __('error'), __("manage_markup.error.range"));
                        }
                        elseif(!($rule['procent']>0 || $rule['margin']>0)){
                            $logger->message("Error on rule! Wrong margin", $rule);
                            fn_set_notification('E', __('error'), __("manage_markup.error.margin"));
                        }                      
                    }
                }
            }       
        }
        else{
            $category_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $category_id);
            db_query('DELETE FROM ?:manage_markup WHERE ?:manage_markup.category_path=?s', $category_path);
            $logger->message("Delete rules for category_id: ".$category_id);
        }
        if(!empty($category_data['manage_markup_concurent_runtime'])){
            if(!empty($category_data['manage_markup_concurent'])){
                if($category_data['manage_markup_concurent'] != $category_data['manage_markup_concurent_runtime']){
                    $params=array(
                        "status" => "Y",
                        'category_id' => $category_id
                    );
                    fn_manage_markup_change_c_status_groups($params);
                }
            }
            elseif($category_data['manage_markup_concurent_runtime'] == "Y"){
                $params=array(
                    "status" => "N",
                    'category_id' => $category_id
                );
                fn_manage_markup_change_c_status_groups($params);
            }
        }
    }
}

// $category_id	int	Category ID
// $field_list	array	List of fields for retrieving
// $get_main_pair	boolean	Get or not category image
// $skip_company_condition	boolean	Select data for other stores categories. By default is false. This flag is used in ULT for displaying common categories in picker.
// $lang_code	string	2-letters language code
// $category_data	array	Array with category fields
function fn_manage_markup_get_category_data_post(&$category_id, &$field_list, &$get_main_pair, &$skip_company_condition, &$lang_code, &$category_data){
    $logger = $GLOBALS['manage_markup_logger_func']->instance('fn_manage_markup_get_category_data_post');
    $condition = db_quote('?:manage_markup.category_path = ?s' , $category_data["id_path"]);
    $category_data['manage_markup'] = db_get_array("SELECT * FROM ?:manage_markup  WHERE ?p ORDER BY ?:manage_markup.price_from ", $condition);
    $category_data['manage_markup_concurent'] = db_get_field("SELECT status FROM ?:manage_markup_c_category  WHERE ?:manage_markup_c_category.category_id = ?i ", $category_data['category_id']); 
}

function fn_manage_markup_update_product_prices_pre(&$price_data)
{
    $runtime = Registry::get('runtime');
    $logger = $GLOBALS['manage_markup_logger_func']->instance('fn_manage_markup_update_product_prices_pre|'.$price_data['product_id']);
    if(($settings = $GLOBALS['manage_markup_local_values']->settings) === NULL){
        $GLOBALS['manage_markup_local_values']->settings = $settings = Registry::get('addons.manage_markup');
        $logger->message("load settings: ", $settings);
    }
    if(($controllers_schema = $GLOBALS['manage_markup_local_values']->controllers_schema) === NULL){
        $GLOBALS['manage_markup_local_values']->controllers_schema = $controllers_schema = fn_get_schema('controllers', 'execute');
        $logger->message("load schema: ", $controllers_schema);
    }
    if(($dir = $GLOBALS['manage_markup_local_values']->dir) === NULL){
        $GLOBALS['manage_markup_local_values']->dir = $dir = fn_get_files_dir_path();
        $logger->message("get files path: ", $dir);
    }
    if ($GLOBALS['manage_markup_local_values']->manage_markup_status && in_array($runtime['controller'], $controllers_schema)){
        try {
            $logger->message("\n");
            $product_code = '';
            $logger->message("product id : ".$price_data['product_id']."  start price: ".$price_data['price']);
            $exec = true;
            $concurent_rate = 0;
            $concurent_price = 0;
            $current_price = (int)$price_data['price'];
            $product_data = db_get_row('SELECT list_price, product_code, mm_force_list_price FROM ?:products WHERE product_id = ?i', $price_data['product_id']);

            // check akkom product
            $akkom_product_code = db_get_field("SELECT product_code FROM ?:api_merlion_akkom_product WHERE product_code = ?i", $product_data['product_code']);
            if (!$akkom_product_code) {
                $akkom_product_code = false;
            } else {
                $akkom_product_code = true;
            }

            if($settings['manage_markup_concuren_enable'] == 'Y' && !empty(trim($settings['manage_markup_concurent_file']))) {
                $logger->message("Concurent enable!");
                if(($concurent = $GLOBALS['manage_markup_local_values']->concurent) === NULL){
                    $concurent = array();
                    if (($handle = fopen($dir.trim($settings['manage_markup_concurent_file']), "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 64, ";")) !== FALSE) {
                            if((int)$data[1]>0){
                                $concurent[$data[0]]=(int)$data[1];
                            }
                        }
                        fclose($handle);
                    }                    
                    $GLOBALS['manage_markup_local_values']->concurent = $concurent;
                    $logger->message("Load prices: ".count($concurent));
                }
                $fields=array(
                    '?:products.product_code',
                    '?:manage_markup_c_category.status'
                );
                $join = db_quote(" LEFT JOIN ?:products_categories ON ?:products_categories.product_id = ?:products.product_id ");
                $join .= db_quote(" LEFT JOIN ?:manage_markup_c_category ON ?:manage_markup_c_category.category_id = ?:products_categories.category_id ");
                $product = db_get_row('SELECT ' . implode(',', $fields) . ' FROM ?:products ?p WHERE ?:products.product_id = ?i', $join, $price_data['product_id']);
                $product_code = $product['product_code'];
                $logger->message("concurent category: ".$product['status']);
                $logger->message("get product code: ".$product_code);
                if($product['status'] == "Y"){
                    if(array_key_exists($product_code, $concurent )){
                        $concurent_price = (int)$concurent[$product_code];
                        $logger->message("product code: ".$product_code." found in concurent prices", array("price"=>(int)$price_data['price'],"concurent"=>$concurent_price));
                        // если у нас цена мерлиона больше чем у конкурента
                        if ((int)$price_data['price'] >= $concurent_price) {
                            // если цена равная добавляем 1 процент к цене
                            if ((int)$price_data['price'] == $concurent_price) {
                                $concurent_rate = 0.01;
                                $exec = true;
                            } else {
                                $check_over_price = round((int)$price_data['price'] - (int)$price_data['price'] * 0.1 , (int)$settings['manage_markup_round']);
                                // если цена поставщика существенно выше чем цена конкурента не выводим данный товар
                                if ($check_over_price > $concurent_price) {
                                    $price_data['price']  = 0;
                                    $logger->message("concurent price [" .$concurent_price. "] less than 10 % with supplier price [" .$price_data['price']. "]");
                                    $logger->message("product id : ".$price_data['product_id']." product_code: ".$product_code." new price: ".$price_data['price']);
                                    return;
                                }
                            }
                        }
                        if((int)$price_data['price'] < $concurent_price){
                            $concurent_rate = 0.01;
                            $exec = true;
                        }
                    }                    
                }
            }
            if($settings['manage_markup_list_price'] == 'Y' && ($exec || $product_data['mm_force_list_price'] == 'Y') && !$akkom_product_code){
                $logger->message("List price enable");
                if(!empty($product_data['list_price'])){
                    $list_price = $product_data['list_price'];
                }
                else{
                    $list_price = 0;
                }
                if(!empty($product_data['product_code'])){
                    $product_code = $product_data['product_code'];
                }
                else{
                    $product_code = '';
                }
                $logger->message("Get list price: ".$list_price." for product_id ".$price_data['product_id']);
                if ($settings['manage_markup_filter_price'] == 'Y'){
                    $logger->message("List price filter enable");
                    if((int)$list_price < (int)$price_data['price'] && (int)$list_price > 0){
                        $logger->message('manage_markup_filter_price: ID['.$price_data['product_id'].'] price:'.(string)$price_data['price'].' > recommended:'.(string)$list_price.' product nulled');
                        $data = array(
                            'list_price' => 0,
                        );
                        if($settings['manage_markup_disable_filtered'] == 'Y'){
                            $data['status'] = 'D';
                        }
                        db_query("UPDATE ?:products SET ?u WHERE product_id = ?i", $data, $price_data['product_id']);
                        $list_price = 0;
                        $price_data['price'] = 0;
                        $product_data['list_price'] = 0;
                        $exec = false;
                    }
                }
                if((int)$list_price > 0){
                    $price_data['price'] = $list_price;
                    $data = array(
                        'list_price' => 0
                    );
                    db_query("UPDATE ?:products SET ?u WHERE product_id = ?i", $data, $price_data['product_id']);
                    $product_data['list_price'] = 0;
                    $exec = false;                   
                }
                else{
                    $exec = true;
                }
            }
            if((int)$product_data['list_price'] > 0 && $settings['manage_markup_list_price'] == 'Y' && !$akkom_product_code){
                $data = array(
                    'list_price' => 0
                );
                $logger->message("List price filter enable - nulled LIST PRICE",
                    db_query("UPDATE ?:products SET ?u WHERE product_id = ?i", $data, $price_data['product_id']));
            }
            if($exec && !$akkom_product_code){
                $logger->message("Pricing rules enable");
                $logger->message("Get price: ".$price_data['price']);
                $product_id = $price_data['product_id'];
                $condition = db_quote('?:products.product_id = ?s', $product_id);
                $join = db_quote(' LEFT JOIN ?:products_categories ON ?:products.product_id = ?:products_categories.product_id');
                $join .= db_quote(' LEFT JOIN ?:categories ON ?:products_categories.category_id = ?:categories.category_id');
                $fields = array(
                   '?:categories.id_path',
                   '?:products.product_code'
                );
                $fields = implode(',', $fields);
                $rows = db_get_array("SELECT ?p FROM ?:products ?p WHERE ?p ", $fields, $join, $condition);
                $category_path = '';
                
                foreach($rows as $row){
                    if ($row){
                        $product_code = $row['product_code'];
                        $categories = explode('/', $row['id_path']);
                        $condition = db_quote('?:manage_markup.price_from < ?i AND ?:manage_markup.price_to >= ?i AND ?:manage_markup.category_path LIKE ?l AND ?:manage_markup.status = ?s ', $price_data['price'], $price_data['price'], $categories[0].'%', "A");
                        $markups = db_get_array("SELECT * FROM ?:manage_markup  WHERE ?p ORDER BY ?:manage_markup.category_path ", $condition);
                        $category_path = '';
                        $current_markup = NULL;
                        foreach ($categories as $category){
                            $category_path .= $category;
                            foreach ($markups as $markup){
                                if ($category_path == $markup['category_path']){
                                    $current_markup = $markup;
                                }
                            }
                            $category_path .= '/';
                        }
                        if ($current_markup['margin'] ){
                            $price_data['price'] = round($price_data['price'] + $current_markup['margin'], (int)$settings['manage_markup_round']);
                        }
                        else{
                            $price_data['price'] = round($price_data['price'] + ($price_data['price']*$current_markup['procent'])/100, (int)$settings['manage_markup_round']);
                        }
                    }
                    else{
                        $price_data['price'] = 0;
                    }                      
                }
                $logger->message("Category path: ".$category_path);
                
            }

            // проверяем цену после наценки
            if ($concurent_price > 0) {
                // если цена после всех наценок больше чем у конкурента скинем
                if ($price_data['price'] > $concurent_price) {
                    if ($current_price < $concurent_price
                        && round($current_price + $current_price *$concurent_rate, (int)$settings['manage_markup_round']) <= $concurent_price
                    ) {
                        $logger->message("new price: [" .$price_data['price']. "] >  concurent price: ". $concurent_price);
                        $price_data['price'] = $concurent_price;
                    }
                }
            }

            $logger->message("product id : ".$price_data['product_id']." product_code: ".$product_code." new price: ".$price_data['price']);
            $logger->message("\n");
        } catch (Exception $e) {
             error_log($e->getMessage());
        }
    }
}


function fn_manage_markup_settings(){
    return Settings::instance()->getValues('manage_markup', 'ADDON')['manage_markup_config'];
}

function fn_manage_markup_create_date($date=NULL, $format='Y-m-d\TH:i:s'){
    try{
        switch(gettype($date)){
            case "integer":
                return date($format, $date ? $date : time());
                break;
            case "object":
                return $date->format($format);
                break;
            default:
                return date($format, time());
                break;
        }        
    }
    catch(Exception $e){
        error_log($e->getMessage());
    } 
}
function fn_manage_markup_change_c_status_groups($params){ 
    $ins = array("category_id"=>$params["category_id"], "status"=>$params["status"]);
    $result = db_query("INSERT INTO ?:manage_markup_c_category ?e ON DUPLICATE KEY UPDATE ?u ", $ins , $ins);
    $childs = db_get_fields('SELECT category_id FROM ?:categories WHERE ?:categories.parent_id = ?i', $params['category_id']);
    foreach($childs as $child_id){
        $child_params=array(
            "status" => $params['status'],
            'category_id' => $child_id
        );
        fn_manage_markup_change_c_status_groups($child_params);
    }
    return $result;
}

?>
