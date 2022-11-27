<?php

namespace App\Http\Controllers\Maritimo;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Models\Maritimo\Flota;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FlotaController extends Controller
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
                $flotas = Flota::getLightList();
            }else{
                if(isset($data['ordenar_por'])){
                    $data['ordenar_por'] = format_order_by_attributes($data);
                }
                $flotas = Flota::getList($data);
            }
            return response($flotas, Response::HTTP_OK);
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
                'numero' => [
                    'string',
                    'required',
                    'max:8',
                    'min:8',
                    'regex:/^[a-zA-Z]{3}[0-9]{4}[a-zA-Z]{1}/',
                    Rule::unique('flotas')
                        ->where(fn ($query) => 
                            $query->where('numero', $data['numero'])
                        )
                ],
                'nombre' => 'string|required|max:128',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $flota = Flota::modifyOrCreate($data);
            
            if ($flota) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La flota ha sido creada.", 2], $flota),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear la flota."]), Response::HTTP_CONFLICT);
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
                'id' => 'integer|required|exists:flotas,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(Flota::show($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:flotas,id',
                'numero' => [
                    'string',
                    'required',
                    'max:8',
                    'min:8',
                    'regex:/^[a-zA-Z]{3}[0-9]{4}[a-zA-Z]{1}/',
                    Rule::unique('flotas')
                        ->where(fn ($query) => 
                            $query->where('numero', $data['numero'])
                        )->ignore(Flota::find($id))
                ],
                'nombre' => 'string|required|max:128',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $flota = Flota::modifyOrCreate($data);
            if($flota){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La flota ha sido modificada.", 1], $flota),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar la flota."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:flotas,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = Flota::destroy($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La flota ha sido eliminada.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar la flota."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
