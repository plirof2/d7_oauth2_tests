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


class AuthorizationGrantController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Authorization Grant Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users from external authenticating services and
    | directs them to password grant flow like internal users.
    |
    |
    */
  use AuthenticatesUsers;

	const REFRESH_TOKEN = 'refreshToken';
	private $cookie;
  private $user;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Application $app, ExternalUserRepositoryInterface $ext_userRep)
    {
		 $this->cookie = $app->make('cookie');
		 $this->ext_userRep = $ext_userRep;
		 $this->middleware('guest');
    }

    /**
     * @SWG\Get(
     *      path="/authorization_grant ",
     *      operationId="externalUserLogin",
     *      tags={"Login"},
     *      summary="External user Login",
     *      description="External user Login through ΓΓΠΣ",
     *      @SWG\Response(
     *          response=200,
     *          description="User Logged In Successfully"
     *       ),
     * )
     *
     */
	public function external(Request $request){

		$query = http_build_query([
		'audience' => env('AUDIENCE'),
		'client_id' => env('CLIENT_ID_EXT'),
        'redirect_uri' => env('REDIR_URI'),
        'response_type' => 'code',
        'scope' => 'offline_access openid email profile'
    ]);
    // Redirect the user to the OAuth authorization page
		return redirect(env('AUTHORIZE').$query);


    }

	public function callback(Request $request)
    {

		$http = new \GuzzleHttp\Client;

		$response = $http->post(env('TOKEN_URI'), [
        'form_params' => [
			'grant_type' => 'authorization_code',
            'client_id' => env('CLIENT_ID_EXT'),
            'client_secret' => env('CLIENT_SECRET_EXT'),
            'redirect_uri' => env('REDIR_URI'),
            'code' => $request->code, // Get code from the callback
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

			$response2 = $http2->get('https://oauth2-test.auth0.com/userinfo', [
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
		return redirect('http://localhost:8080/#/callback?'.$response_data);
	}


	/**
	 * Convert external user to internal if he does not exist.
	 * Call api/login and give password grant credentials.
	 *
	 * @returns array with access token info
	 */

  /**
 * Convert external user to internal if he does not exist.
 * Call api/login and give password grant credentials.
 *
 * @returns array with access token info
 */
	protected function revertToLocalAuth(array $array)	{


		 /*used instead of repository

		 	$user = ExternalUser::firstOrCreate(['name' => $array['name']],
		 					['email' => $array['email'], 'password' => Hash::make($array['sub'])]);
		 */


		/*used to insert a random user for test purposes

			$user = ExternalUser::firstOrCreate(['name' => 'testuser'],
						['email' => 't@t.com', 'password' => Hash::make('testuser')]);

		*/

		$user = $this->ext_userRep->find_or_create($array);

			if (Hash::check($array['sub'], $user->getAttributeValue('password'))) {

				$http3 = new \GuzzleHttp\Client;

					$response3 = $http3->post(env('APP_URL2').'/api/login', [
							'form_params' => [
								'name' => $user->getAttributeValue('name'),
								'password' => $array['sub'],
								'scope'=> 'Applicant'

							],
						]);

						$array = json_decode((string)$response3->getBody(), true);// το switch true γυρναει associative array
						//$data = json_decode((string)$response3->getBody());    // γυρναει object(stdClass)
						//$array = get_object_vars($data);



			}
			else{

					$array = array('message'=>'Wrong credentials');
			}
			return $array;

			//$userObj = User::find($user->id);
			//$tokenStr = $userObj->createToken('Token Name')->accessToken;


	}
}
