<?php

class ProductsController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return Response::json(Product::all());
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Sync products between channels.
	 *
	 * @return string
	 */
	public function sync()
	{

		$client = new \GuzzleHttp\Client();

		$channels = ['shopify', 'vend'];

		foreach($channels as $channel) {

			switch($channel) {
				case 'shopify':

					/* load config */
					$config = \Config::get('services.shopify');
					
					/* make request */
					$url = 'https://' . $config['account'] . '.myshopify.com/admin/products.json';
					
					$products = $client->get($url, [
						'auth' => [$config['key'], $config['secret']]
					])->json();

					foreach($products['products'] as $remoteProduct) {

						$variants = array_pull($remoteProduct, 'variants');
						foreach($variants as $variant) {

							$name = $remoteProduct['title'] . ' - ' . ucfirst($variant['title']);
							$price = $variant['price'];
							$quantity = $variant['inventory_quantity'];
							$sku = $variant['sku'];

							$product = Product::where('sku', '=', $sku)->get();

							if($product->isEmpty()) {

								$product = new Product;
								$product->name = $name;
								$product->price = $price;
								$product->quantity = $quantity;
								$product->sku = $sku;
								
								/* we could eventually use this property
								   to store far more info about each product */
								$product->channelInfo = ['shopify' => ['id' => $variant['id']]];

								$product->save();

							} else {

								/* sync */
								$product = $product->first();
								$id = $product->channelInfo->shopify->id;
								$url = 'https://' . $config['account'] . '.myshopify.com/admin/variants/' . $id . '.json';

								/* do we need to update the quantitiy? */
								if($product->quantity != $variant['inventory_quantity']) {

									/* you could either use inventory_quantity_adjustment or set old and new inventory
									   I'm using the latter */
									$variant = [
										'id' => $id,
										'inventory_quantity_adjustment' => $product->quantity - $variant['inventory_quantity'],
									];

									var_dump($variant);

									$response = $client->put($url, [
										'auth' => [$config['key'], $config['secret']],
										'json' => [
											'variant' => $variant
										]
									])->json();

								}

							}

						}
					}

					break;

				case 'vend':

					break;

			}

		}


	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		
		return Response::json(Product::find($id));

	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		
		$input = Input::json();

		$product = Product::find($id);
		
		foreach($input as $key => $value) {

			if(isset($product->$key)) {
				$product->$key = $value;
			}
		}

		$product->save();

		return Response::json($product);
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
