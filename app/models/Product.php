<?php

/**
 *  @SWG\Model(id="Product")
 */
class Product extends Eloquent {

	/* the name of the product */
	/**
	 *  @SWG\Property(
	 *    name="name",
	 *    type="string",
	 *    description="The name of the product"
	 *  )
	 */
	private $name;

	/* the name of the product */
	/**
	 *  @SWG\Property(
	 *    name="sku",
	 *    type="string",
	 *    description="The SKU of the product"
	 *  )
	 */
	private $sku;

	/* the name of the product */
	/**
	 *  @SWG\Property(
	 *    name="price",
	 *    type="decimal",
	 *    description="The price of the product"
	 *  )
	 */
	private $price;

	/* the name of the product */
	/**
	 *  @SWG\Property(
	 *    name="quantity",
	 *    type="integer",
	 *    description="The quantity of the product"
	 *  )
	 */
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