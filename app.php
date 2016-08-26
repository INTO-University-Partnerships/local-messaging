<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Debug\ErrorHandler;

// bootstrap Moodle
require_once __DIR__ . '/../../config.php';

// create Silex app
require_once __DIR__ . '/../../vendor/autoload.php';
$app = new Silex\Application();
$app['debug'] = debugging('', DEBUG_MINIMAL);

// require the services
foreach (array(
    'messaging_model'
) as $service) {
    require __DIR__ . '/services/' . $service . '.php';
}

// define middleware
$app['middleware'] = array(
    'basic_auth' => function (Request $request) use ($app) {
        global $CFG;
        $authorization = $request->headers->get('Authorization');
        if (!$authorization) {
            throw new UnauthorizedHttpException('Basic realm="Moodle"', 'Unauthorized');
        }
        $basic_auth = base64_decode(substr($authorization, strlen('Basic ')));
        if (empty($CFG->django_vle_sync_basic_auth) || $basic_auth !== join(':', $CFG->django_vle_sync_basic_auth)) {
            throw new UnauthorizedHttpException('Basic realm="Moodle"', 'Unauthorized');
        }
    },
);

// handle errors as exceptions (prevents 200 responses on error)
ErrorHandler::register();

// serve all courses, groups, course memberships, course tutors, group memberships
$app->get('/', function () use ($app) {
    /** @var messaging_model $mm */
    $mm = $app['messaging_model'];

    // return JSON
    $data = $mm->get_all_data_requiring_synchronization();
    return $app->json($data);
})
->before($app['middleware']['basic_auth']);

// handle any exceptions
$app->error(function (Exception $e, $code) use ($app) {
    $headers = $e instanceof UnauthorizedHttpException ? $e->getHeaders() : array();
    return $app->json(array(
        'errorMessage' => $e->getMessage(),
    ), $code, $headers);
});

// return the app
return $app;
