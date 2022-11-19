<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

class Auth extends Model
{
  public function __construct()
  {
    $configuration = new SdkConfiguration([
      'domain' => config('auth0.domain'),
      'clientId' => config('auth0.managementId'),
      'clientSecret' => config('auth0.managementSecret'),
      'cookieSecret' => config('auth0.cookieSecret'),
    ]);

    $this->auth0 = new Auth0($configuration);
  }

  /**
   * Auth0のユーザーを更新する
   *
   * @param string $auth_id
   * @param array $request
   * @return boolean
   * @link https://auth0.com/docs/api/management/v2#!/Users/patch_users_by_id
   */
  public function updateAuth0Account(string $auth_id, array $request): bool
  {
    $response = $this->auth0->management()->users()->update($auth_id, $request);

    if ($response->getStatusCode() >= 400){
      abort(500);
    }

    return true;
  }

  /**
   * Auth0のユーザーを削除する
   *
   * @param string $auth_id
   * @return boolean
   */
  public function deleteAuth0Account(string $auth_id): bool
  {
    $response = $this->auth0->management()->users()->delete($auth_id);

    if ($response->getStatusCode() >= 400){
      abort(500);
    }

    return true;
  }
}
