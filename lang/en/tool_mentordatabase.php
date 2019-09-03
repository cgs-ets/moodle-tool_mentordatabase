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
 * Strings for component 'tool_mentordatabase', language 'en'.
 *
 * @package   tool_mentordatabase
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['dbencoding'] = 'Database encoding';
$string['dbhost'] = 'Database host';
$string['dbhost_desc'] = 'Type database server IP address or host name. Use a system DSN name if using ODBC.';
$string['dbname'] = 'Database name';
$string['dbname_desc'] = 'Leave empty if using a DSN name in database host.';
$string['dbpass'] = 'Database password';
$string['dbsetupsql'] = 'Database setup command';
$string['dbsetupsql_desc'] = 'SQL command for special database setup, often used to setup communication encoding - example for MySQL and PostgreSQL: <em>SET NAMES \'utf8\'</em>';
$string['dbsybasequoting'] = 'Use sybase quotes';
$string['dbsybasequoting_desc'] = 'Sybase style single quote escaping - needed for Oracle, MS SQL and some other databases. Do not use for MySQL!';
$string['dbtype'] = 'Database driver';
$string['dbtype_desc'] = 'ADOdb database driver name, type of the external database engine.';
$string['dbuser'] = 'Database user';
$string['debugdb'] = 'Debug ADOdb';
$string['debugdb_desc'] = 'Debug ADOdb connection to external database - use when getting empty page during login. Not suitable for production sites!';
$string['localuserfield'] = 'Local user field';
$string['pluginname'] = 'Mentor external database';
$string['pluginname_desc'] = 'You can use an external database (of nearly any kind) to control your mentors.';
$string['settings'] = 'Settings';
$string['testsettingsheading'] = 'Test settings';
$string['remotementortable'] = 'Remote user mentor table';
$string['remotementortable_desc'] = 'Specify the name of the table that contains list of user mentors.';
$string['remoteuserfield'] = 'Remote user field';
$string['remoteuserfield_desc'] = 'The name of the field in the remote table that we are using to match entries in the user table.';
$string['remotementoridfield'] = 'Remote mentor id field';
$string['remotementoridfield_desc'] = 'The name of the field in the remote table that we are using to match entries in the mentor table.';
$string['settingsheaderdb'] = 'External database connection';
$string['mentorrole'] = 'Select the role to assign here';
$string['mentorrole_desc'] = 'Click on the drop down menu to select a role';
$string['settingsheaderlocal'] = 'Local field mapping';
$string['settingsheaderremote'] = 'Remote mentor sync';
$string['removementor'] = 'Remove mentor';
$string['keepmentor'] = 'Keep mentor';
$string['removedaction_desc'] = 'Select action to carry out when user disappears from external mentor source.';
$string['removedaction'] = 'External remove action';
$string['sync'] = 'Sync mentors with external database';
$string['minrecords'] = 'Minimum records';
$string['minrecords_desc'] = 'Prevent the sync from running if the number of records returned in the external table is below this number (helps to prevent removal of users when the external table is empty).';
$string['privacy:metadata'] = 'The Mentor database plugin does not store any personal data.';
$string['testsettings'] = "Test settings";