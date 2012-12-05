<?php

/**
 * Antivirus file scan configuration controller.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Controllers
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Antivirus file scan configuration controller.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

class Settings extends ClearOS_Controller
{
    /**
     * File scanner configuration.
     *
     * @return view
     */

    function index()
    {
        $this->_common('view');
    }

    /**
     * Edit settings view.
     *
     * @return view
     */

    function edit()
    {
        $this->_common('edit');
    }

    /**
     * View settings view.
     *
     * @return view
     */

    function view()
    {
        $this->_common('view');
    }

    /**
     * Common form handler.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _common($form_type)
    {
        // Load libraries
        //---------------

        $this->lang->load('file_scan');
        $this->load->library('file_scan/File_Scan');

        // Handle form submit
        //-------------------

        $this->form_validation->set_policy('notify_email', 'file_scan/File_Scan', 'validate_notify_email', FALSE);
        $this->form_validation->set_policy('notify_on_virus', 'file_scan/File_scan', 'validate_notify_on_virus', FALSE);
        $this->form_validation->set_policy('notify_on_error', 'file_scan/File_scan', 'validate_notify_on_error', FALSE);
        $form_ok = $this->form_validation->run();

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $requested = $this->input->post('directories');
                $presets = $this->file_scan->get_directory_presets();
                $configured = $this->file_scan->get_directories();
                $schedule_exists = $this->file_scan->scan_schedule_exists();

                // Update directories
                //-------------------

                foreach ($presets as $preset => $description) {
                    if (array_key_exists($preset, $requested) && (!in_array($preset, $configured)))
                        $this->file_scan->add_directory($preset);
                    else if (!array_key_exists($preset, $requested) && (in_array($preset, $configured)))
                        $this->file_scan->remove_directory($preset);
                }

                // Update shedule
                //---------------

                $hour = $this->input->post('hour');

                if ($schedule_exists && ($hour === 'disabled')) {
                    $this->file_scan->remove_scan_schedule();
                } else if (!$schedule_exists && ($hour !== 'disabled')) {
                    $this->file_scan->set_scan_schedule('0', $hour, '*', '*', '*');
                }

                // Update Alerts
                //--------------
                $this->file_scan->set_notify_email($this->input->post('notify_email'));
                $this->file_scan->set_notify_on_virus($this->input->post('notify_on_virus'));
                $this->file_scan->set_notify_on_error($this->input->post('notify_on_error'));

                $this->page->set_status_updated();
                redirect('/file_scan/settings');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['directories'] = $this->file_scan->get_directories();
            $data['presets'] = $this->file_scan->get_directory_presets();
            $data['custom'] = $this->file_scan->get_directory_custom();
            $data['schedule_exists'] = $this->file_scan->scan_schedule_exists();
            $data['notify_on_virus'] = $this->file_scan->get_notify_on_virus();
            $data['notify_on_error'] = $this->file_scan->get_notify_on_error();
            $data['notify_email'] = $this->file_scan->get_notify_email();

            $schedule = $this->file_scan->get_scan_schedule();
            $data['hour'] = $schedule['hour'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('file_scan/settings', $data, lang('base_settings'));
    }
}
