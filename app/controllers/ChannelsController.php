<?php

class ChannelsController extends \BaseController {


	public function auth($channel_type) {
		
		/* load config */
		$config = \Config::get('services.' . $channel_type);
		$redirect_uri = \Config::get('app.url');

		$oauth = new OAuth($config['key'], $config['secret']);
		
		$params = [
			'response_type' => 'code',
			'client_id' => $config['key'],
			'redirect_uri' => $redirect_uri . '/channels/callback/' . $channel_type
		];

		$query = http_build_query($params, null, '&');

		return Redirect::to($config['url'] . '?' . $query);

	}

	public function callback($channel_type) {

		/* load config */
		$config = \Config::get('services.' . $channel_type);
		$redirect_uri = \Config::get('app.url');
		
		$params = [
			'code' => Input::get('code'),
			'client_id' => $config['key'],
			'client_secret' => $config['secret'],
			'grant_type' => 'authorization_code',
			'redirect_uri' => $redirect_uri . '/channels/callback/' . $channel_type
		];

		$request_token_url = 'https://' . Input::get('domain_prefix') . '.vendhq.com/api/1.0/token';

		$client = new \GuzzleHttp\Client();
		$response = $client->post($request_token_url, [
			'body' => $params
		])->json();

		$channel = new Channel;

		$channel->type = $channel_type;
		$channel->refresh_token = $response['refresh_token'];
		$channel->access_token = $response['access_token'];

		$channel->save();

	}

}
