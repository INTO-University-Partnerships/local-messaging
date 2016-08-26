<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/messaging_model.php';

$app['messaging_model'] = $app->share(function ($app) {
    $mm = new messaging_model();
    return $mm;
});
