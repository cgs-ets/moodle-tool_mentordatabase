# Mentor external database sync tool for Moodle
[![Build Status](https://travis-ci.org/cgs-ets/moodle-tool_mentordatabase.svg?branch=master)](https://travis-ci.org/cgs-ets/moodle-tool_mentordatabase)

This plugin syncs parent/mentor relationships using an external database table.

Author
--------
Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>


Features
--------
* Can be triggered via CLI and/or scheduled task.
* Syncronises parent/mentor relationships


Installation
------------

1. The plugin assumes that a parent/mentor role has been configured on your Moodle instance as defined here: https://docs.moodle.org/en/Parent_role

2. Download the plugin or install it by using git to clone it into your source:

   ```sh
   git clone git@github.com:cgs-ets/moodle-tool_mentordatabase.git admin/tool/mentordatabase
   ```

3. Then run the Moodle upgrade

Setting up the database and sync (How to)
-----------------------------------------
Only a single table/view is required in the external database which contains a record for every parent/student combination. If the table is large it is a good idea to make sure appropriate indexes have been created:

* The table/view must have the following minimum fields.
  * A unique mentor/parent identifier
  * A unique user identifier for the child

* The identifiers must match one of the following fields.
  * the "idnumber" field in Moodle's user table (varchar 255), which is manually specified as the "ID number" when editing a user's profile
  * the "username" field in Moodle's user table (varchar 100), which is manually specified as the "Username" when editing a user's profile
  * the "email" field in Moodle's user table (varchar 100), which is manually specified as the "Email address" when editing a user's profile
  * the "id" field in Moodle's user table (int 10), which is based on user creation order

* In Moodle, go to Site administration > Plugins > Admin tools > Mentor external database > Settings.
  * In the top panel, select the database type (make sure you have the necessary configuration in PHP for that type) and then supply the information to connect to the database.
  * role - The role select contains a list of roles that can be applied at the user level. Select the mentor/parent role that you want to assign.
  * localuserfield - in Moodle the name of the field in the user profile that uniquely identified the user (e.g., idnumber).
  * remotementortable - the name of the remote table/view.
  * remoteuserfield - the name of the column in the external database table that uniquiely identifies the child user.
  * remotementoridfield - the name of the column in the external database table that uniquiely identifies the mentor/parent user.
  * removeaction - Select whether to remove or keep mentor relationships that exist in moodle but do not appear in the external database table.


Support
-------

If you have issues please log them in github here

https://github.com/cgs-ets/moodle-tool_mentordatabase/issues

