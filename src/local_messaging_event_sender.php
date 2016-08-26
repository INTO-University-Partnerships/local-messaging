<?php

defined('MOODLE_INTERNAL') || die;

require_once __DIR__ . '/../../../vendor/autoload.php';

class local_messaging_event_sender {

    /**
     * c'tor
     */
    public function __construct() {
        // empty
    }

    /**
     * send some given data to a given endpoint
     * @param \GuzzleHttp\Client $client
     * @param string $endpoint
     * @param array $data
     */
    public function send(\GuzzleHttp\Client $client, $endpoint, array $data) {
        global $CFG;
        foreach (array('djangowwwroot', 'django_urls', 'django_vle_sync_basic_auth') as $p) {
            if (!isset($CFG->$p)) {
                return;
            }
        }
        $request = $client->createRequest(
            'POST',
            $CFG->djangowwwroot . $CFG->django_urls[$endpoint],
            array(
                'auth' => $CFG->django_vle_sync_basic_auth,
                'body' => json_encode($data),
            )
        );
        $client->send($request);
    }

}
