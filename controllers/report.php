<?php

/**
 * File Scan report controller.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2012 ClearFoundation
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * File Scan report controller.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

class Report extends ClearOS_Controller
{
    /**
     * Account import progress controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('file_scan');
        $this->load->library('file_scan/File_Scan');

        // Load view data
        //---------------

        $data = array();

        $data['is_running'] = $this->file_scan->is_scan_running();

        // Load views
        //-----------

        $this->page->view_form('report', $data, lang('file_scan_app_name'));
    }

    /**
     * File Scan report clear controller
     *
     * @return view
     */

    function clear()
    {
        // Load libraries
        //---------------
        $this->load->library('file_scan/File_Scan');

        $this->file_scan->delete_state();
        redirect('file_scan');
    }
}
