<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/messaging_model.php';

class messaging_model_test extends advanced_testcase {

    /**
     * @var messaging_model
     */
    protected $_cut;

    /**
     * setUp
     */
    public function setUp() {
        $this->_cut = new messaging_model();
        $this->resetAfterTest();
    }

    /**
     * tests instantiation
     */
    public function test_instantiation() {
        $this->assertInstanceOf('messaging_model', $this->_cut);
    }

    /**
     * tests passing an array by value
     */
    public function test_pass_array_by_value() {
        $input = [1, 2, 3];
        $f = function (array $a) {
            $count = count($a);
            for ($i = 0; $i < $count; ++$i) {
                $a[$i] = $a[$i] * $a[$i];
            }
        };
        $f($input);
        $this->assertEquals(array(1, 2, 3), $input);
    }

    /**
     * tests passing an array by reference
     */
    public function test_pass_array_by_reference() {
        $input = [1, 2, 3];
        $f = function (array &$a) {
            $count = count($a);
            for ($i = 0; $i < $count; ++$i) {
                $a[$i] = $a[$i] * $a[$i];
            }
        };
        $f($input);
        $this->assertEquals(array(1, 4, 9), $input);
    }

    /**
     * tests array_map with an associative array
     */
    public function test_array_map_associative_array() {
        $a = array(
            'one_squared' => 1,
            'two_squared' => 2,
            'three_squared' => 3,
        );
        $b = array_map(function ($f) {
            return $f * $f;
        }, $a);
        $this->assertEquals(array(
            'one_squared' => 1,
            'two_squared' => 4,
            'three_squared' => 9,
        ), $b);
    }

    /**
     * tests converting Moodle records to an indexed array
     * @global moodle_database $DB
     */
    public function test_moodle_records_to_array() {
        global $DB;
        $ids = array();
        foreach (range(1, 3) as $i) {
            $ids[$i + 1] = $this->getDataGenerator()->create_course(array(
                'fullname' => 'Course ' . ($i + 1),
            ))->id;
        }
        $courses = $DB->get_records_select('course', 'id > 1', null, 'id', 'id, fullname');
        $this->assertEquals(array(
            $ids[2] => (object)array(
                'id' => $ids[2],
                'fullname' => 'Course 2',
            ),
            $ids[3] => (object)array(
                'id' => $ids[3],
                'fullname' => 'Course 3',
            ),
            $ids[4] => (object)array(
                'id' => $ids[4],
                'fullname' => 'Course 4',
            ),
        ), $courses);
        $courses = $this->_cut->moodle_records_to_array($courses);
        $this->assertEquals(array(
            (object)array(
                'fullname' => 'Course 2',
            ),
            (object)array(
                'fullname' => 'Course 3',
            ),
            (object)array(
                'fullname' => 'Course 4',
            ),
        ), $courses);
    }

    /**
     * tests getting all data requiring synchronization
     */
    public function test_get_all_data_requiring_synchronization() {
        $all_data = $this->_cut->get_all_data_requiring_synchronization();
        $keys = array('course_kv_store', 'group_kv_store', 'course_member', 'group_member');
        $this->assertEquals($keys, array_keys($all_data));
        foreach ($keys as $key) {
            $this->assertEmpty($all_data[$key]);
        }
    }

    /**
     * tests getting courses
     */
    public function test_get_courses() {
        $seeded = array();
        $courseids = array();

        // courses with non-empty idnumbers
        foreach (range(1, 5) as $i) {
            $seeded[] = $this->getDataGenerator()->create_course(array(
                'fullname' => 'Course fullname 00' . $i,
                'idnumber' => '00' . $i,
            ));
            $courseids[] = $seeded[count($seeded) - 1]->id;
        }

        // course with an empty idnumber
        $this->getDataGenerator()->create_course(array(
            'idnumber' => '',
        ));

        // ensure the course ids are as expected
        $courses = $this->_cut->get_courses();
        sort($courseids);
        $keys = array_keys($courses);
        sort($keys);
        $this->assertEquals($courseids, $keys);
    }

