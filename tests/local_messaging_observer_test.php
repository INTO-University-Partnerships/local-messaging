<?php

use Mockery as m;

defined('MOODLE_INTERNAL') || die();

class local_messaging_observer_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_roleid;

    /**
     * @var local_messaging_observer
     */
    protected $_cut;

    /**
     * setUp
     */
    protected function setUp() {
        global $CFG;

        $CFG->djangowwwroot = 'http://some.django.site.com';
        $CFG->django_vle_sync_basic_auth = array('username', 'password');
        $CFG->django_urls = array(
            'create_course' => '/messaging_vle/create/course/',
            'update_course' => '/messaging_vle/update/course/',
            'delete_course' => '/messaging_vle/delete/course/',
            'add_course_members' => '/messaging_vle/add/course/members/',
            'remove_course_members' => '/messaging_vle/remove/course/members/',
            'add_tutor' => '/messaging_vle/add/tutor/',
            'remove_tutor' => '/messaging_vle/remove/tutor/',
            'create_group' => '/messaging_vle/create/group/',
            'update_group' => '/messaging_vle/update/group/',
            'delete_group' => '/messaging_vle/delete/group/',
            'add_group_members' => '/messaging_vle/add/group/members/',
            'remove_group_members' => '/messaging_vle/remove/group/members/',
        );

        $this->_roleid = create_role('Tutor', 'tutor', 'Description');

        $this->_cut = new local_messaging_observer();
        $this->resetAfterTest();
    }

    /**
     * tearDown
     */
    public function tearDown() {
        m::close();
    }

    /**
     * tests instantiation
     */
    public function test_instantiation() {
        $this->assertInstanceOf('local_messaging_observer', $this->_cut);
    }

    /**
     * tests creating a course uses Guzzle to send data to Django
     */
    public function test_create_course() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create the course to trigger the event
        $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));
    }

    /**
     * tests updating an existing course with an empty vle_course_id to a non-empty vle_course_id actually invokes
     * the 'create_course' endpoint (and not the 'update_course' endpoint)
     */
    public function test_update_course_to_set_non_empty_vle_course_id() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => '',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when updating a course
        $data = array(
            'vle_course_id' => 'id002',
            'name' => 'Course full name 002',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // actually create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => '',
            'fullname' => 'Course full name 001',
        ));

        // update course to trigger the event
        $course->idnumber = 'id002';
        $course->fullname = 'Course full name 002';
        update_course($course);
    }

    /**
     * tests updating an existing course with a non-empty vle_course_id to an empty vle_course_id actually invokes
     * the 'delete_course' endpoint (and not the 'update_course' endpoint)
     */
    public function test_update_course_to_set_empty_vle_course_id() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when updating a course
        $data = array(
            'vle_course_id' => 'id001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['delete_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // actually create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // update course to trigger the event
        $course->idnumber = '';
        $course->fullname = 'Course full name 002';
        update_course($course);
    }

    /**
     * tests updating a course uses Guzzle to send data to Django
     */
    public function test_update_course() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course (not what's under test, but we have to create a course before we can update it)
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when updating a course
        $data = array(
            'old_vle_course_id' => 'id001',
            'vle_course_id' => 'id002',
            'name' => 'Course full name 002',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['update_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // actually create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // update course to trigger the event
        $course->idnumber = 'id002';
        $course->fullname = 'Course full name 002';
        update_course($course);
    }

    /**
     * tests deleting a course uses Guzzle to send data to Django
     */
    public function test_delete_course() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course (not what's under test, but we have to create a course before we can update it)
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when deleting a course
        $data = array(
            'vle_course_id' => 'id001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['delete_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // actually create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // delete the course to trigger the event
        ob_start();
        delete_course($course);
        ob_end_clean();
    }

    /**
     * tests enrolling a user uses Guzzle to send data to Django
     */
    public function test_enrol_user() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when enrolling a user on a course
        $data = array(
            'vle_course_id' => 'id001',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_course_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a user
        $user = $this->getDataGenerator()->create_user(array(
            'username' => 'mike.mcgowan',
        ));

        // enrol the user to trigger the event
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
    }

    /**
     * tests unenrolling a user uses Guzzle to send data to Django
     * @global moodle_database $DB
     */
    public function test_unenrol_user() {
        global $DB, $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when enrolling a user on a course
        $data = array(
            'vle_course_id' => 'id001',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_course_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when unenrolling a user from a course
        $data = array(
            'vle_course_id' => 'id001',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['remove_course_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a user
        $user = $this->getDataGenerator()->create_user(array(
            'username' => 'mike.mcgowan',
        ));

        // enrol the user
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // unenrol the user to trigger the event
        /** @var enrol_manual_plugin */
        $plugin = enrol_get_plugin('manual');
        $instances = $DB->get_records('enrol', array(
            'courseid' => $course->id,
            'enrol' => 'manual',
        ));
        $instance = reset($instances);
        $plugin->unenrol_user($instance, $user->id);
    }

    /**
     * tests adding a tutor uses Guzzle to send data to Django
     */
    public function test_add_tutor() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when assigning a role within a course
        $data = array(
            'vle_course_id' => 'id001',
            'username' => 'mike.mcgowan',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_tutor'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a user
        $user = $this->getDataGenerator()->create_user(array(
            'username' => 'mike.mcgowan',
        ));

        // assign the role to trigger the event
        role_assign($this->_roleid, $user->id, context_course::instance($course->id));
    }

    /**
     * tests removing a tutor uses Guzzle to send data to Django
     */
    public function test_remove_tutor() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when assigning a role within a course
        $data = array(
            'vle_course_id' => 'id001',
            'username' => 'mike.mcgowan',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_tutor'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when unassigning a role within a course
        $data = array(
            'vle_course_id' => 'id001',
            'username' => 'mike.mcgowan',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['remove_tutor'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a user
        $user = $this->getDataGenerator()->create_user(array(
            'username' => 'mike.mcgowan',
        ));

        // get the course context
        $context = context_course::instance($course->id);

        // assign the role
        role_assign($this->_roleid, $user->id, $context->id);

        // unassign the role to trigger the event
        role_unassign($this->_roleid, $user->id, $context->id);
    }

    /**
     * tests creating a group uses Guzzle to send data to Django
     */
    public function test_create_group() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when creating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'name' => 'Group name 001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create the group to trigger the event
        $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id,
            'idnumber' => 'id001a',
            'name' => 'Group name 001a',
        ));
    }

    /**
     * tests updating an existing group with an empty vle_group_id to a non-empty vle_group_id actually invokes
     * the 'create_group' endpoint (and not the 'update_group' endpoint)
     */
    public function test_update_group_to_set_non_empty_vle_group_id() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when creating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => '',
            'name' => 'Group name 001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when updating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001b',
            'name' => 'Group name 001b',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a group
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id,
            'idnumber' => '',
            'name' => 'Group name 001a',
        ));

        // update the group to trigger the event
        $group->idnumber = 'id001b';
        $group->name = 'Group name 001b';
        groups_update_group($group);
    }

    /**
     * tests updating an existing group with a non-empty vle_group_id to an empty vle_group_id actually invokes
     * the 'delete_group' endpoint (and not the 'update_group' endpoint)
     */
    public function test_update_group_to_set_empty_vle_group_id() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when creating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'name' => 'Group name 001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when updating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['delete_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a group
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id,
            'idnumber' => 'id001a',
            'name' => 'Group name 001a',
        ));

        // update the group to trigger the event
        $group->idnumber = '';
        $group->name = 'Group name 001b';
        groups_update_group($group);
    }

    /**
     * tests updating a group uses Guzzle to send data to Django
     */
    public function test_update_group() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when creating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'name' => 'Group name 001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when updating a group
        $data = array(
            'vle_course_id' => 'id001',
            'old_vle_group_id' => 'id001a',
            'vle_group_id' => 'id001b',
            'name' => 'Group name 001b',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['update_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a group
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id,
            'idnumber' => 'id001a',
            'name' => 'Group name 001a',
        ));

        // update the group to trigger the event
        $group->idnumber = 'id001b';
        $group->name = 'Group name 001b';
        groups_update_group($group);
    }

    /**
     * tests deleting a group uses Guzzle to send data to Django
     */
    public function test_delete_group() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when creating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'name' => 'Group name 001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when deleting a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['delete_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a group
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id,
            'idnumber' => 'id001a',
            'name' => 'Group name 001a',
        ));

        // delete the group to trigger the event
        groups_delete_group($group);
    }

    /**
     * tests adding a group member uses Guzzle to send data to Django
     */
    public function test_group_member_added() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when enrolling a user on a course
        $data = array(
            'vle_course_id' => 'id001',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_course_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when creating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'name' => 'Group name 001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when adding a group member
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_group_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a group
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id,
            'idnumber' => 'id001a',
            'name' => 'Group name 001a',
        ));

        // create a user
        $user = $this->getDataGenerator()->create_user(array(
            'username' => 'mike.mcgowan',
        ));

        // enrol the user on the course (otherwise they won't get added to the group)
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // add the user to the group to trigger the event
        groups_add_member($group, $user);
    }

    /**
     * tests removing a group member uses Guzzle to send data to Django
     */
    public function test_group_member_removed() {
        global $CFG;

        $request = m::mock('\GuzzleHttp\Message\Request');
        local_messaging_observer::$client = $client = m::mock('\GuzzleHttp\Client');

        // mock out event observer invoked when creating a course
        $data = array(
            'vle_course_id' => 'id001',
            'name' => 'Course full name 001',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_course'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when enrolling a user on a course
        $data = array(
            'vle_course_id' => 'id001',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_course_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);
        // mock out event observer invoked when creating a group
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'name' => 'Group name 001a',
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['create_group'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when adding a group member
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['add_group_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // mock out event observer invoked when removing a group member
        $data = array(
            'vle_course_id' => 'id001',
            'vle_group_id' => 'id001a',
            'usernames' => array('mike.mcgowan'),
        );
        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['remove_group_members'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        // create a course
        $course = $this->getDataGenerator()->create_course(array(
            'idnumber' => 'id001',
            'fullname' => 'Course full name 001',
        ));

        // create a group
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id,
            'idnumber' => 'id001a',
            'name' => 'Group name 001a',
        ));

        // create a user
        $user = $this->getDataGenerator()->create_user(array(
            'username' => 'mike.mcgowan',
        ));

        // enrol the user on the course (otherwise they won't get added to the group)
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // add the user to the group
        groups_add_member($group, $user);

        // remove the user from the group to trigger the event
        groups_remove_member($group, $user);
    }

}
