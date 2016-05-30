<?php

namespace App\Http\Controllers\Alma;

use Illuminate\Support\Facades\Input;

class AlmaUserController extends AlmaController {

  /**
   * Get a list of Users from Alma
   *
   * @param string $barcode
   * @return \Illuminate\Http\JsonResponse
   */
  public function getUsers()
  {
    try {
      $reply = AlmaUserServiceProvider::getUsers();
      return $this->ok($reply);
    } catch (\Exception $ex) {
      return $this->error($ex->getMessage());
    }
  }

  /**
   * Get a user from its ID or Barcode
   *
   * @param string $userId
   * @return \Illuminate\Http\JsonResponse
   */
  public function getPatron($userId)
  {
    try {
      $reply = AlmaUserServiceProvider::getUser($userId);
      return $this->ok($reply);
    } catch (\Exception $ex) {
      return $this->error($ex->getMessage());
    }
  }

  /**
   * Returns patron type, stat class and  home library based on membership submission info.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getPatronFields()
  {
    try {
      $item = Input::all();
      if ($item and isset($item['type'])) {
        if ($item['type'] === 'hospital') {
          $fields = AlmaStaticDataProvider::_loadAlmaFields(
            $item['type'], $item['hospitalService'], $item['hospitalClass']
          );
        } elseif ($item['type'] === 'reciprocal') {
          $fields = AlmaStaticDataProvider::_loadAlmaFields(
            $item['type'], '', '', array_key_exists('reciprocalInstitution', $item) ? $item['reciprocalInstitution'] : ''
          );
        } else {
          $fields = AlmaStaticDataProvider::_loadAlmaFields($item['type']);
        }
      } else {
        throw new \Exception('Could not retrieve the parameter type');
      }

      return $this->ok($fields);
    } catch (\Exception $ex) {
      return $this->error($ex->getMessage());
    }
  }

  /**
   * Gets users summary (fees, holds and requests)
   *
   * @param string $userId
   * @return \Illuminate\Http\JsonResponse
   */
  public function getPatronInfo($userId)
  {
    try {
      $user = json_decode(AlmaUserServiceProvider::getUser($userId), true);
      return $this->ok(DataMigrationServiceProvider::getUserSummary($user));

    } catch (\Exception $ex) {
      return $this->error($ex->getMessage());
    }
  }

  /**
   * Add a new user or update an existing one in Alma
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function postPatron($userId=null)
  {
    $obj = input::all();
    if ($userId) {
      return self::updatePatron($userId, $obj);
    }
    return self::createPatron($obj);
  }

  /**
   * Create a new user in Alma based on the membership submission form.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function createPatron($obj)
  {
    if ($obj) {
      try {
        $reply = AlmaUserServiceProvider::createUser($obj);
        return $this->ok($reply);
      } catch (\Exception $ex) {
        return $this->error($ex->getMessage());
      }
    }
  }

  /**
   * Update an Alma users profile
   *
   * @param string $userId (can also be the barcode)
   * @return \Illuminate\Http\JsonResponse
   */
  public function updatePatron($userId, $obj)
  {
    if ($obj and $userId) {
      try {
        $reply = AlmaUserServiceProvider::updateUser($userId, $obj);
        return $this->ok($reply);
      } catch (\Exception $ex) {
        return $this->error($ex->getMessage());
      }
    }
  }

  /**
   * Remove an User from Alma
   *
   * @param string $userId (can also be users Barcode)
   * @return \Illuminate\Http\JsonResponse
   */
  public function deletePatron($userId)
  {
    try {
      AlmaUserServiceProvider::deleteUser($userId);
      return $this->ok('Deleted');
    } catch (\Exception $ex) {
      return $this->error($ex->getMessage());
    }
  }

  /**
   * @return \Illuminate\Http\JsonResponse
   */
  public function getMembershipData()
  {
    try {
      return $this->ok(AlmaStaticDataProvider::_getMembershipData());
    } catch (\Exception $ex) {
      return $this->error($ex->getMessage());
    }
  }

  /**
   * @return \Illuminate\Http\JsonResponse
   */
  public function getCyberschools()
  {
    try {
      $reply = AlmaStaticDataProvider::_getCyberschools();
      return $this->ok($reply);
    } catch (\Exception $ex) {
      return $this->error($ex->getMessage());
    }
  }
}
