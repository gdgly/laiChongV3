<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
$api = app('Dingo\Api\Routing\Router');


$api->version('v1', ['namespace' => 'Wormhole\Http\Controllers\Api\V1'], function ($api) {
    $api->post('send_cmd/{hash}', [
        'as'   => 'api.gtw.sendCmd',
        'uses' => 'CommonController@sendCmd'
    ]);

    $api->any('test/{hash}', [
        'as'   => 'api.test',
        'uses' => 'TestController@test'
    ]);
});


$api->version('LaiChong', ['namespace' => 'Wormhole\Protocols\LcV3\Controllers\Api',

], function ($api) {

    $api->any('test/{hash}', [
        'as'   => 'api.test',
        'uses' => 'EvseController@test'
    ]);

    $api->post('startCharge/{hash}', [
        'as'   => 'api.startCharge',
        'uses' => 'EvseController@startCharge'
    ]);

    $api->post('continueCharge/{hash}', [
        'as'   => 'api.renew',
        'uses' => 'EvseController@renew'
    ]);

    $api->post('stopCharge/{hash}', [
        'as'   => 'api.stopCharge',
        'uses' => 'EvseController@stopCharge'
    ]);

    $api->post('chargeRealtime/{hash}', [
        'as'   => 'api.chargeRealtime',
        'uses' => 'EvseController@chargeRealtime'
    ]);

    $api->post('chargeRecords/{hash}', [
        'as'   => 'api.chargeRecords',
        'uses' => 'EvseController@chargeRecords'
    ]);


    $api->post('setHearbeat/{hash}', [
        'as'   => 'api.setHearbeat',
        'uses' => 'EvseController@setHearbeat'
    ]);

    $api->post('setServerInfo/{hash}', [
        'as'   => 'api.setServerInfo',
        'uses' => 'EvseController@setServerInfo'
    ]);

    $api->post('emptyTurnover/{hash}', [
        'as'   => 'api.emptyTurnover',
        'uses' => 'EvseController@emptyTurnover'
    ]);

    $api->post('setParameter/{hash}', [
        'as'   => 'api.setParameter',
        'uses' => 'EvseController@setParameter'
    ]);

    $api->post('setDateTime/{hash}', [
        'as'   => 'api.setDateTime',
        'uses' => 'EvseController@setDateTime'
    ]);




    $api->post('getHearbeat/{hash}', [
        'as'   => 'api.getHearbeat',
        'uses' => 'EvseController@getHearbeat'
    ]);

    $api->post('getMeter/{hash}', [
        'as'   => 'api.getMeter',
        'uses' => 'EvseController@getMeter'
    ]);

    $api->post('getTurnover/{hash}', [
        'as'   => 'api.getTurnover',
        'uses' => 'EvseController@getTurnover'
    ]);

    $api->post('getChannel/{hash}', [
        'as'   => 'api.getChannel',
        'uses' => 'EvseController@getChannel'
    ]);

    $api->post('getDateTime/{hash}', [
        'as'   => 'api.getDateTime',
        'uses' => 'EvseController@getDateTime'
    ]);

    $api->post('setId/{hash}', [
        'as'   => 'api.setId',
        'uses' => 'EvseController@setId'
    ]);

    $api->post('getId/{hash}', [
        'as'   => 'api.getId',
        'uses' => 'EvseController@getId'
    ]);

    $api->post('deviceIdentification/{hash}', [
        'as'   => 'api.deviceIdentification',
        'uses' => 'EvseController@deviceIdentification'
    ]);

    $api->post('getParameter/{hash}', [
        'as'   => 'api.getParameterData',
        'uses' => 'EvseController@getParameterData'
    ]);
    
    $api->post('setParameterData/{hash}', [
        'as'   => 'api.setParameterData',
        'uses' => 'EvseController@setParameterData'
    ]);



});
