
# A PHP API library for FastSpring
FastSpring's own API wrapper library for PHP being deprecated due to it being based on FastSpring Classic, this is an unofficial API wrapper library for the latest version of FastSpring.

The purpose and information available at the different endpoints will not be documented here. Check out [FastSpring's API documentation](https://fastspring.com/docs/fastspring-api/) for this type of information.

## Getting started
To get started, simply require the package via Composer:

```
composer require emileperron/fastspring-php
```

Once that's done, you can start using the library in your project. Here's a very brief overview of the library's usage and functionalities:

```php
<?php

use Emileperron\FastSpring\FastSpring;
use Emileperron\FastSpring\Entity\Order;
use Emileperron\FastSpring\Entity\Product;
use Emileperron\FastSpring\Entity\Subscription;

// First, initialize the library with your API credentials
FastSpring::initialize('your-fastspring-api-username', 'your-fastspring-api-password');

// Find all
$orders = Order::findAll();

// Find single by ID/path
$product = Product::find('mytestproduct');

// Find all with filters
$orders = Order::findBy(['products' => 'mytestproduct']);

// Find by IDs
$subscriptions = Subscription::find(['2XzdAW3_SMSl1I18ccj26A', '9zGdAW3_AM1L6I18cqj21Y']);

// Deleting
$product->delete();

// Working with entities
foreach ($subscriptions as $subscription) {
	echo $subscription['state'] . "\n";
}
```

## Entities
There are two ways to use the library: making requests manually via the `FastSpring` class, and using the built-in entities.

The entities provide a simple interface through which you can load your desired objects and access their data. For every endpoint that FastSpring's API offers, there is a corresponding entity in the `Emileperron\FastSpring\Entity` namespace.

Every entity works the same way, as they all extend the same `AbstractEntity` class. This provides all of the basic functionalities you need to start working with the API.

### Fetching objects
To fetch data using entities, simply call the static methods `find($ids)` or `findAll()` on your desired entity class. Both methods will return an array of entities. All entities implement `ArrayAccess`, which means you can access their data just like you would with an array.

The `find()` method allows to provide either an array of IDs or a single ID. When you provide a single ID, the method will return the resulting entity directly instead of returning an array.

### Deleting objects
Once you have loaded an entity, you can call the `delete()` method on it to delete it.

### Creating and updating objects
This functionality has not been implemented yet. Feel free to submit a pull request if you want to implement it.

## Manual requests
If the entities don't offer the functionalities you need, you can always make manual requests with the `FastSpring` class. You can refer to [FastSpring's API documentation](https://fastspring.com/docs/fastspring-api/) for the exact data and response formats of the different endpoints, and for the request methods to use.

Each request method has its own static method that accepts two parameters: the endpoint, and the payload you wish to send. The payload is optional, as GET requests without a payload can be used to get a list of all IDs for an endpoint.

Here are a few examples of manual requests:

```php
// Making manual requests to the API
$response = FastSpring::get('orders');
$response = FastSpring::post('products', [
	'products' => [
		[
			'product' => 'mytestproduct',
			'sku' => 'my-updated-sku'
		]
	]
]);
$response = FastSpring::delete('orders', ['8UzdwW3_qM6lgI18Pca52y']);
```


## What's missing
At the moment, this library does not expose every feature of the FastSpring API to its users. Feel free to submit pull requests if you would like to add more functionalities.

Here is a list of things that are not available with the library at the moment:

- the `save()` method for entities (partially developed in `AbstractEntity`)
- URL parameters for non-filtered requests (ex.: for localization)
- batch requests
- scope option (test/live/all)

## Contributing
Feel free to submit pull requests on [the GitHub repository](https://github.com/EmilePerron/fastspring-php) if you want to add functionalities or suggest improvements to this library. I will look them over and merge them as soon as possible.

You can also submit issues if you run into problems but don't have time to implement a fix.

## Supporting
Finally, if you use the library and would like to support me, here are the ways you can do that:

- Saying thanks directly on Twitter: [@cunrakes](https://twitter.com/cunrakes)
- Giving this repository a star [on GitHub](https://github.com/EmilePerron/fastspring-php)
- Taking a look at my other projects [on my website](https://www.emileperron.com)
- [Buying me a cup of tea](https://www.buymeacoffee.com/EmilePerron) ☕️
