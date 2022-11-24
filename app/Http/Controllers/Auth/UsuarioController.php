<?php

namespace App\Http\Controllers\Auth;

use Exception;
use App\Models\Auth\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        try{
            $datos = $request->all();
            if(!($request->ligera)) {
                $validator = Validator::make($datos, [
                    'limite' => 'integer|between:1,500'
                ]);

                if ($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }
            if($request->ligera){
                $usuarios = Usuario::obtenerColeccionLigera($datos);
            }else{
                if (isset($datos['ordenar_por'])) {
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $usuarios = Usuario::obtenerColeccion($datos);
            }
            return response($usuarios, Response::HTTP_OK);
        }catch(Exception $e){
            return response(get_response_body([$e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $validator = Validator::make($datos, [
                'nombre' => 'string|required|max:128',
                'correo_electronico' => 'required|unique:usuarios,correo_electronico',
                'identificacion_usuario' => 'required|unique:usuarios,identificacion_usuario|unique:users,email',
                'clave'=> 'required',
            ]);
            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }
            
            $usuario = Usuario::modificarOCrear($datos);
            if ($usuario) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El usuario ha sido creado.", 2], $usuario),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el usuario."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(get_response_body([$e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Seguridad\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:usuarios,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }
            $usuario = Usuario::cargar($id);
            return response($usuario, Response::HTTP_OK);
        }catch (Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Seguridad\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $datos['id'] = $id;
            if(!$request->cambio_clave){
                $validations = [
                    'id' => 'integer|required|exists:usuarios,id',
                    'nombre' => 'string|required|min:3|max:128',
                    'correo_electronico' => 'unique:usuarios,correo_electronico,'.$id,
                    'estado' => 'boolean',
                    'identificacion_usuario' => 'unique:usuarios,identificacion_usuario,' .$id,
                ];
            }

            $validator = Validator::make($datos, $validations);
            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }
            if($request->cambio_clave){
                Usuario::cambiarClave($id, $datos);
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La contraseña ha sido modificada."]),
                    Response::HTTP_OK
                );
            }else{
                $usuario = Usuario::modificarOCrear($datos);
                if($usuario){
                    DB::commit(); // Se cierra la transacción correctamente
                    return response(
                        get_response_body(["El usuario ha sido modificado.", 1], $usuario),
                        Response::HTTP_OK
                    );
                } else {
                    DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                    return response(get_response_body(["Ocurrió un error al intentar modificar el usuario."]), Response::HTTP_CONFLICT);;
                }
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(get_response_body([$e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Seguridad\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:usuarios,id'
            ]);
                
            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }
            
            $eliminado = Usuario::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El usuario ha sido eliminado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el usuario."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
