<?php

/**
 * File Scan settings view.
 *
 * @category   ClearOS
 * @package    File_Scan
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('file_scan');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/file_scan')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/file_scan/settings/edit'),
        anchor_cancel('/app/file_scan')
    );
}

// Daily scan dropdown
//--------------------

$hours = array('disabled' => lang('base_disabled'));

for ($i = 0; $i < 24; $i++)
        $hours[$i] = sprintf('%02d:00', $i);

if (! $hour)
    $hour = "disabled";

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('file_scan/settings'); 
echo form_header(lang('base_settings'));

echo fieldset_header(lang('file_scan_schedule'));
echo field_dropdown('hour', $hours, $hour, lang('file_scan_daily_scan'), $read_only);

echo fieldset_header(lang('file_scan_email_notification'));
echo field_checkbox('notify_on_virus', $notify_on_virus, lang('file_scan_notify_on_virus'), $read_only);
echo field_checkbox('notify_on_error', $notify_on_error, lang('file_scan_notify_on_error'), $read_only);
echo field_input('notify_email', $notify_email, lang('file_scan_email_address'), $read_only);

echo fieldset_header(lang('file_scan_directories'));

foreach ($presets as $directory => $description) {
    $selected = in_array($directory, $directories) ? TRUE : FALSE;
    echo field_checkbox("directories[$directory]", $selected, $description, $read_only);
}
$index = 1;
foreach ($custom as $directory) {
    echo field_input("custom-$index", $directory, lang('file_scan_custom') . " #$index", TRUE);
    $index++;
}

echo fieldset_footer(); 

echo field_button_set($buttons);

echo form_footer();
echo form_close();
