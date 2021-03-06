<?php

/**
 * Antivirus file scan controller.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2015 ClearFoundation
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Antivirus file scan controller.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2015 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

class File_Scan extends ClearOS_Controller
{
    /**
     * File scan server overview.
     * 
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('file_scan');
        $this->load->library('file_scan/File_Scan');

        try {
            if ($this->input->post('start')) {
                $this->file_scan->start_scan();
                sleep(2);
            } else if ($this->input->post('stop')) {
                $this->file_scan->stop_scan();
                sleep(2);
            }
        } catch (\Exception $e) {
            $this->page->set_message(clearos_exception_message($e), 'warning');
            redirect('/file_scan/settings');
        }

        // Load views
        //-----------

        $views = array('file_scan/scan', 'file_scan/report', 'file_scan/quarantine');

        $this->page->view_controllers($views, lang('file_scan_app_name'), $options);
    }
}
