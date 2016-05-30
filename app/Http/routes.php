<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
  return view('welcome');
});

$prefix = 'alma';
$ns = 'Alma\\';

Route::get    ($prefix . '/',                       $ns . 'AlmaUserController@getUsers');
Route::get    ($prefix . '/cyberschools',           $ns . 'AlmaUserController@getCyberschools');
Route::get    ($prefix . '/patron_info/{barcode}',  $ns . 'AlmaUserController@getPatronInfo');
Route::get    ($prefix . '/patron_fields',          $ns . 'AlmaUserController@getPatronFields');
Route::get    ($prefix . '/patron/{barcode}',       $ns . 'AlmaUserController@getPatron');
Route::post   ($prefix . '/patron/{barcode?}',      $ns . 'AlmaUserController@postPatron');
Route::delete ($prefix . '/patron/{barcode}',       $ns . 'AlmaUserController@deletePatron');
Route::get    ($prefix . '/membership-data',        $ns . 'AlmaUserController@getMembershipData');
