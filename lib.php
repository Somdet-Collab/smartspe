<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Library of functions and constants for module smartspe
 *
 * @package    mod_smartspe
 * @copyright  2025 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add smartspe instance.
 *
 * Called when a new instance of the module is created.
 *
 * @param stdClass $data An object from the form in mod_form.php
 * @param mod_smartspe_mod_form $mform
 * @return int new smartspe instance id
 */
function smartspe_add_instance($data, $mform = null) 
{
    global $DB;

    // Add created time.
    $data->timecreated = time();

    // Insert new record into the module table.
    $id = $DB->insert_record('smartspe', $data);

    // Return the new instance id.
    return $id;
}

/**
 * Update smartspe instance.
 *
 * Called when an existing instance is updated.
 *
 * @param stdClass $data An object from the form in mod_form.php
 * @param smartspe_mod_form $mform
 * @return bool true on success, false otherwise
 */
function smartspe_update_instance($data, $mform = null) 
{
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance; // Important: set the correct id.

    return $DB->update_record('smartspe', $data);
}

/**
 * Delete smartspe instance.
 *
 * Called when an instance of the module is deleted.
 *
 * @param int $id ID of the module instance
 * @return bool true on success, false otherwise
 */
function smartspe_delete_instance($id) 
{
    global $DB;

    if (!$record = $DB->get_record('smartspe', ['id' => $id])) 
    {
        return false;
    }

    // Delete dependent records first if any (like evaluations, teams, etc.)
    // Example:
    // $DB->delete_records('smartspe_evaluations', ['smartspeid' => $id]);
    // $DB->delete_records('smartspe_teams', ['smartspeid' => $id]);

    // Delete main instance.
    $DB->delete_records('smartspe', ['id' => $id]);

    return true;
}

/**
 * Supports feature detection.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if module supports feature, null if unknown
 */
function smartspe_supports($feature) 
{
    switch ($feature) 
    {
        case FEATURE_MOD_INTRO:          return true;
        case FEATURE_SHOW_DESCRIPTION:   return true;
        case FEATURE_BACKUP_MOODLE2:     return true;
        default:                         return null;
    }
}

/**
 * Extend settings navigation with smartspe specific settings.
 *
 * @param settings_navigation $settingsnav
 * @param navigation_node $smartspe_node
 */
function smartspe_extend_settings_navigation($settingsnav, $smartspe_node) 
{
    // Example: add a custom link to your plugin page.
    // $url = new moodle_url('/mod/smartspe/custom.php', ['id' => $smartspe_node->id]);
}