<?php
 /* CS-Cart Addon Manage Markup
 * @category   Add-ons
 * @copyright  Copyright (c) by Alexey Bituganov (ailands@ya.ru)
 * @license    MIT License
*/

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'add') {
    if (Registry::get('runtime.company_id') && fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR')) {
        Registry::set('navigation.tabs.manage_markup', array(
            'title' => __('mmu_menu'),
            'js' => true
        ));
    }

} elseif ($mode == 'update') {
    if (Registry::get('runtime.company_id') && fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR')) {
        Registry::set('navigation.tabs.manage_markup', array(
            'title' => __('mmu_menu'),
            'js' => true
        ));
    }

}
