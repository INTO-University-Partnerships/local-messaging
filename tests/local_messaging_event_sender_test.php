<?php

use Mockery as m;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/local_messaging_event_sender.php';

class local_messaging_event_sender_test extends advanced_testcase {

    /**
     * @var local_messaging_event_sender
     */
    protected $_cut;

    /**
     * setUp
     */
    protected function setUp() {
        $this->_cut = new local_messaging_event_sender();
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
        $this->assertInstanceOf('local_messaging_event_sender', $this->_cut);
    }

    /**
     * tests sending data
     */
    public function test_send() {
        global $CFG;

        $CFG->djangowwwroot = 'http://some.django.site.com';
        $CFG->django_vle_sync_basic_auth = array('username', 'password');
        $CFG->django_urls = array(
            'foobar' => '/foo/bar/',
        );

        $data = array(
            'property1' => 'value1',
            'property2' => 'value2',
        );

        $request = m::mock('\GuzzleHttp\Message\Request');
        $client = m::mock('\GuzzleHttp\Client');

        $client->shouldReceive('createRequest')
            ->once()
            ->with('POST', $CFG->djangowwwroot . $CFG->django_urls['foobar'], array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            ))
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request);

        $this->_cut->send($client, 'foobar', $data);
    }

}
