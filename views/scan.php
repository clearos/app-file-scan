<?php

/**
 * File scan scanner view.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage views
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

$read_only = TRUE;

$buttons_off = array(
    form_submit_custom('start', lang('base_start'), 'high', array('id' => 'start'))
);

$buttons_on = array(
    form_submit_custom('stop', lang('base_stop'), 'high', array('id' => 'stop')),
);

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('file_scan'); 
echo form_header(lang('file_scan_scanner'));

echo field_input('state', $state, lang('base_state'), $read_only);
echo field_input('status', $status, lang('base_status'), $read_only);
echo field_progress_bar(lang('file_scan_progress'), 'progress');
echo field_input('known_viruses', '---', lang('file_scan_known_viruses'), $read_only, array('hide_field' => TRUE));
echo field_input('scanned_dirs', '---', lang('file_scan_total_dirs_scanned'), $read_only, array('hide_field' => TRUE));
echo field_input('scanned_files', '---', lang('file_scan_total_files_scanned'), $read_only, array('hide_field' => TRUE));
echo field_input('infected_files', '---', lang('file_scan_infected_files'), $read_only, array('hide_field' => TRUE));
echo field_input('data_scanned', '---', lang('file_scan_data_scanned'), $read_only, array('hide_field' => TRUE));
echo field_input('data_read', '---', lang('file_scan_data_read'), $read_only, array('hide_field' => TRUE));
echo field_input('time', '---', lang('file_scan_time'), $read_only, array('hide_field' => TRUE));
echo field_button_set($buttons_off);
echo field_button_set($buttons_on);

echo form_footer();
echo form_close();
