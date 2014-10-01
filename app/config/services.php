<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Third Party Services
	|--------------------------------------------------------------------------
	|
	| This file is for storing the credentials for third party services such
	| as Stripe, Mailgun, Mandrill, and others. This file provides a sane
	| default location for this type of information, allowing packages
	| to have a conventional place to find your various credentials.
	|
	*/

	'mailgun' => array(
		'domain' => '',
		'secret' => '',
	),

	'mandrill' => array(
		'secret' => '',
	),

	'stripe' => array(
		'model'  => 'User',
		'secret' => '',
	),

	'shopify' => [
		'key' => '15f7c6bcbcc761c0346cba5bc4be29b5',
		'secret' => 'f845e4237a3d52c253fac22d1c358354',
		'account' => 'code-sample'
	],

	'vend' => [
		'key' => 'mDhuluDXuZc3YBQk30sANSSdugzVJjAt',
		'secret' => '7H4M3GbergrDvHMhs1yGnY6ijR0AnHlz',
		'url' => 'https://secure.vendhq.com/connect',
	]

);
