<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:4',
        ]);
        if ($validator->fails()){
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = ['token' => $token];
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 422);
        }
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:18',
            'email' => 'required|string|unique:users',
            'password' => 'required|string|min:4',
        ]);
        if($validator->fails()){
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $request['password']=Hash::make($request['password']);
        $request['remember_token']=Str::random(10);
        $user = User::create($request->toArray());
        $token = $user->createToken('Logistic Password Grant Client')->accessToken;
        $response = ['token' => $token];
        return response($response, 200);
    }

    public function getToken(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()){
            $errors = $validator->errors();
            return response([
                "messages" => $errors
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $http = new Client;
        $hostname = env("APP_URL");
        
        try{
            $user = User::where('email',$request->username)->first();
            if(isset($user)){

                $response = $http->post($hostname.'/oauth/token', [
                    'form_params' => [
                        'client_id' => env("PASSWORD_CLIENT_ID"),
                        'client_secret' => env("PASSWORD_CLIENT_SECRET"),
                        'grant_type' => 'password',
                        'username' => $request->username,
                        'password' => $request->password
                    ]
                ]);
                
                if($response->getStatusCode() == Response::HTTP_OK){
                    $responseBody = json_decode((string) $response->getBody(), true);
                    return response($responseBody,Response::HTTP_OK);
                } else {
                    return response([
                    
                        "messages" => ["La contraseña ingresada es inválidas."]
                    ],Response::HTTP_UNAUTHORIZED);
                }
            }else{
                return response([
                    "messages" => ["El usuario " . $request->username . " no está registrado."]
                ],Response::HTTP_UNAUTHORIZED);
            }
        }catch (RequestException $e){
            return response([
                "data" => $e->getMessage(),
                "messages" => ["La contraseña ingresada es inválida."]
            ],Response::HTTP_UNAUTHORIZED);
        }catch (Exception $e){
            return response([
                "data" => $e->getMessage(),
                "messages" => $e->getMessage()
            ],Response::HTTP_UNAUTHORIZED);
        }
    }

    public function getSession(){
        try{
            // Constantes
            $user = Auth::user();
            $usuario = $user->usuario();            

            return response([
                'usuario' => [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'correo_electronico' => $usuario->correo_electronico,
                    'identificacion_usuario' => $usuario->identificacion_usuario,
                ]
            ], Response::HTTP_OK);
        }catch (Exception $e){
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Revoke user tokens
     */
    public function logout(){
        try{
            $user = Auth::user();
            $userTokens = $user->tokens;
            foreach($userTokens as $token) {
                $token->revoke();
            }
            return response(null, Response::HTTP_OK);
        }catch (Exception $e){
            return response([
                "messages" => "Ocurrió un error al intentar cerrar la sesión."
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function forgotPassword(Request $request){
        $datos = $request->all();
        $validator = Validator::make($datos, [
            'email' => 'required|exists:users,email'
        ], [
            'exists'=>'El usuario ' .$request->email .' no existe.'
        ]);
    
        if($validator->fails()) {
            return response(
                get_response_body(format_messages_validator($validator))
                , Response::HTTP_BAD_REQUEST
            );
        }
    
        $user = User::where('email','=',$request->email)->limit(1)->get()[0];
        $usuario = $user->usuario();
    
        $token = Password::getRepository()->create($user);
        
        if(!isset($token)){
            return response(["mensajes" => ['Problema con servidor de correos']], Response::HTTP_BAD_REQUEST);
        }
        
        return response(["mensajes" => ["El email ha sido enviado"]], Response::HTTP_OK);
    }

    public function resetPassword(Request $request){
        $datos = $request->all();
        $validator = Validator::make($datos, [
            'token' => 'required',
            'email' => 'required|exists:users,email',
            'password' => 'required|confirmed',
        ], [
            'email.exists'=>'El usuario no existe.',
            'token.required'=>'El token es inválido.',
        ]);

        if($validator->fails()) {
            return response(
                get_response_body(format_messages_validator($validator))
                , Response::HTTP_BAD_REQUEST
            );
        }
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET){
            return response([
                "mensajes" => ["La contraseña ha sido actualizada"]
            ],Response::HTTP_OK);
        } elseif( $status === Password::INVALID_USER) {
            return response([
                "mensajes" => ['Usuario inválido']
            ], Response::HTTP_BAD_REQUEST);
        } elseif( $status === Password::INVALID_TOKEN) {
            return response([
                "mensajes" => ['Token inválido']
            ], Response::HTTP_BAD_REQUEST);
        } else {
            return response([
                "mensajes" => ['Hubo un error']
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
