<?php

defined('MOODLE_INTERNAL') || die();

class messaging_model {

    /**
     * c'tor
     */
    public function __construct() {
        // empty
    }

    /**
     * convert a Moodle record collection into an indexed array, removing Moodle database identifiers
     * @param array $rows
     * @return array
     */
    public function moodle_records_to_array(array &$rows) {
        return array_values(array_map(function ($row) {
            unset($row->id);
            unset($row->uniqueid);
            return $row;
        }, $rows));
    }

    /**
     * courses, groups, course memberships (including tutor status) and group memberships all require synchronization
     * @return array
     */
    public function get_all_data_requiring_synchronization() {
        // get courses, groups, course memberships, group memberships
        $courses = $this->get_courses();
        $groups = $this->get_groups();
        $course_memberships = $this->get_course_memberships();
        $group_memberships = $this->get_group_memberships();

        // return all data requiring synchronization
        return array_map('messaging_model::moodle_records_to_array', array(
            'course_kv_store' => $courses,
            'group_kv_store' => $groups,
            'course_member' => $course_memberships,
            'group_member' => $group_memberships,
        ));
    }

    /**
     * get all the courses in the database with non-empty idnumbers (vle_course_id)
     * @global moodle_database $DB
     * @return array
     */
    public function get_courses() {
        global $DB;
        $sql = <<<SQL
            SELECT c.id, c.idnumber AS vle_course_id, c.fullname AS name
            FROM {course} c
            WHERE c.id != :siteid
                AND LENGTH(c.idnumber) > 0
            ORDER BY c.idnumber
SQL;
        $params = array(
            'siteid' => SITEID,
        );
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * get all the groups in the database with non-empty idnumbers (vle_group_id) belonging to courses with non-empty idnumbers (vle_course_id)
     * @return array
     */
    public function get_groups() {
        global $DB;
        $sql = <<<SQL
            SELECT g.id, c.idnumber AS vle_course_id, g.idnumber AS vle_group_id, g.name AS name
            FROM {groups} g
            INNER JOIN {course} c
                ON c.id = g.courseid
            WHERE c.id != :siteid
                AND LENGTH(c.idnumber) > 0
                AND LENGTH(g.idnumber) > 0
            ORDER BY c.idnumber, g.idnumber
SQL;
        $params = array(
            'siteid' => SITEID,
        );
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * get all the course memberships (i.e. enrolments) belonging to courses with non-empty idnumbers (vle_course_id)
     * (course membership includes whether each user has the 'tutor' role on the course)
     * @return array
     */
    public function get_course_memberships() {
        global $DB;
        $uniqueid = $DB->sql_concat('c.id', "'_'", 'u.id', "'_'", 'r.shortname');
        $sql = <<<SQL
            SELECT $uniqueid AS uniqueid,
                u.username AS username,
                c.idnumber AS vle_course_id,
                r.shortname AS shortname
            FROM {course} c
            INNER JOIN {context} ctx
                ON ctx.instanceid = c.id
                AND ctx.contextlevel = :context_course
            INNER JOIN {role_assignments} ra
                ON ra.contextid = ctx.id
            INNER JOIN {role} r
                ON r.id = ra.roleid
                AND r.shortname IN (:role_student, :role_tutor)
            INNER JOIN {user} u
                ON u.id = ra.userid
                AND u.deleted = 0
            WHERE c.id != :siteid
                AND LENGTH(c.idnumber) > 0
            ORDER BY vle_course_id, username
SQL;
        $params = array(
            'context_course' => CONTEXT_COURSE,
            'role_student' => 'student',
            'role_tutor' => 'tutor',
            'siteid' => SITEID,
        );
        $rows = $DB->get_records_sql($sql, $params);

        // if the user is both a student and a tutor (an unlikely edge case, admittedly) then discard the student row
        $rows = array_filter($rows, function ($row) use ($rows) {
            if ($row->shortname == 'tutor') {
                return true;
            }
            if (array_key_exists(str_replace('_student', '_tutor', $row->uniqueid), $rows)) {
                return false;
            }
            return true;
        });

        // replace the role shortname (either 'student' or 'tutor') with an 'is_tutor' boolean
        $rows = array_map(function ($row) {
            $row->is_tutor = $row->shortname == 'tutor';
            unset($row->shortname);
            return $row;
        }, $rows);

        // return collection
        return $rows;
    }

    /**
     * get all the group memberships belonging to groups with non-empty idnumbers (vle_group_id)
     * (the courses the groups belong to must also have non-empty idnumbers (vle_course_id))
     * @return array
     */
    public function get_group_memberships() {
        global $DB;
        $uniqueid = $DB->sql_concat('c.id', "'_'", 'g.id', "'_'", 'u.id');
        $sql = <<<SQL
            SELECT $uniqueid AS uniqueid,
                u.username AS username,
                c.idnumber AS vle_course_id,
                g.idnumber AS vle_group_id
            FROM {groups} g
            INNER JOIN {course} c
                ON c.id = g.courseid
            INNER JOIN {groups_members} gm
                ON gm.groupid = g.id
            INNER JOIN {user} u
                ON gm.userid = u.id
                AND u.deleted = 0
            WHERE c.id != :siteid
                AND LENGTH(c.idnumber) > 0
                AND LENGTH(g.idnumber) > 0
            ORDER BY vle_course_id, vle_group_id, username
SQL;
        $params = array(
            'siteid' => SITEID,
        );
        return $DB->get_records_sql($sql, $params);
    }

}
