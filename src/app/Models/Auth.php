<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

class Auth extends Model
{
  /**
   * Auth0のユーザーを削除する
   *
   * @param string $auth_id
   * @return boolean
   */
  public function deleteAuth0Account(string $auth_id): bool
  {
    $configuration = new SdkConfiguration([
      'domain' => config('auth0.domain'),
      'clientId' => config('auth0.managementId'),
      'clientSecret' => config('auth0.managementSecret'),
      'cookieSecret' => config('auth0.cookieSecret'),
    ]);

    $auth0 = new Auth0($configuration);

    $response = $auth0->management()->users()->delete($auth_id);

    if ($response->getStatusCode() >= 400){
      abort(500);
    }

    return true;
  }
}