    /**
     * tests getting groups
     */
    public function test_get_groups() {
        $seeded = array();
        $groupids = array();

        // a course with a non-empty idnumber
        $course1 = $this->getDataGenerator()->create_course(array(
            'name' => 'Course name 001',
            'idnumber' => '001',
        ));

        // a course with a non-empty idnumber
        $course2 = $this->getDataGenerator()->create_course(array(
            'name' => 'Course name 002',
            'idnumber' => '002',
        ));

        // a course with an empty idnumber
        $course3 = $this->getDataGenerator()->create_course(array(
            'name' => 'Course name 003',
            'idnumber' => '',
        ));

        // groups with non-empty idnumbers in course1
        foreach (range(1, 5) as $i) {
            $seeded[] = $this->getDataGenerator()->create_group(array(
                'courseid' => $course1->id,
                'name' => 'Group name 001' . chr(96 + $i),
                'idnumber' => '001' . chr(96 + $i),
            ));
            $groupids[] = $seeded[count($seeded) - 1]->id;
        }

        // groups with non-empty idnumbers in course2
        foreach (range(1, 5) as $i) {
            $seeded[] = $this->getDataGenerator()->create_group(array(
                'courseid' => $course2->id,
                'name' => 'Group name 002' . chr(96 + $i),
                'idnumber' => '002' . chr(96 + $i),
            ));
            $groupids[] = $seeded[count($seeded) - 1]->id;
        }

        // group with an empty idnumber in course1
        $this->getDataGenerator()->create_group(array(
            'courseid' => $course1->id,
            'idnumber' => '',
        ));

        // group with an empty idnumber in course2
        $this->getDataGenerator()->create_group(array(
            'courseid' => $course2->id,
            'idnumber' => '',
        ));

        // group with a non-empty idnumber in course3 (which has an empty idnumber)
        $this->getDataGenerator()->create_group(array(
            'courseid' => $course3->id,
            'idnumber' => '003a',
        ));

        // ensure the group ids are as expected
        $groups = $this->_cut->get_groups();
        sort($groupids);
        $keys = array_keys($groups);
        sort($keys);
        $this->assertEquals($groupids, $keys);
    }

