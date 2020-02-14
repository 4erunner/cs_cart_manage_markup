<?php

 /* CS-Cart Addon Manage Markup
 * @category   Add-ons
 * @copyright  Copyright (c) by Alexey Bituganov (ailands@ya.ru)
 * @license    MIT License
*/


if ( !defined('AREA') ) { die('Access denied'); }

fn_register_hooks(
    'update_product_prices_pre',
    'get_category_data_post',
    'update_category_post'
);

?>