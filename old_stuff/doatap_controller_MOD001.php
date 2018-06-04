<?php

namespace App\Http\Controllers\Auth;


use Illuminate\Foundation\Application;
use App\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Support\Facades\Hash;
use App\ExternalUser;
use App\Repositories\ExternalUserRepositoryInterface;



	public function callback(Request $request)
    {

		$http = new \GuzzleHttp\Client;

		$response = $http->post('http://localhost:8282/oauth2/v4/token', [
        'form_params' => [
			'grant_type' => 'authorization_code',
            'client_id' => 'dummy-client-id',
            'client_secret' => 'dummy-client-secret',
            'redirect_uri' => 'http://localhost/formspde-test/gsis_callback',
            //'code' => $request->code, // Get code from the callback
            'code' => $_REQUEST['code'], // Get code from the callback
        ],
    ]);
  	$data = json_decode((string) $response->getBody());
    $array = get_object_vars($data);
  	//get user data from oauth
  	$http2 = new \GuzzleHttp\Client;

    // echo the access token; normally we would save this in the DB
    //return json_decode((string) $response->getBody(), true);

	 $data = json_decode((string) $response->getBody());
     $array = get_object_vars($data);

	//get user data from oauth

		$http2 = new \GuzzleHttp\Client;

			$response2 = $http2->get('http://localhost:8282/oauth2/v3/userinfo', [
			'headers' => [
				'Authorization' => 'Bearer ' . $array['access_token'],
				'Accept'        => 'application/json',
			],
		]);

	 $data2 = json_decode((string) $response2->getBody());
     $array2 = get_object_vars($data2);



	   /**
		*
		* 1)  return to callback in vue with the token from the external authentication
		*
		*
				$response_data=http_build_query([
					'access_token' => $array['access_token'],
					'expires_in' => $array['expires_in'],
					'scope'=> $scope,
				]);

				return redirect('http://localhost:8080/#/callback?'.$response_data);

		**/


	   /**
		*
		* 2) return to callback in laravel with the token from the external authentication
		*

				return [
					'access_token' => $array['access_token'],
					'expires_in' => $array['expires_in'],
					'scope'=> $scope,
				];

		**/


	   /**
		*
		*  3) Call to api/login for internal authentication and redirecting to vue front end
		*/

		$localTokens = $this->revertToLocalAuth($array2);
		$response_data=http_build_query($localTokens);
		return redirect('http://localhost/formspde-test/gsis_callback?'.$response_data);
	}


	/**
	 * Convert external user to internal if he does not exist.
	 * Call api/login and give password grant credentials.
	 *
	 * @returns array with access token info
	 */