    /**
     * tests getting course memberships
     * @global moodle_database $DB;
     */
    public function test_get_course_memberships() {
        global $DB;

        // get 'student' role
        $student_roleid = $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        ));

        // create 'tutor' role
        $tutor_roleid = $this->getDataGenerator()->create_role(array(
            'name' => 'Tutor',
            'shortname' => 'tutor',
        ));

        // a course with a non-empty idnumber
        $course = $this->getDataGenerator()->create_course(array(
            'name' => 'Course name 001',
            'idnumber' => '001',
        ));
        $context = context_course::instance($course->id);

        // some users who aren't course members
        foreach (range(1, 3) as $i) {
            $this->getDataGenerator()->create_user();
        }

        // 5 students
        $students = array();
        foreach (range(1, 5) as $i) {
            $student = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->role_assign($student_roleid, $student->id, $context->id);
            $students[] = $student;
        }

        // 2 tutors
        foreach (range(1, 2) as $i) {
            $tutor = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->role_assign($tutor_roleid, $tutor->id, $context->id);
            $tutors[] = $tutor;
        }

        // one user who has both roles (to ensure they're not counted twice)
        $user_with_student_and_tutor_roles = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($student_roleid, $user_with_student_and_tutor_roles->id, $context->id);
        $this->getDataGenerator()->role_assign($tutor_roleid, $user_with_student_and_tutor_roles->id, $context->id);

        // ensure the memberships are as expected
        $memberships = $this->_cut->get_course_memberships();
        $this->assertCount(count($students) + count($tutors) + 1, $memberships);

        // students
        foreach ($students as $i => $student) {
            $key = $course->id . '_' . $students[$i]->id . '_student';
            $this->assertArrayHasKey($key, $memberships);
            $this->assertFalse($memberships[$key]->is_tutor);
        }

        // tutors
        foreach ($tutors as $i => $tutor) {
            $key = $course->id . '_' . $tutors[$i]->id . '_tutor';
            $this->assertArrayHasKey($key, $memberships);
            $this->assertTrue($memberships[$key]->is_tutor);
        }

        // extra tutor with both roles
        $key = $course->id . '_' . $user_with_student_and_tutor_roles->id . '_tutor';
        $this->assertArrayHasKey($key, $memberships);
        $this->assertTrue($memberships[$key]->is_tutor);
    }

    /**
     * tests getting group memberships
     */
    public function test_get_group_memberships() {
        $seeded = array();
        $groupids = array();

        // a course with a non-empty idnumber
        $course1 = $this->getDataGenerator()->create_course(array(
            'name' => 'Course name 001',
            'idnumber' => '001',
        ));

        // a course with a non-empty idnumber
        $course2 = $this->getDataGenerator()->create_course(array(
            'name' => 'Course name 002',
            'idnumber' => '002',
        ));

        // a course with an empty idnumber
        $course3 = $this->getDataGenerator()->create_course(array(
            'name' => 'Course name 003',
            'idnumber' => '',
        ));

        // groups with non-empty idnumbers in course1
        foreach (range(1, 5) as $i) {
            $seeded[] = $this->getDataGenerator()->create_group(array(
                'courseid' => $course1->id,
                'name' => 'Group name 001' . chr(96 + $i),
                'idnumber' => '001' . chr(96 + $i),
            ));
            $groupids[] = $seeded[count($seeded) - 1]->id;
        }

        // groups with non-empty idnumbers in course2
        foreach (range(1, 5) as $i) {
            $seeded[] = $this->getDataGenerator()->create_group(array(
                'courseid' => $course2->id,
                'name' => 'Group name 002' . chr(96 + $i),
                'idnumber' => '002' . chr(96 + $i),
            ));
            $groupids[] = $seeded[count($seeded) - 1]->id;
        }

        // group with an empty idnumber in course1
        $group_empty_idnumber1 = $this->getDataGenerator()->create_group(array(
            'courseid' => $course1->id,
            'idnumber' => '',
        ));

        // group with an empty idnumber in course2
        $group_empty_idnumber2 = $this->getDataGenerator()->create_group(array(
            'courseid' => $course2->id,
            'idnumber' => '',
        ));

        // group with a non-empty idnumber in course3 (which has an empty idnumber)
        $group_course_empty_idnumber = $this->getDataGenerator()->create_group(array(
            'courseid' => $course3->id,
            'idnumber' => '003a',
        ));

        // student1 in a group in course1
        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id);
        $this->getDataGenerator()->create_group_member(array(
            'userid' => $student1->id,
            'groupid' => $groupids[0],
        ));

        // student2 in a group in course1
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id);
        $this->getDataGenerator()->create_group_member(array(
            'userid' => $student2->id,
            'groupid' => $groupids[1],
        ));

        // student3 in a group in course2
        $student3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student3->id, $course2->id);
        $this->getDataGenerator()->create_group_member(array(
            'userid' => $student3->id,
            'groupid' => $groupids[7],
        ));

        // some student in a group with an empty idnumber in course1
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->create_group_member(array(
            'userid' => $user->id,
            'groupid' => $group_empty_idnumber1->id,
        ));

        // some student in a group with an empty idnumber in course2
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        $this->getDataGenerator()->create_group_member(array(
            'userid' => $user->id,
            'groupid' => $group_empty_idnumber2->id,
        ));

        // some student in a group in course3
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course3->id);
        $this->getDataGenerator()->create_group_member(array(
            'userid' => $user->id,
            'groupid' => $group_course_empty_idnumber->id,
        ));

        // ensure the memberships are as expected
        $memberships = $this->_cut->get_group_memberships();
        $this->assertCount(3, $memberships);

        // student1 in a group in course1
        $this->assertArrayHasKey($course1->id . '_' . $groupids[0] . '_' . $student1->id, $memberships);

        // student2 in a group in course1
        $this->assertArrayHasKey($course1->id . '_' . $groupids[1] . '_' . $student2->id, $memberships);

        // student3 in a group in course2
        $this->assertArrayHasKey($course2->id . '_' . $groupids[7] . '_' . $student3->id, $memberships);
    }

}
