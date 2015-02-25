<?php

/**
 * Antivirus file scan javascript helper.
 *
 * @category   apps
 * @package    file-scan
 * @subpackage javascript
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
//////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('file_scan');

header('Content-Type:application/x-javascript');
?>

var lang_delete = '<?php echo lang('base_delete'); ?>';
var lang_quarantine = '<?php echo lang('file_scan_quarantine'); ?>';
var lang_moved_to_quarantine = '<?php echo lang('file_scan_moved_to_quarantine'); ?>';

$(document).ready(function() {

	getData();

    $('#notify_email').css('width', '250px');
	function getData() {
        $.ajax({
            url: '/app/file_scan/scan/info',
            method: 'GET',
            dataType: 'json',
            success : function(json) {
				showData(json);
				window.setTimeout(getData, 1000);
            },
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				$("#status").html('Ooops: ' + textStatus);
				window.setTimeout(getData, 1000);
			}
        });
	}

	function showData(info) {
        $('#progress').animate_progressbar(parseInt(info.progress));

        if (info.state == 1)
            $("#state_text").html('<div class=\'theme-loading-small\'>' + info.state_text + '</div>');
        else
            $("#state_text").html(info.state_text);
		$("#status_text").html(info.status);
        if (info.stats != undefined) {
            $("#known_viruses_text").html(info.stats.known_viruses);
            $("#known_viruses_field").show();
            $("#engine_version_text").html(info.stats.engine_version);
            $("#engine_version_field").show();
            $("#scanned_dirs_text").html(info.stats.scanned_dirs);
            $("#scanned_dirs_field").show();
            $("#scanned_files_text").html(info.stats.scanned_files);
            $("#scanned_files_field").show();
            $("#infected_files_text").html(info.stats.infected_files);
            $("#infected_files_field").show();
            $("#data_scanned_text").html(info.stats.data_scanned);
            $("#data_scanned_field").show();
            $("#data_read_text").html(info.stats.data_read);
            $("#data_read_field").show();
            $("#data_read_text").html(info.stats.data_read);
            $("#data_read_field").show();
            $("#time_text").html(info.stats.time);
            $("#time_field").show();
            $("#stop").hide();
        }
        // Logs
        if ($('#report').length > 0) {
            table_report.fnClearTable();
            if (info.virus != undefined && info.virus != null) {
                $.each(info.virus, function(virushash) {
                    table_report.fnAddData([
                        this.filename,
                        this.virus,
                        (this.quarantined ? lang_moved_to_quarantine : action_buttons(virushash))
                    ]);
                });
            }
            if (info.error != undefined && info.error != null) {
                $.each(info.error, function() {
                    table_report.fnAddData([
                        this.error,
                        this.virus,
                        ''
                    ]);
                });
            }
            table_report.fnAdjustColumnSizing();
            $("#report tr:eq(1) td").css('line-height','20px');
        }
	}
});

function action_buttons(virushash) {
    
    return '<div class=\'theme-button-set ui-button-set\'>' +
        '<a href=\'/app/file_scan/scan/delete/' + virushash + '\' class=\'theme-button-set-first theme-anchor theme-anchor-edit theme-anchor-important\'>' + lang_delete + '</a>' +
        '<a href=\'/app/file_scan/scan/quarantine/' + virushash + '\' class=\'theme-button-set-last theme-anchor theme-anchor-edit theme-anchor-important\'>' + lang_quarantine + '</a>' +
        '</div>';
}


// vim: ts=4 syntax=javascript
