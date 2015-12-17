<?php

define("AOE_SCHEDULER_TESTSUITE_BASEDIR", dirname(__FILE__));

$magentoRoot = getenv('MAGENTO_ROOT');
if (empty($magentoRoot)) {
    $i=0;
    $prefix = '';
    do {
        foreach(array('app/Mage.php', 'htdocs/app/Mage.php') as $file) {
            if (is_file($prefix.$file)) {
                break 2;
            }
        }
        $prefix = '../'.$prefix;
    } while ($i++<6);
    if (!is_file($prefix.$file)) {
        echo "Could not find Magento root!"; exit(1);
    }
    $magentoRoot = realpath(dirname(dirname($prefix.$file)));
}

define('MAGENTO_ROOT', $magentoRoot);

require_once 'AbstractTest.php';