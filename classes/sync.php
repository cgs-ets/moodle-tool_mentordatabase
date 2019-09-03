<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mentor database sync plugin
 *
 * This plugin synchronises mentors with external database table.
 *
 * @package   tool_mentordatabase
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mentordatabase tool class
 *
 * @package   tool_mentordatabase
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_mentordatabase_sync {
    /**
     * @var stdClass config for this plugin
     */
    protected $config;

    /**
     * Performs a full sync with external database.
     *
     * @param progress_trace $trace
     * @return int 0 means success, 1 db connect failure, 4 db read failure
     */
    public function sync(progress_trace $trace) {
        global $DB;

        $this->config = get_config('tool_mentordatabase');

        // Check if it is configured.
        if (empty($this->config->dbtype) || empty($this->config->dbhost)) {
            $trace->finished();
            return 1;
        }

        $trace->output('Starting mentor synchronisation...');

        // We may need a lot of memory here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        // Set some vars for better code readability.
        $mentortable           = trim($this->config->remotementortable);
        $localuserfield        = trim($this->config->localuserfield);
        $remoteuserfield       = strtolower(trim($this->config->remoteuserfield));
        $remotementoridfield   = strtolower(trim($this->config->remotementoridfield));
        $removeaction          = trim($this->config->removeaction); // 0 = remove, 1 = keep.

        // Get the roleid we're going to assign.
        $roleid = $this->config->role;

        if (empty($mentortable) || empty($localuserfield) || empty($remoteuserfield) || empty($remotementoridfield) ||
            empty($roleid)) {
            $trace->output('Plugin config not complete.');
            $trace->finished();
            return 1;
        }

        if (!$extdb = $this->db_init()) {
            $trace->output('Error while communicating with external mentor database');
            $trace->finished();
            return 1;
        }

        // Sanity check - make sure external table has the expected number of records before we trigger the sync.
        $hasenoughrecords = false;
        $count = 0;
        $minrecords = $this->config->minrecords;
        if (!empty($minrecords)) {
            $sql = "SELECT count(*) FROM $mentortable";
            if ($rs = $extdb->Execute($sql)) {
                if (!$rs->EOF) {
                    while ($fields = $rs->FetchRow()) {
                        $count = array_pop($fields);
                        if ($count > $minrecords) {
                            $hasenoughrecords = true;
                        }
                    }
                }
            }
        }
        if (!$hasenoughrecords) {
            $trace->output("Failed to sync because the external db returned $count records and the minimum
                required is $minrecords");
            $trace->finished();
            return 1;
        }

        // Get list of current mentor relationships.
        $trace->output('Indexing current mentor roles assignments');

        $sql = "SELECT ra.userid as mentorid, c.instanceid as studentid, ra.id as contextid
        FROM {role_assignments} ra
        INNER JOIN {context} c ON ra.contextid = c.id
        WHERE ra.roleid = ?
        AND c.contextlevel = ".CONTEXT_USER;
        $mentorrecords = $DB->get_recordset_sql($sql, array($roleid));

        // Index the current parent to student relationships in an associative array, keyed mentorid_studentid.
        $currentmentors = array();
        foreach ($mentorrecords as $record) {
            $key = $record->mentorid . "_" . $record->studentid;
            $currentmentors[$key] = $record;
        }
        $mentorrecords->close();

        // Get records from the external database and assign mentors.
        $trace->output('Starting mentor database user sync');
        $sql = $this->db_get_sql($mentortable);
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($fields = $rs->FetchRow()) {
                    $fields = array_change_key_case($fields, CASE_LOWER);
                    $fields = $this->db_decode($fields);
                    $fields[$remoteuserfield] = trim($fields[$remoteuserfield]);
                    $fields[$remotementoridfield] = trim($fields[$remotementoridfield]);

                    if (empty($fields[$remoteuserfield]) || empty($fields[$remotementoridfield])) {
                        $trace->output('error: invalid external mentor record, user fields is mandatory: '
                            . json_encode($fields), 1);
                        continue;
                    }

                    $rowdesc = $fields[$remotementoridfield] . " => " . $fields[$remoteuserfield];
                    $usersearch[$localuserfield] = $fields[$remoteuserfield];
                    if (!$student = $DB->get_record('user', $usersearch, 'id', IGNORE_MULTIPLE)) {
                        $trace->output("error: skipping '$rowdesc' due to unknown user $localuserfield
                            '$fields[$remoteuserfield]'", 1);
                        continue;
                    }
                    $usersearch[$localuserfield] = $fields[$remotementoridfield];
                    if (!$mentor = $DB->get_record('user', $usersearch, 'id', IGNORE_MULTIPLE)) {
                        $trace->output("error: skipping '$rowdesc' due to unknown user $localuserfield
                            '$fields[$remotementoridfield]'", 1);
                        continue;
                    }

                    $key = $mentor->id . "_" . $student->id;
                    if (isset($currentmentors[$key])) {
                        // This mentor relationship already exists.
                        $trace->output('Mentor role already assigned: ' . $key . ' (mentorid_studentid)');
                        unset($currentmentors[$key]);
                    } else {
                        // Create the relationship.
                        $trace->output('Assigning a mentor role: ' . $key . ' (mentorid_studentid)');
                        $usercontext = context_user::instance($student->id);
                        role_assign($roleid, $mentor->id, $usercontext->id);
                    }
                }
            }
        }
        $extdb->Close();

        // Unassign remaining mentor roles.
        $trace->output('Unassigning removed mentors');
        foreach ($currentmentors as $key => $cr) {
            $trace->output('Unassigning: ' . $key . ' (mentorid_studentid)');
            $usercontext = context_user::instance($cr->studentid);
            role_unassign($roleid, $cr->mentorid, $usercontext->id);
        }

        $trace->finished();

        return 0;
    }

    /**
     * Test plugin settings, print info to output.
     */
    public function test_settings() {
        global $CFG, $OUTPUT;

        // NOTE: this is not localised intentionally, admins are supposed to understand English at least a bit...

        raise_memory_limit(MEMORY_HUGE);

        $this->config = get_config('tool_mentordatabase');

        $mentortable = $this->config->remotementortable;

        if (empty($mentortable)) {
            echo $OUTPUT->notification('External mentor table not specified.', 'notifyproblem');
            return;
        }

        $olddebug = $CFG->debug;
        $olddisplay = ini_get('display_errors');
        ini_set('display_errors', '1');
        $CFG->debug = DEBUG_DEVELOPER;
        $olddebugdb = $this->config->debugdb;
        $this->config->debugdb = 1;
        error_reporting($CFG->debug);

        $adodb = $this->db_init();

        if (!$adodb or !$adodb->IsConnected()) {
            $this->config->debugdb = $olddebugdb;
            $CFG->debug = $olddebug;
            ini_set('display_errors', $olddisplay);
            error_reporting($CFG->debug);
            ob_end_flush();

            echo $OUTPUT->notification('Cannot connect the database.', 'notifyproblem');
            return;
        }

        if (!empty($mentortable)) {
            $rs = $adodb->Execute("SELECT *
                                     FROM $mentortable");
            if (!$rs) {
                echo $OUTPUT->notification('Can not read external mentor table.', 'notifyproblem');

            } else if ($rs->EOF) {
                echo $OUTPUT->notification('External mentor table is empty.', 'notifyproblem');
                $rs->Close();

            } else {
                $fieldsobj = $rs->FetchObj();
                $columns = array_keys((array)$fieldsobj);

                echo $OUTPUT->notification('External mentor table contains following columns:<br />'.
                    implode(', ', $columns), 'notifysuccess');
                $rs->Close();
            }
        }

        $adodb->Close();

        $this->config->debugdb = $olddebugdb;
        $CFG->debug = $olddebug;
        ini_set('display_errors', $olddisplay);
        error_reporting($CFG->debug);
        ob_end_flush();
    }

    /**
     * Tries to make connection to the external database.
     *
     * @return null|ADONewConnection
     */
    public function db_init() {
        global $CFG;

        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        // Connect to the external database (forcing new connection).
        $extdb = ADONewConnection($this->config->dbtype);
        if ($this->config->debugdb) {
            $extdb->debug = true;
            ob_start(); // Start output buffer to allow later use of the page headers.
        }

        // The dbtype my contain the new connection URL, so make sure we are not connected yet.
        if (!$extdb->IsConnected()) {
            $result = $extdb->Connect($this->config->dbhost, $this->config->dbuser, $this->config->dbpass,
                $this->config->dbname, true);
            if (!$result) {
                return null;
            }
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($this->config->dbsetupsql) {
            $extdb->Execute($this->config->dbsetupsql);
        }
        return $extdb;
    }

    /**
     * Encode text.
     *
     * @param string $text
     * @return string
     */
    protected function db_encode($text) {
        $dbenc = $this->config->dbencoding;
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_encode($value);
            }
            return $text;
        } else {
            return core_text::convert($text, 'utf-8', $dbenc);
        }
    }

    /**
     * Decode text.
     *
     * @param string $text
     * @return string
     */
    protected function db_decode($text) {
        $dbenc = $this->config->dbencoding;
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_decode($value);
            }
            return $text;
        } else {
            return core_text::convert($text, $dbenc, 'utf-8');
        }
    }

    /**
     * Generate SQL required based on params.
     *
     * @param string $table - name of table
     * @param array $conditions - conditions for select.
     * @param array $fields - fields to return
     * @param boolean $distinct
     * @param string $sort
     * @return string
     */
    protected function db_get_sql($table, $conditions = array(), $fields = array(), $distinct = false, $sort = "") {
        $fields = $fields ? implode(',', $fields) : "*";
        $where = array();
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $value = $this->db_encode($this->db_addslashes($value));

                $where[] = "$key = '$value'";
            }
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        $sort = $sort ? "ORDER BY $sort" : "";
        $distinct = $distinct ? "DISTINCT" : "";
        $sql = "SELECT $distinct $fields
                  FROM $table
                 $where
                  $sort";

        return $sql;
    }

    /**
     * Add slashes to text.
     *
     * @param string $text
     * @return string
     */
    protected function db_addslashes($text) {
        // Use custom made function for now - it is better to not rely on adodb or php defaults.
        if ($this->config->dbsybasequoting) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }
}



