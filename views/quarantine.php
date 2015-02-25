<?php

/**
 * Quarantine summary.
 *
 * @category   apps
 * @package    file_scan
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2015 ClearFoundation
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

$this->lang->load('file_scan');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('base_filename'),
    lang('base_timestamp')
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($files as $virus) {

    ///////////////////////////////////////////////////////////////////////////
    // Item buttons
    ///////////////////////////////////////////////////////////////////////////

    $anchors = button_set(
        array(
            anchor_delete('/app/file_scan/quarantine/delete/' . md5($virus['name']), 'high')
        )
    );

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

    $item['title'] = $virus['filename'];
    $item['action'] = NULL;
    $item['anchors'] = $anchors;
    $item['details'] = array(
        preg_replace('/\.[0-9]{6}_[0-9]{6}$/', '', $virus['name']),
        date('F d, Y @ h:m', $virus['modified']),
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

$options['default_rows'] = 100;

echo summary_table(
    lang('file_scan_quarantine'),
    NULL,
    $headers,
    $items,
    $options
);
