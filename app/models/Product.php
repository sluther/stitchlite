<?php

class Product extends Eloquent {

	/* the name of the product */
	private $name;

	/* the SKU of the product */
	private $sku;

	/* the price of the item */
	private $price;

	/* the quantity */
	private $quantity;

	/* raw channel info about the product, used for syncing */
	private $channelInfo;

    public function getChannelInfoAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setChannelInfoAttribute($value)
    {
        $this->attributes['channelInfo'] = json_encode($value);
    }

}