<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


Route::get('channels/auth/{channel}', 'ChannelsController@auth');
Route::get('channels/callback/{channel}', 'ChannelsController@callback');
Route::post('products/sync', 'ProductsController@sync');
Route::resource('products', 'ProductsController');
