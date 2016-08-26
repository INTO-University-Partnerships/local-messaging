<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(

    array(
        'eventname' => '\core\event\course_created',
        'callback' => 'local_messaging_observer::course_created',
    ),

    array(
        'eventname' => '\core\event\course_updated',
        'callback' => 'local_messaging_observer::course_updated',
    ),

    array(
        'eventname' => '\core\event\course_deleted',
        'callback' => 'local_messaging_observer::course_deleted',
    ),

    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => 'local_messaging_observer::user_enrolment_created',
    ),

    array(
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => 'local_messaging_observer::user_enrolment_deleted',
    ),

    array(
        'eventname' => '\core\event\role_assigned',
        'callback' => 'local_messaging_observer::role_assigned',
    ),

    array(
        'eventname' => '\core\event\role_unassigned',
        'callback' => 'local_messaging_observer::role_unassigned',
    ),

    array(
        'eventname' => '\core\event\group_created',
        'callback' => 'local_messaging_observer::group_created',
    ),

    array(
        'eventname' => '\core\event\group_updated',
        'callback' => 'local_messaging_observer::group_updated',
    ),

    array(
        'eventname' => '\core\event\group_deleted',
        'callback' => 'local_messaging_observer::group_deleted',
    ),

    array(
        'eventname' => '\core\event\group_member_added',
        'callback' => 'local_messaging_observer::group_member_added',
    ),

    array(
        'eventname' => '\core\event\group_member_removed',
        'callback' => 'local_messaging_observer::group_member_removed',
    ),

);
