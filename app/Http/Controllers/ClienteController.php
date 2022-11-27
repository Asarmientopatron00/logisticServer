<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Enum\TipoDocumentoEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        try{
            $data = $request->all();
            if(!$request->ligera){
                $validator = Validator::make($data, [
                    'limite' => 'integer|between:1,500'
                ]);

                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }

            if($request->ligera){
                $clientes = Cliente::getLightList();
            }else{
                if(isset($data['ordenar_por'])){
                    $data['ordenar_por'] = format_order_by_attributes($data);
                }
                $clientes = Cliente::getList($data);
            }
            return response($clientes, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
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
            $data = $request->all();
            $validator = Validator::make($data, [
                'tipo_documento' => 'string|required|in:'.join(',', TipoDocumentoEnum::OPTIONS),
                'numero_documento' => [
                    'string',
                    'required',
                    Rule::unique('clientes')
                        ->where(fn ($query) => 
                            $query->where('numero_documento', $data['numero_documento'])
                        )
                ],
                'nombre' => 'string|required|min:1|max:128',
                'telefono' => 'string|nullable|max:128',
                'email' => 'string|nullable|email|max:128',
                'direccion' => 'string|nullable|max:128',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $cliente = Cliente::modifyOrCreate($data);
            
            if ($cliente) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El cliente ha sido creado.", 2], $cliente),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el cliente."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $data['id'] = $id;
            $validator = Validator::make($data, [
                'id' => 'integer|required|exists:clientes,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(Cliente::show($id), Response::HTTP_OK);
        }catch (Exception $e){
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $data = $request->all();
            $data['id'] = $id;
            $validator = Validator::make($data, [
                'id' => 'integer|required|exists:clientes,id',
                'tipo_documento' => 'string|required|in:'.join(',', TipoDocumentoEnum::OPTIONS),
                'numero_documento' => [
                    'string',
                    'required',
                    Rule::unique('clientes')
                        ->where(fn ($query) => 
                            $query->where('numero_documento', $data['numero_documento'])
                        )->ignore(Cliente::find($id))
                ],
                'nombre' => 'string|required|min:1|max:128',
                'telefono' => 'string|nullable|max:128',
                'email' => 'string|nullable|email|max:128',
                'direccion' => 'string|nullable|max:128',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $cliente = Cliente::modifyOrCreate($data);
            if($cliente){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El cliente ha sido modificado.", 1], $cliente),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el cliente."]), Response::HTTP_CONFLICT);;
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(get_response_body([$e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $data['id'] = $id;
            $validator = Validator::make($data, [
                'id' => 'integer|required|exists:clientes,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = Cliente::destroy($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El cliente ha sido eliminado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el cliente."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
