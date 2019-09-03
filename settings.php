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
 * Mentor database plugin settings and presets.
 *
 * @package   tool_mentordatabase
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


if ($hassiteconfig) {

    // Add a new category under tools.
    $ADMIN->add('tools',
        new admin_category('tool_mentordatabase', get_string('pluginname', 'tool_mentordatabase')));

    $settings = new admin_settingpage('tool_mentordatabase_settings', new lang_string('settings', 'tool_mentordatabase'),
        'moodle/site:config', false);

    // Add the settings page.
    $ADMIN->add('tool_mentordatabase', $settings);

    // Add the test settings page.
    $ADMIN->add('tool_mentordatabase',
            new admin_externalpage('tool_mentordatabase_test', get_string('testsettings', 'tool_mentordatabase'),
                $CFG->wwwroot . '/' . $CFG->admin . '/tool/mentordatabase/test_settings.php'));

    // General settings.
    $settings->add(new admin_setting_heading('tool_mentordatabase_settings', '',
        get_string('pluginname_desc', 'tool_mentordatabase')));

    $settings->add(new admin_setting_heading('tool_mentordatabase_exdbheader',
        get_string('settingsheaderdb', 'tool_mentordatabase'), ''));

    $options = array('', "pdo", "pdo_mssql", "pdo_sqlsrv", "access", "ado_access", "ado", "ado_mssql", "borland_ibase",
        "csv", "db2", "fbsql", "firebird", "ibase", "informix72", "informix", "mssql", "mssql_n", "mssqlnative", "mysql",
        "mysqli", "mysqlt", "oci805", "oci8", "oci8po", "odbc", "odbc_mssql", "odbc_oracle", "oracle", "postgres64",
        "postgres7", "postgres", "proxy", "sqlanywhere", "sybase", "vfp");
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect('tool_mentordatabase/dbtype',
        get_string('dbtype', 'tool_mentordatabase'),
        get_string('dbtype_desc', 'tool_mentordatabase'), '', $options));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/dbhost',
        get_string('dbhost', 'tool_mentordatabase'),
        get_string('dbhost_desc', 'tool_mentordatabase'), ''));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/dbuser',
        get_string('dbuser', 'tool_mentordatabase'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('tool_mentordatabase/dbpass',
        get_string('dbpass', 'tool_mentordatabase'), '', ''));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/dbname',
        get_string('dbname', 'tool_mentordatabase'),
        get_string('dbname_desc', 'tool_mentordatabase'), ''));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/dbencoding',
        get_string('dbencoding', 'tool_mentordatabase'), '', 'utf-8'));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/dbsetupsql',
        get_string('dbsetupsql', 'tool_mentordatabase'),
        get_string('dbsetupsql_desc', 'tool_mentordatabase'), ''));

    $settings->add(new admin_setting_configcheckbox('tool_mentordatabase/dbsybasequoting',
        get_string('dbsybasequoting', 'tool_mentordatabase'),
        get_string('dbsybasequoting_desc', 'tool_mentordatabase'), 0));

    $settings->add(new admin_setting_configcheckbox('tool_mentordatabase/debugdb',
        get_string('debugdb', 'tool_mentordatabase'),
        get_string('debugdb_desc', 'tool_mentordatabase'), 0));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/minrecords',
        get_string('minrecords', 'tool_mentordatabase'),
        get_string('minrecords_desc', 'tool_mentordatabase'), 1));

    $settings->add(new admin_setting_heading('tool_mentordatabase_localheader',
        get_string('settingsheaderlocal', 'tool_mentordatabase'), ''));

     // Get all roles that can be assigned at the user context level and put their id's nicely into the configuration.
    $roleids = get_roles_for_contextlevels(CONTEXT_USER);
    list($insql, $inparams) = $DB->get_in_or_equal($roleids);
    $sql = "SELECT * FROM {role} WHERE id $insql";
    $roles = $DB->get_records_sql($sql, $inparams);
    $i = 1;
    foreach ($roles as $role) {
        $rolename[$i] = $role->shortname;
        $roleid[$i] = $role->id;
        $i++;
    }
    $rolenames = array_combine($roleid, $rolename);

    $settings->add(new admin_setting_configselect('tool_mentordatabase/role',
        get_string('mentorrole', 'tool_mentordatabase'), get_string('mentorrole_desc', 'tool_mentordatabase'),
        '', $rolenames));

    $options = array('id' => 'id', 'idnumber' => 'idnumber', 'email' => 'email', 'username' => 'username');
    $settings->add(new admin_setting_configselect('tool_mentordatabase/localuserfield',
        get_string('localuserfield', 'tool_mentordatabase'), '', 'idnumber', $options));

    $settings->add(new admin_setting_heading('tool_mentordatabase_remoteheader',
        get_string('settingsheaderremote', 'tool_mentordatabase'), ''));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/remotementortable',
        get_string('remotementortable', 'tool_mentordatabase'),
        get_string('remotementortable_desc', 'tool_mentordatabase'), ''));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/remoteuserfield',
        get_string('remoteuserfield', 'tool_mentordatabase'),
        get_string('remoteuserfield_desc', 'tool_mentordatabase'), ''));

    $settings->add(new admin_setting_configtext('tool_mentordatabase/remotementoridfield',
        get_string('remotementoridfield', 'tool_mentordatabase'),
        get_string('remotementoridfield_desc', 'tool_mentordatabase'), ''));

    $options = array(0  => get_string('removementor', 'tool_mentordatabase'),
                     1  => get_string('keepmentor', 'tool_mentordatabase'));
    $settings->add(new admin_setting_configselect('tool_mentordatabase/removeaction',
        get_string('removedaction', 'tool_mentordatabase'),
        get_string('removedaction_desc', 'tool_mentordatabase'), 0, $options));

}
