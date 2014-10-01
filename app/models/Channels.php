<?php

class Channel extends Eloquent {

	/* the name of the channel */
	private $name;

	/* the channel type for the channel (shopify or vend) */
	private $channel;

	/* the auth token for the channel */
	private $token;

}