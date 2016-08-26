<?php

use Mockery as m;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;

defined('MOODLE_INTERNAL') || die();

class local_messaging_web_test extends advanced_testcase {

    /**
     * @var object
     */
    protected $_user;

    /**
     * @var Silex\Application
     */
    protected $_app;

    /**
     * setUp
     */
    public function setUp() {
        global $CFG;

        // create a user
        $this->_user = $this->getDataGenerator()->create_user();

        // create Silex app
        $this->_app = require __DIR__ . '/../app.php';

        // add middleware to work around Moodle expecting non-empty $_GET or $_POST
        $this->_app->before(function (Request $request) {
            if (empty($_GET) && 'GET' == $request->getMethod()) {
                $_GET = $request->query->all();
            }
            if (empty($_POST) && 'POST' == $request->getMethod()) {
                $_POST = $request->request->all();
            }
        });

        // mock out messaging_model
        $this->_app['messaging_model'] = $this->_app->share(function () {
            $mock = m::mock('messaging_model');
            $mock->shouldReceive('get_all_data_requiring_synchronization')
                ->once()
                ->andReturn(array());
            return $mock;
        });

        // valid basic auth
        $CFG->django_vle_sync_basic_auth = array('vle_sync', 'valid');

        // reset the database after each test
        $this->resetAfterTest();
    }

    /**
     * tearDown
     */
    public function tearDown() {
        $_GET = array();
        $_POST = array();
        m::close();
    }

    /**
     * tests requesting the route without a basic auth header
     */
    public function test_missing_basic_auth() {
        $client = new Client($this->_app);
        $client->request('GET', '/',
            array(
                // no parameters
            ),
            array(
                // no files
            ),
            array(
                // no headers
            )
        );
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    /**
     * test requesting the route without a valid basic auth header
     */
    public function test_invalid_basic_auth() {
        $client = new Client($this->_app);
        $client->request('GET', '/',
            array(
                // no parameters
            ),
            array(
                // no files
            ),
            array(
                'HTTP_Authorization' => 'Basic ' . base64_encode('invalid'),
            )
        );
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    /**
     * tests requesting the route with a valid basic auth header
     */
    public function test_valid_basic_auth() {
        global $CFG;
        $client = new Client($this->_app);
        $client->request('GET', '/',
            array(
                // no parameters
            ),
            array(
                // no files
            ),
            array(
                'HTTP_Authorization' => 'Basic ' . base64_encode(join(':', $CFG->django_vle_sync_basic_auth)),
            )
        );
        $this->assertTrue($client->getResponse()->isOk());
    }

}
