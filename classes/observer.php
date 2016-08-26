<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/local_messaging_event_sender.php';

class local_messaging_observer {

    /**
     * @var \GuzzleHttp\Client
     */
    public static $client = null;

    /**
     * @global moodle_database $DB
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $DB;
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $lmes = new local_messaging_event_sender();
            $lmes->send($client, 'create_course', array(
                'vle_course_id' => $vle_course_id,
                'name' => $event->other['fullname'],
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @global moodle_database $DB
     * @param \core\event\course_updated $event
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $DB;
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $old_vle_course_id = trim($event->other['old_vle_course_id']);
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $lmes = new local_messaging_event_sender();

            // if old_vle_course_id is empty and vle_course_id is non-empty, then create
            if (empty($old_vle_course_id) && !empty($vle_course_id)) {
                $lmes->send($client, 'create_course', array(
                    'vle_course_id' => $vle_course_id,
                    'name' => $event->other['fullname'],
                ));
                return;
            }

            // if old_vle_course_id is non-empty and vle_course_id is empty, then delete
            if (!empty($old_vle_course_id) && empty($vle_course_id)) {
                $lmes->send($client, 'delete_course', array(
                    'vle_course_id' => $old_vle_course_id,
                ));
                return;
            }

            // otherwise, update
            $lmes->send($client, 'update_course', array(
                'old_vle_course_id' => $old_vle_course_id,
                'vle_course_id' => $vle_course_id,
                'name' => $event->other['fullname'],
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $course = $event->get_record_snapshot('course', $event->objectid);
            $lmes = new local_messaging_event_sender();
            $lmes->send($client, 'delete_course', array(
                'vle_course_id' => trim($course->idnumber),
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        self::_course_membership($event, 'add_course_members');
    }

    /**
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        self::_course_membership($event, 'remove_course_members');
    }

    /**
     * @global moodle_database $DB
     * @param \core\event\role_assigned $event
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        self::_tutor_status($event, 'add_tutor');
    }

    /**
     * @param \core\event\role_unassigned $event
     */
    public static function role_unassigned(\core\event\role_unassigned $event) {
        self::_tutor_status($event, 'remove_tutor');
    }

    /**
     * @global moodle_database $DB
     * @param \core\event\group_created $event
     */
    public static function group_created(\core\event\group_created $event) {
        global $DB;
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $group = $event->get_record_snapshot('groups', $event->objectid);
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $lmes = new local_messaging_event_sender();
            $lmes->send($client, 'create_group', array(
                'vle_course_id' => $vle_course_id,
                'vle_group_id' => trim($group->idnumber),
                'name' => $group->name,
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @global moodle_database $DB
     * @param \core\event\group_updated $event
     */
    public static function group_updated(\core\event\group_updated $event) {
        global $DB;
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $group = $event->get_record_snapshot('groups', $event->objectid);
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $old_vle_group_id = trim($event->other['old_vle_group_id']);
            $vle_group_id = trim($group->idnumber);
            $lmes = new local_messaging_event_sender();

            // if old_vle_group_id is empty and vle_group_id is non-empty, then create
            if (empty($old_vle_group_id) && !empty($vle_group_id)) {
                $lmes->send($client, 'create_group', array(
                    'vle_course_id' => $vle_course_id,
                    'vle_group_id' => $vle_group_id,
                    'name' => $group->name,
                ));
                return;
            }

            // if old_vle_group_id is non-empty and vle_group_id is empty, then delete
            if (!empty($old_vle_group_id) && empty($vle_group_id)) {
                $lmes->send($client, 'delete_group', array(
                    'vle_course_id' => $vle_course_id,
                    'vle_group_id' => $old_vle_group_id,
                ));
                return;
            }

            // otherwise, update
            $lmes->send($client, 'update_group', array(
                'vle_course_id' => $vle_course_id,
                'old_vle_group_id' => $old_vle_group_id,
                'vle_group_id' => $vle_group_id,
                'name' => $group->name,
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @global moodle_database $DB
     * @param \core\event\group_deleted $event
     */
    public static function group_deleted(\core\event\group_deleted $event) {
        global $DB;
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $group = $event->get_record_snapshot('groups', $event->objectid);
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $lmes = new local_messaging_event_sender();
            $lmes->send($client, 'delete_group', array(
                'vle_course_id' => $vle_course_id,
                'vle_group_id' => $group->idnumber,
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @param \core\event\group_member_added $event
     */
    public static function group_member_added(\core\event\group_member_added $event) {
        self::_group_membership($event, 'add_group_members');
    }

    /**
     * @param \core\event\group_member_removed $event
     */
    public static function group_member_removed(\core\event\group_member_removed $event) {
        self::_group_membership($event, 'remove_group_members');
    }

    /**
     * @param \core\event\base $event
     * @param string $endpoint
     */
    protected static function _course_membership(\core\event\base $event, $endpoint) {
        global $DB;
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $lmes = new local_messaging_event_sender();
            $lmes->send($client, $endpoint, array(
                'vle_course_id' => $vle_course_id,
                'usernames' => array($DB->get_field('user', 'username', array(
                    'id' => $event->relateduserid,
                    'deleted' => 0,
                ))),
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @global moodle_database $DB
     * @param \core\event\base $event
     * @param string $endpoint
     */
    protected static function _tutor_status(\core\event\base $event, $endpoint) {
        global $DB;
        if (!($event->get_context() instanceof context_course)) {
            return;
        }
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $roleid = $DB->get_field('role', 'id', array('shortname' => 'tutor'));
            if (empty($roleid) || ($event->objectid != $roleid)) {
                return;
            }
            $lmes = new local_messaging_event_sender();
            $lmes->send($client, $endpoint, array(
                'vle_course_id' => $vle_course_id,
                'username' => $DB->get_field('user', 'username', array(
                    'id' => $event->relateduserid,
                    'deleted' => 0,
                )),
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @global moodle_database $DB
     * @param \core\event\base $event
     * @param string $endpoint
     */
    protected static function _group_membership(\core\event\base $event, $endpoint) {
        global $DB;
        try {
            $client = empty(self::$client) ? new \GuzzleHttp\Client() : self::$client;
            $group = $event->get_record_snapshot('groups', $event->objectid);
            $vle_course_id = trim($DB->get_field('course', 'idnumber', array('id' => $event->courseid), MUST_EXIST));
            $lmes = new local_messaging_event_sender();
            $lmes->send($client, $endpoint, array(
                'vle_course_id' => $vle_course_id,
                'vle_group_id' => trim($group->idnumber),
                'usernames' => array($DB->get_field('user', 'username', array(
                    'id' => $event->relateduserid,
                    'deleted' => 0,
                ))),
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

}
