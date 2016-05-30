<?php

namespace App\Http\Controllers\Alma;

use Illuminate\Support\ServiceProvider;
use Guzzle\Http\Client;

class AlmaUserServiceProvider extends ServiceProvider {

  /**
   * Indicates if loading of the provider is deferred.
   *
   * @var bool
   */
  protected $defer = true;

  /**
   * Bootstrap the application events.
   *
   * @return void
   */
  public function boot()
  {
    $this->package('uqlibrary/alma');
    include __DIR__ . '/../../routes.php';
  }

	/**
	 * Register the service provider.
	 * @return void
	 */
	public function register() {
		//
	}

	/**
	 * Connect to Alma API
	 * @return Client
	 */
	public static function connect() {
		return new Client(
      getenv('ALMA_ENDPOINT').'/'.getenv('ALMA_USERPATH'),
      [
        'request.options' => [
          'headers' => [
            'Authorization' => 'apikey '.getenv('ALMA_APIKEY'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
          ]
        ]
      ]
    );
	}

  public static function getQuery($method='GET') {
    $method = strtoupper($method);
    $query = [];
    if ($method <> 'POST') {
      $query['use_id_type'] = "all_unique";
      switch ($method) {
        case 'GET':
          $query['expand'] = "loans,requests,fees";
          break;
        case 'PUT':
          $query['override'] = "user_group,job_category,preferred_language,user_title";
          break;
      }
    }
    if (count($query)) {
      return ("?".http_build_query($query));
    }
    return '';
  }

  public static function getResponse($client) {
    try {
      $request = $client->send();
      return $request->getBody(true);
    } catch (BadResponseException $bre) {
      throw $bre;
    } catch (CurlException $ce) {
      throw $ce;
    }
  }

	/**
	 * Get Users List
	 * api call method: GET
	 */
	public static function getUsers() {
    $client = self::connect()->get();
    $users = [];
    foreach(self::getResponse($client) as $value) {
      $users[] = DataMigrationServiceProvider::toMembership($value);
    }
    return $users;
	}

	/**
	 * Create User
	 */
	public static function createUser($user) {
    $user = DataMigrationServiceProvider::toAlma($user);
    $client = self::connect()->post('', [], json_encode($user));
    $reply = DataMigrationServiceProvider::toMembership(self::getResponse($client));
    return $reply;
	}

	/**
	 * Get User details
	 */
	public static function getUser($userId) {
    $endPoint=$userId.self::getQuery('GET');
    $client = self::connect()->get($endPoint);
    $reply = DataMigrationServiceProvider::toMembership(self::getResponse($client));
    return $reply;
	}

	/**
	 * Update User details
	 */
	public static function updateUser($userId, $user) {
    $user = DataMigrationServiceProvider::toAlma($user);
    $endPoint=$userId.self::getQuery('PUT');
    $client = self::connect()->put($endPoint, [], json_encode($user));
    $reply = DataMigrationServiceProvider::toMembership(self::getResponse($client));
    return $reply;
	}

	/**
	 * Delete User
	 */
	public static function deleteUser($userId) {
    $client = self::connect()->delete($userId);
    return self::getResponse($client);
	}
}

