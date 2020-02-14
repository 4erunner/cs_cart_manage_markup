<?php
 /*
*/

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'update') {
    if (Registry::get('runtime.company_id') && fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR')) {
        Registry::set('navigation.tabs.manage_markup', array(
            'title' => __("mmu_menu"),
            'js' => true
        ));
    }
}
