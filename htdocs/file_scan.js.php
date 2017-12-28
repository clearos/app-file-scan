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
var lang_whitelist = '<?php echo lang('file_scan_whitelist'); ?>';
var lang_moved_to_quarantine = '<?php echo lang('file_scan_moved_to_quarantine'); ?>';

$(document).ready(function() {

    window.setTimeout(getData, 200);

	function getData() {
        $.ajax({
            url: '/app/file_scan/scan/info',
            method: 'GET',
            dataType: 'json',
            success : function(json) {
				showData(json);
                if (json.state != 0)
                    window.setTimeout(getData, 3000);
            },
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				$("#status").html('Ooops: ' + textStatus);
				window.setTimeout(getData, 3000);
			}
        });
	}

	function showData(info) {

        clearos_set_progress_bar('progress', parseInt(info.progress), null);
        if (info.state == 0) {
            $("#state_text").html(info.state_text);
        } else if (info.state == 1) {
            var options = new Object();
            options.text = info.state_text;
            if ($("#state").val() != info.state)
                $("#state_text").html(clearos_loading(options));
            $("#state").val(info.state);
        } else {
            $("#state_text").html(info.state_text);
        }

		$("#status_text").html(info.status);

        if ((info.stats != undefined) && (info.state != 1)) {
            $("#known_viruses_text").html(info.stats.known_viruses);
            $("#known_viruses_field").show();
            $("#engine_version_text").html(info.stats.engine_version);
            $("#engine_version_field").show();
            $("#scanned_dirs_text").html(info.stats.scanned_dirs);
            $("#scanned_dirs_field").show();
            $("#scanned_files_text").html(info.stats.scanned_files);
            $("#scanned_files_field").show();
            $("#infected_files_text").html(info.stats.infected_files);
            if (info.stats.infected_files > 0)
                $("#infected_files_text").addClass('theme-text-alert');
            $("#infected_files_field").show();
            $("#data_scanned_text").html(info.stats.data_scanned);
            $("#data_scanned_field").show();
            $("#data_read_text").html(info.stats.data_read);
            $("#data_read_field").show();
            $("#data_read_text").html(info.stats.data_read);
            $("#data_read_field").show();
            $("#time_text").html(info.stats.time);
            $("#time_field").show();
        }

        if ((info.state == 1)) {
            $("#start").hide();
            $("#edit").hide();
            $("#stop").show();
        } else {
            $("#start").show();
            $("#stop").hide();
            $("#edit").show();
        }

        // Logs
        if ($('#report').length > 0) {
            table_report = get_table_report();
            table_report.fnClearTable();
            if (info.virus != undefined && info.virus != null) {
                $.each(info.virus, function(virushash) {
                    table_report.fnAddData([
                        this.filename_without_path,
                        this.path,
                        this.virus,
                        (this.quarantined ? lang_moved_to_quarantine : action_buttons(virushash))
                    ]);
                });
            }
            if (info.error != undefined && info.error != null) {
                $.each(info.error, function() {
                    table_report.fnAddData([
                        this.error,
                        '',
                        this.virus,
                        ''
                    ]);
                });
            }
            table_report.fnAdjustColumnSizing();
        }
	}
});

function action_buttons(virushash) {
    var buttons = [];
    buttons.push({
        url: '/app/file_scan/scan/delete/' + virushash,
        text: lang_delete
    });
    buttons.push({
        url: '/app/file_scan/scan/quarantine/' + virushash,
        text: lang_quarantine
    });
    buttons.push({
        url: '/app/file_scan/scan/whitelist/' + virushash,
        text: lang_whitelist
    });
    return clearos_anchors(buttons);
}

// vim: ts=4 syntax=javascript
