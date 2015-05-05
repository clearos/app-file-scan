<?php

/**
 * File Scan class.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2015 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\file_scan;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('file_scan');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\tasks\Cron as Cron;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Configuration_File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('tasks/Cron');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Folder_Not_Found_Exception as Folder_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Folder_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * File_Scan base class.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2015 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

class File_Scan extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    // killall command
    const CMD_KILLALL = '/usr/bin/killall';

    // Antivirus scanner (wrapper)
    const FILE_AVSCAN = '/usr/sbin/file_scan';

    // Antivirus scanner (basename)
    const BASENAME_AVSCAN = 'file_scan';

    // List of directories to scan for viruses.
    const FILE_SCAN_FOLDERS = '/etc/avscan.conf';

    // Options for clamscan
    const FILE_CONFIG = '/etc/clearos/file_scan.conf';

    // Filename of instance (PID) lock file
    const FILE_LOCKFILE = '/var/run/avscan.pid';

    // Location of ClamAV scanner
    const FILE_CLAMSCAN = '/usr/bin/clamscan';

    // Location of scanner state/status file
    const FILE_STATE = '/var/clearos/framework/tmp/avscan.state';

    // Locating of quarantine directory
    const PATH_QUARANTINE = '/var/clearos/file_scan/quarantine';

    // Location of whitelist file
    const FILE_WHITELIST = '/var/clearos/file_scan/whitelist';

    // Location of quarantined files
    const FILE_QUARANTINE_LIST = '/var/clearos/file_scan/quarantine_list';

    // Status
    const STATUS_IDLE = 0;    
    const STATUS_SCANNING = 1;    
    const STATUS_INTERRUPT = 2;    

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    public $state = array();
    protected $config = array();
    protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Constructor.
     *
     * @return object
     */

    public function __construct() 
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->reset_state();
    }

    /**
     * Adds directory to scan list.
     *
     * @param string $dir Directory to scan
     *
     * @throws Engine_Exception, Folder_Not_Found_Exception
     * @return void
     */

    public function add_directory($dir)
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder($dir, TRUE);
        if (!$folder->exists())
            throw new Folder_Not_Found_Exception($dir);

        $dirs = $this->get_directories();

        if (count($dirs) && in_array($dir, $dirs))
            throw new Engine_Exception(lang('file_scan_dir_exists'), CLEAROS_ERROR);

        $dirs[] = $dir; sort($dirs);

        $file = new File(self::FILE_SCAN_FOLDERS, TRUE);

        if (!$file->exists())
            $file->create('root', 'root', '0644');

        $file->dump_contents_from_array($dirs);
    }

    /**
     * Deletes an entry in the whitelist.
     *
     * @param string  $hash          MD5 hash of virus filename to delete
     *
     * @throws Engine_Exception
     * @return void
     */

    public function delete_whitelist($hash)
    {
        clearos_profile(__METHOD__, __LINE__);

        $whitelist = new File(self::FILE_WHITELIST, TRUE);
        if (!$whitelist->exists())
            return;
        $list = $whitelist->get_contents_as_array();
        foreach ($list as $line) {
            if (md5($line) == $hash) {
                $whitelist->delete_lines("|^$line$|");
                break;
            }
        }
    }

    /**
     * Deletes a virus.
     *
     * @param string  $hash          MD5 hash of virus filename to delete
     * @param boolean $in_quarantine boolean indicating whether file is in quarantine or not
     *
     * @throws Engine_Exception
     * @return void
     */

    public function delete_virus($hash, $in_quarantine = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($in_quarantine) {
            $list = $this->get_quarantined_viruses();
            $quarantine = new File(self::FILE_QUARANTINE_LIST, TRUE);
            foreach ($list as $file_hash => $meta) {
                if ($file_hash == $hash) {
                    $virus = new File(self::PATH_QUARANTINE . "/" . $meta['filename'], TRUE);
                    if ($virus->exists())
                        $virus->delete();
                    if ($quarantine->exists())
                        $quarantine->delete_lines("/^$hash.*/");
                    break;
                }
            }
            return;
        }

        if (!file_exists(self::FILE_STATE))
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);

        // XXX: Here we use fopen rather than the File class.  This is because the File
        // class provides us with no way to do file locking (flock).  The state file
        // is therefore owned by webconfig so that we can manipulate it's contents.
        if (!($fh = @fopen(self::FILE_STATE, 'a+')))
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);

        if ($this->unserialize_state($fh) === FALSE) {
            fclose($fh);
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);
        }

        if (!isset($this->state['virus'][$hash]))
            throw new Engine_Exception(lang('base_file_not_found'), CLEAROS_ERROR);

        $virus = new File($this->state['virus'][$hash]['filename'], TRUE);
        if ($virus->exists())
            $virus->delete();

        // Update state file, delete virus
        unset($this->state['virus'][$hash]);

        $this->serialize_state($fh);
    }

    /**
     * Returns array of directories configured to scan for viruses.
     *
     * @return array of directory names
     * @throws Engine_Exception
     */

    public function get_directories()
    {
        clearos_profile(__METHOD__, __LINE__);

        $dirs = array();
        $file = new File(self::FILE_SCAN_FOLDERS, TRUE);
        if (!$file->exists())
            return $dirs;

        $folders = $file->get_contents_as_array();
        foreach ($folders as $path) {
            $folder = new Folder($path);
            if ($folder->exists())
                $dirs[] = $path;
        }

        sort($dirs);

        return $dirs;
    }

    /**
     * Returns array of preset directories.
     *
     * @return array of human directory names keyed by filessytem directory name
     */

    public function get_directory_presets()
    {
        clearos_profile(__METHOD__, __LINE__);

        $AVDIRS = array();
        
        include 'File_Scan.inc.php';

        $dirs = $AVDIRS;

        foreach ($dirs as $dir => $label) {
            $folder = new Folder($dir);
            if (!$folder->exists())
                unset($dirs[$dir]);
        }

        return $dirs;
    }

    /**
     * Returns array of custom directories.
     *
     * @return array of custom directory paths
     */

    public function get_directory_custom()
    {
        clearos_profile(__METHOD__, __LINE__);

        $dirs = array();
        $all_dirs = $this->get_directories();
        $preset_dirs = $this->get_directory_presets();

        foreach ($all_dirs as $dir) {
            $folder = new Folder($dir);
            if (!$folder->exists())
                continue;
            if (array_key_exists($dir, $preset_dirs))
                continue;
            $dirs[] = $dir;
        }

        return $dirs;
    }

    /**
     * Returns information on the scan.
     *
     * @return array of the scanner's status and information
     * @throws Engine_Exception
     */

    public function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Unserialize the scanner state file (if it exists)
        //--------------------------------------------------

        if (file_exists(self::FILE_STATE)) {
            if (($fh = @fopen(self::FILE_STATE, 'r'))) {
                $this->unserialize_state($fh);
                fclose($fh);
            }
        }

        // Set the last run timestamp if available
        //----------------------------------------

        if ($this->state['timestamp'] != 0)
            $info['last_run'] = strftime('%D %T', $this->state['timestamp']);
        else
            $info['last_run'] = lang('base_unknown');

        // Determine the scanner's status
        //-------------------------------

        $info['state'] = self::STATUS_IDLE;
        $info['state_text'] = lang('file_scan_idle');

        if (file_exists(self::FILE_LOCKFILE)) {
            if (($fh = @fopen(self::FILE_LOCKFILE, 'r'))) {
                list($pid) = fscanf($fh, '%d');

                if (!file_exists("/proc/$pid")) {
                    $info['state'] = self::STATUS_INTERRUPT;
                    $info['state_text'] = lang('file_scan_interrupted');
                } else {
                    $info['state'] = self::STATUS_SCANNING;
                    $info['state_text'] = lang('file_scan_scanning');
                }

                fclose($fh);
            }
        }

        // Calculate the completed percentage if possible
        //-----------------------------------------------

        $info['progress'] = 0;

        if ($this->state['count'] != 0 || $this->state['total'] != 0)
            $info['progress'] = sprintf('%.02f', $this->state['count'] * 100 / $this->state['total']);

        // ClamAV error codes as per clamscan(1) man page.
        // TODO: Perhaps all possible error strings should be localized?
        //--------------------------------------------------------------

        switch ($this->state['rc']) {
            case 0:
                $info['last_result'] = lang('file_scan_no_malware_found');
                break;
            case 1:
                $info['last_result'] = lang('file_scan_malware_found');
                break;
            case 40:
                $info['last_result'] = 'Unknown option passed';
                break;
            case 50:
                $info['last_result'] = 'Database initialization error';
                break;
            case 52:
                $info['last_result'] = 'Not supported file type';
                break;
            case 53:
                $info['last_result'] = 'Can\'t open directory';
                break;
            case 54:
                $info['last_result'] = 'Can\'t open file';
                break;
            case 55:
                $info['last_result'] = 'Error reading file';
                break;
            case 56:
                $info['last_result'] = 'Can\'t stat input file / directory';
                break;
            case 57:
                $info['last_result'] = 'Can\'t get absolute path name of current working directory';
                break;
            case 58:
                $info['last_result'] = 'I/O error, please check your file system';
                break;
            case 59:
                $info['last_result'] = 'Can\'t get information about current user from /etc/passwd';
                break;
            case 60:
                $info['last_result'] = 'Can\'t get  information about user (clamav) from /etc/passwd';
                break;
            case 61:
                $info['last_result'] = 'Can\'t fork';
                break;
            case 62:
                $info['last_result'] = 'Can\'t initialize logger';
                break;
            case 63:
                $info['last_result'] = 'Can\'t create temporary files/directories (check permissions)';
                break;
            case 64:
                $info['last_result'] = 'Can\'t write to temporary directory (please specify another one)';
                break;
            case 70:
                $info['last_result'] = 'Can\'t allocate and clear memory (calloc)';
                break;
            case 71:
                $info['last_result'] = 'Can\'t allocate memory (malloc)';
                break;
            default:
                $info['last_result'] = lang('base_unknown');
        }

        // Other information
        //------------------

        $info['error_count'] = 0;
        $info['malware_count'] = 0;
        $info['current_scandir'] = $this->state['dir'];

        // Errors and viruses
        // ------------------
        if (isset($this->state['virus'])) {
            $info['virus'] = $this->state['virus'];
            $info['malware_count'] = count($this->state['virus']);
        }
        if (isset($this->state['error'])) {
            $info['error'] = $this->state['error'];
            $info['error_count'] = count($this->state['error']);
        }

        // Errors and viruses
        // ------------------
        if (isset($this->state['virus']))
            $info['virus'] = $this->state['virus'];
        if (isset($this->state['error']))
            $info['error'] = $this->state['error'];

        // Stats
        // -----
        if (isset($this->state['stats']))
            $info['stats'] = $this->state['stats'];

        // Create a generic status message for the state of the scanner
        //-------------------------------------------------------------

        if ($info['state'] === self::STATUS_IDLE)
            $info['status'] = sprintf(lang('file_scan_last_run'), $info['last_run']);
        else if ($info['state'] === self::STATUS_SCANNING)
            $info['status'] = sprintf(lang('file_scan_currently_scanning'), $info['current_scandir']);
        else
            $info['status'] = '...';

        return $info;
    }

    /**
     * Set the quarantine action
     *
     * @param boolean $quarantine quarantine
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_quarantine($quarantine)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_quarantine($quarantine));

        if ($quarantine === 'on' || $quarantine == 1 || $quarantine == TRUE)
            $this->_set_parameter('quarantine', 1);
        else
            $this->_set_parameter('quarantine', 0);
    }

    /**
     * Set the notify on virus setting.
     *
     * @param boolean $notify notify
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_notify_on_virus($notify)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_notify_on_virus($notify));

        if ($notify === 'on' || $notify == 1 || $notify == TRUE)
            $this->_set_parameter('notify_on_virus', 1);
        else
            $this->_set_parameter('notify_on_virus', 0);
    }

    /**
     * Set the notify on error setting.
     *
     * @param boolean $notify notify
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_notify_on_error($notify)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_notify_on_error($notify));

        if ($notify === 'on' || $notify == 1 || $notify == TRUE)
            $this->_set_parameter('notify_on_error', 1);
        else
            $this->_set_parameter('notify_on_error', 0);
    }

    /**
     * Set the email address to notify of errors/viruses.
     *
     * @param String $email email
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_notify_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_notify_email($email));

        $this->_set_parameter('notify-email', $email);
    }

    /**
     * Returns array of quarantined viruses.
     *
     * @returns array Array of viruses in quarantine
     * @throws Engine_Exception
     * @return array virus information
     */

    public function get_quarantined_viruses()
    {
        clearos_profile(__METHOD__, __LINE__);

        $files = array();
        try {
            $quarantine = new File(self::FILE_QUARANTINE_LIST, TRUE);
            if (!$quarantine->exists())
                return $files;

            $list = $quarantine->get_contents_as_array();
            foreach ($list as $line) {
                list($md5, $filename, $folder, $extension) = preg_split('/\\|/', $line);
                $virus = new File(self::PATH_QUARANTINE . "/" . $filename . $extension, TRUE);
                if (!$virus->exists())
                    continue;
                $files[$md5] = array(
                    'filename' => $filename,
                    'folder' => $folder,
                    'extension' => $extension,
                    'timestamp' => $virus->last_modified()
                );
            }
        } catch (Folder_Not_Found_Exception $e) {
            return $files;
        }

        return $files;
    }

    /**
     * Returns configured file scan schedule.
     *
     * @return array of the scanner's configured schedule. 
     * @throws Engine_Exception
     */

    public function get_scan_schedule()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hour = '*';
        $day_of_month = '*';
        $month = '*';
        $cron = new Cron();

        if (!$cron->exists_configlet('app-file-scan')) return array('*', '*', '*');

        list($minute, $hour, $day_of_month, $month, $day_of_week) 
            = explode(' ', $cron->get_configlet('app-file-scan'), 5);

        $schedule['hour'] = $hour;
        $schedule['day_of_month'] = $day_of_month;
        $schedule['month'] = $month;

        return $schedule;
    }

    /**
     * Checks status of scanner.
     *
     * @return boolea TRUE if scan is running
     */

    public function is_scan_running()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!file_exists(self::FILE_LOCKFILE))
            return FALSE;

        $fh = @fopen(self::FILE_LOCKFILE, 'r');
        list($pid) = fscanf($fh, '%d');
        fclose($fh);

        // Perhaps this is a stale lock file?
        if (!file_exists("/proc/$pid")) {
            // Yes, the process 'appears' to no longer be running...
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Quarantines a virus.
     *
     * @param string $hash MD5 hash of virus filename to quarantine
     *
     * @throws Engine_Exception
     * @return void
     */

    public function quarantine_virus($hash)
    {
        clearos_profile(__METHOD__, __LINE__);

        $dir = new Folder(self::PATH_QUARANTINE, TRUE);
        if (!$dir->exists())
            $dir->create('root', 'root', 600);

        if (!file_exists(self::FILE_STATE))
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);

        // XXX: Here we use fopen rather than the File class.  This is because the File
        // class provides us with no way to do file locking (flock).  The state file
        // is therefore owned by webconfig so that we can manipulate it's contents.
        if (!($fh = @fopen(self::FILE_STATE, 'a+')))
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);

        if ($this->unserialize_state($fh) === FALSE) {
            fclose($fh);
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);
        }

        if (!isset($this->state['virus'][$hash]))
            throw new Engine_Exception(lang('base_file_not_found'), CLEAROS_ERROR);

        $virus = new File($this->state['virus'][$hash]['filename'], TRUE);
        
        $file_extension = '.' . date('ymd_His');
        $virus->move_to(self::PATH_QUARANTINE . '/' . basename($this->state['virus'][$hash]['filename']) . $file_extension);

        $quarantine_list = new File(self::FILE_QUARANTINE_LIST, TRUE);
        if (!$quarantine_list->exists())
            $quarantine_list->create('root', 'root', 600);
        
        $quarantine_list->add_lines(
            $hash . "|" . basename($this->state['virus'][$hash]['filename']) . "|" .
            dirname($this->state['virus'][$hash]['filename'])  . "|" . $file_extension  
        );

        // Update state file, delete virus
        unset($this->state['virus'][$hash]);
        $this->serialize_state($fh);
    }

    /**
     * Get whitelist.
     *
     * @throws Engine_Exception
     * @return void
     */

    public function get_whitelist()
    {
        clearos_profile(__METHOD__, __LINE__);
        $whitelist = new File(self::FILE_WHITELIST, TRUE);
        if (!$whitelist->exists())
            return FALSE;

        return $whitelist->get_contents_as_array();
    }

    /**
     * Whitelist a file.
     *
     * @param string  $hash          MD5 hash of file to whitelist
     * @param boolean $in_quarantine boolean indicating whether file is in quarantine or not
     *
     * @throws Engine_Exception
     * @return void
     */

    public function whitelist($hash, $in_quarantine = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($in_quarantine) {
            $list = $this->get_quarantined_viruses();
            $quarantine = new File(self::FILE_QUARANTINE_LIST, TRUE);
            foreach ($list as $file_hash => $meta) {
                if ($file_hash == $hash) {
                    $whitelist = new File(self::FILE_WHITELIST, TRUE);
                    if (!$whitelist->exists())
                        $whitelist->create('root', 'root', 600);
                    $whitelist->add_lines("\"" . $meta['folder'] . "/" . $meta['filename'] . "\"");
                    $virus = new File(self::PATH_QUARANTINE . "/" . $meta['filename'] . $meta['extension'], TRUE);
                    if ($virus->exists())
                        $virus->move_to($meta['folder'] . '/' . $meta['filename']);
                    if ($quarantine->exists())
                        $quarantine->delete_lines("/^$hash.*/");
                    break;
                }
            }
            return;
        }
        if (!file_exists(self::FILE_STATE))
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);

        // XXX: Here we use fopen rather than the File class.  This is because the File
        // class provides us with no way to do file locking (flock).  The state file
        // is therefore owned by webconfig so that we can manipulate it's contents.
        if (!($fh = @fopen(self::FILE_STATE, 'a+')))
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);

        if ($this->unserialize_state($fh) === FALSE) {
            fclose($fh);
            throw new Engine_Exception(lang('file_scan_state_error'), CLEAROS_ERROR);
        }

        if (!isset($this->state['virus'][$hash]))
            throw new Engine_Exception(lang('base_file_not_found'), CLEAROS_ERROR);

        $whitelist = new File(self::FILE_WHITELIST, TRUE);
        if (!$whitelist->exists())
            $whitelist->create('root', 'root', 600);
        $whitelist->add_lines("\"" . $this->state['virus'][$hash]['filename'] . "\"");

        // Update state file, remove file
        unset($this->state['virus'][$hash]);
        $this->serialize_state($fh);
    }

    /**
     * Removes directory from scan list.
     *
     * @param string $dir Directory to remove from scan
     *
     * @throws Engine_Exception
     * @return void
     */

    public function remove_directory($dir)
    {
        clearos_profile(__METHOD__, __LINE__);

        $dirs = $this->get_directories();

        if (!count($dirs) || !in_array($dir, $dirs))
            throw new Engine_Exception(lang('base_file_not_found'), CLEAROS_ERROR);

        foreach ($dirs as $id => $entry) {
            if ($entry != $dir) continue;
            unset($dirs[$id]);
            sort($dirs);
            break;
        }

        $file = new File(self::FILE_SCAN_FOLDERS, TRUE);

        $file->dump_contents_from_array($dirs);
    }

    /**
     * Removes an file scan schedule.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function remove_scan_schedule()
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();

        if ($cron->exists_configlet('app-file-scan'))
            $cron->delete_configlet('app-file-scan');
    }

    /**
     * Deletes state
     *
     * @return void
     */

    public function delete_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATE, FALSE);
        if ($file->exists())
            $file->delete();
    }

    /**
     * Resets state
     *
     * @return void
     */

    public function reset_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->state['rc'] = 0;
        $this->state['dir'] = '-';
        $this->state['filename'] = '-';
        $this->state['result'] = NULL;
        $this->state['count'] = 0;
        $this->state['total'] = 0;
        $this->state['error'] = array();
        $this->state['virus'] = array();
        $this->state['timestamp'] = 0;

        unset($this->state['stats']);
    }

    /**
     * Checks for existence of scan schedule.
     *
     * @return boolean TRUE if a cron configlet exists.
     * @throws Engine_Exception
     */

    public function scan_schedule_exists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();

        return $cron->exists_configlet('app-file-scan');
    }

    /**
     * Locks state file and writes serialized state.
     *
     * @param string $fh    file handle
     * @param array  $state state
     *
     * @return boolean TRUE if method succeeds
     */

    public function serialize_state($fh, $state = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($state != NULL)
            $this->state = $state;

        if (flock($fh, LOCK_EX) === FALSE)
            return FALSE;

        if (ftruncate($fh, 0) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (fseek($fh, SEEK_SET, 0) == -1) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (fwrite($fh, serialize($this->state)) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        fflush($fh);

        if (flock($fh, LOCK_UN) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
     * Sets an file-scan schedule.
     *
     * @param string $minute     cron minute value
     * @param string $hour       cron hour value
     * @param string $dayofmonth cron day-of-month value
     * @param string $month      cron month value
     * @param string $dayofweek  cron day-of-week value
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_scan_schedule($minute, $hour, $dayofmonth, $month, $dayofweek)
    {
        clearos_profile(__METHOD__, __LINE__);

        $cron = new Cron();

        $cron->add_configlet_by_parts(
            'app-file-scan',
            $minute, $hour, $dayofmonth, $month, $dayofweek,
            'root', self::FILE_AVSCAN . " >/dev/null 2>&1"
        );
    }

    /**
     * Starts virus scanner.
     *
     * @throws Engine_Exception
     * @return void
     */

    public function start_scan()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_scan_running())
            throw new Engine_Exception(lang('file_scan_file_scan_already_running'));

        $dirs = $this->get_directories();
        if (empty($dirs))
            throw new Engine_Exception(lang('file_scan_no_folders_selected'));

        // Make sure path exists
        $dir = new Folder(self::PATH_QUARANTINE, TRUE);
        if (!$dir->exists())
            $dir->create('root', 'root', 600);

        $options = array();
        $options['background'] = TRUE;
        $shell = new Shell();
        $shell->execute(self::FILE_AVSCAN, '', TRUE, $options);
    }

    /**
     * Stops virus scanner.
     *
     * @throws Engine_Exception
     * @return void
     */

    public function stop_scan()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_scan_running())
            return;

        $options = array();
        $options['background'] = TRUE;
        $shell = new Shell();
        $shell->execute(self::CMD_KILLALL, self::BASENAME_AVSCAN, TRUE, $options);
    }

    /**
     * Locks state file, reads and unserialized status.
     *
     * @param string $fh file handle
     *
     * @return boolean TRUE if method succeeds
     */

    public function unserialize_state($fh)
    {
        clearos_profile(__METHOD__, __LINE__);

        clearstatcache();
        $stats = fstat($fh);
        
        if ($stats['size'] == 0) {
            $this->reset_state();
            return TRUE;
        }

        if (flock($fh, LOCK_EX) === FALSE)
            return FALSE;

        if (fseek($fh, SEEK_SET, 0) == -1) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (($contents = stream_get_contents($fh)) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (($this->state = unserialize($contents)) === FALSE) {
            flock($fh, LOCK_UN);
            return FALSE;
        }

        if (flock($fh, LOCK_UN) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
     * Get the quarantine setting
     *
     * @return Boolean
     */

    function get_quarantine()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        $quarantine = $this->config['quarantine'];

        if ($quarantine == NULL || !$quarantine)
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Get the notify on virus setting
     *
     * @return Boolean
     */

    function get_notify_on_virus()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        $notify = $this->config['notify_on_virus'];

        if ($notify == NULL || !$notify)
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Get the notify on error setting
     *
     * @return Boolean
     */

    function get_notify_on_error()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        $notify = $this->config['notify_on_error'];

        if ($notify == NULL || !$notify)
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Get the email notification.
     *
     * @return String
     */

    function get_notify_email()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        $email = $this->config['notify-email'];

        return $email;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration files.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);

        $this->config = $configfile->load();

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG, TRUE);

        if (!$file->exists())
            $file->create('webconfig', 'webconfig', '0644');

        $match = $file->replace_lines("/^$key\s*=\s*/", "$key=$value\n");

        if (!$match)
            $file->add_lines("$key=$value\n");

        $this->is_loaded = FALSE;
    }

    /**
     * Validation routine for email.
     *
     * @param string $email email
     *
     * @return mixed void if email is valid, errmsg otherwise
     */

    public function validate_notify_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($email == "")
            return;

        if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email))
            return lang('file_scan_email_invalid');
    }

    /**
     * Validation routine for notify on virus.
     *
     * @param boolean $notify notify
     *
     * @return mixed void if notify on virus is valid, errmsg otherwise
     */

    public function validate_notify_on_virus($notify)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for notify on error.
     *
     * @param boolean $notify notify
     *
     * @return mixed void if notify on error is valid, errmsg otherwise
     */

    public function validate_notify_on_error($notify)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for quarantine.
     *
     * @param boolean $quarantine quarantine
     *
     * @return mixed void if quarantine on error is valid, errmsg otherwise
     */

    public function validate_quarantine($quarantine)
    {
        clearos_profile(__METHOD__, __LINE__);
    }
}

// vi: ts=4
