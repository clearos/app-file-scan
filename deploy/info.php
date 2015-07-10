<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'file_scan';
$app['version'] = '2.1.6';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('file_scan_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('file_scan_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['file_scan']['title'] = $app['name'];

/////////////////////////////////////////////////////////////////////////////
// Tooltips
/////////////////////////////////////////////////////////////////////////////
$app['tooltip'] = array(
    lang('file_scan_tooltip_custom_folders')
);

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-tasks-core',
    'app-antivirus-core',
    'app-mail-notification-core',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/file_scan' => array(),
    '/var/clearos/file_scan/quarantine' => array('mode' => '700', 'owner' => 'root', 'group' => 'root')
);
$app['core_file_manifest'] = array(
   'file_scan' => array(
        'target' => '/usr/sbin/file_scan',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    ),
    'app-file-scan.cron' => array(
        'target' => '/etc/cron.d/app-file-scan',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
   'file_scan.conf' => array(
        'target' => '/etc/clearos/file_scan.conf',
        'mode' => '0644',
        'owner' => 'webconfig',
        'group' => 'webconfig',
        'config' => TRUE,
        'config_params' => 'noreplace',
    )
);

$app['delete_dependency'] = array(
    'app-file-scan-core'
);
