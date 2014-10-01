<?php

/**
 *  @SWG\Resource(
 *    apiVersion="2.0",
 *    resourcePath="/products",
 *    @SWG\Api(path="/products",
 *      @SWG\Operations(
 *        @SWG\Operation(
 *          method="GET",
 *          nickname="get",
 *          type="array",
 *          items="$ref:Product",
 *          @SWG\ResponseMessage(code=200,message="")
 *        )
 *      ),
 *     ),
 *    ),
 *    @SWG\Api(path="/products/{id}",
 *      @SWG\Operations(
 *        @SWG\Operation(
 *      	method="PUT",
 *      	nickname="put",
 *      	@SWG\Parameters(
 *            @SWG\Parameter(
 *              name="id",
 *              description="",
 *              paramType="path",
 *              type="integer",
 *            ),
 *            @SWG\Parameter(
 *              name="name",
 *              description="",
 *              paramType="body",
 *              type="string",
 *            ),
 *            @SWG\Parameter(
 *              name="sku",
 *              description="",
 *              paramType="body",
 *              type="string",
 *            ),
 *            @SWG\Parameter(
 *              name="price",
 *              description="",
 *              paramType="body",
 *              type="decimal",
 *            ),
 *            @SWG\Parameter(
 *              name="quantity",
 *              description="",
 *              paramType="body",
 *              type="integer",
 *            ),
 *         )
 *      )
 *    )
 *  )
 */

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
								if(!isset($product->channelInfo['shopify'])) {
									$product->channelInfo = array_merge($product->channelInfo, ['shopify' => ['id' => $variant['id']]]);
									$product->save();
								}

								$id = $product->channelInfo['shopify']['id'];
								$url = 'https://' . $config['account'] . '.myshopify.com/admin/variants/' . $id . '.json';

								/* do we need to update the quantitiy? */
								if($product->quantity != $variant['inventory_quantity'] || $product->price != $variant['price']) {

									/* you could either use inventory_quantity_adjustment or set old and new inventory
									   I'm using the former */
									$variant = [
										'id' => $id,
										'price' => $product->price,
										'option1' => $product->name,
										'inventory_quantity_adjustment' => $product->quantity - $variant['inventory_quantity'],
									];

									$response = $client->put($url, [
										'auth' => [$config['key'], $config['secret']],
										'json' => [
											'variant' => $variant
										]
									])->json();

									dd($variant);

								}

							}

						}
					}

					break;

				case 'vend':
					/* get vend channel token */
					$channel = Channel::where('type', '=', 'vend')->get();

					/* do we have a Vend channel in the DB? */
					if(!$channel->isEmpty()) {
						$channel = $channel->first();

						$url = 'https://' . $channel->domain_prefix . '.vendhq.com/api/products';
						$products = $client->get($url, [
							'headers' => [
								'Authorization' => sprintf('Bearer %s', $channel->access_token)
							]
						])->json();
						
						foreach($products['products'] as $remoteProduct) {

							if(!isset($remoteProduct['type']))
								continue;

							$outlet = array_pull($remoteProduct, 'inventory');
							$outlet = array_pull($outlet, 0);

							$name = $remoteProduct['name'];
							$price = $remoteProduct['price'];

							$sku = $remoteProduct['sku'];

							$product = Product::where('sku', '=', $sku)->get();

							if($product->isEmpty()) {

								$product = new Product;
								$product->name = $name;
								$product->price = $price;
								$product->quantity = $outlet['count'];
								$product->sku = $sku;
								
								/* we could eventually use this property
								   to store far more info about each product */
								$product->channelInfo = ['vend' => ['id' => $remoteProduct['id']]];
								$product->save();

							} else {

								/* sync */
								$product = $product->first();
								/* is product linked to vend? */

								if(!isset($product->channelInfo['vend'])) {
									$product->channelInfo = array_merge($product->channelInfo, ['vend' => ['id' => $remoteProduct['id']]]);
									$product->save();
								}

								$id = $product->channelInfo['vend']['id'];
								$url = 'https://' . $channel->domain_prefix . '.vendhq.com/api/products';

								/* do we need to update the quantity? */
								if($product->quantity != $outlet['count'] || $product->price != $remoteProduct['price']) {

									$outlet['count'] = $product->quantity;

									$params = [
										'id' => $id,
										'name' => $product->name,
										'retail_price' => $product->price,
										'inventory' => [(object) $outlet],
									];

									$response = $client->post($url, [
										'headers' => [
											'Authorization' => sprintf('Bearer %s', $channel->access_token)
										],
										'body' => json_encode($params)
									])->json();

								}

							}

						}

						break;
					}


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
		$input = Input::json();

		$product = new Product;
		
		foreach($input as $key => $value) {

			if(isset($product->$key)) {
				$product->$key = $value;
			}
		}

		$product->save();

		return Response::json($product);
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
