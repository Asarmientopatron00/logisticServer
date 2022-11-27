<?php

namespace App\Http\Controllers\Terrestre;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Enum\EstadoPedidoEnum;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Terrestre\PedidoTerrestre;
use Illuminate\Support\Facades\Validator;

class PedidoTerrestreController extends Controller
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
                $pedidosTerrestres = PedidoTerrestre::getLightList();
            }else{
                if(isset($data['ordenar_por'])){
                    $data['ordenar_por'] = format_order_by_attributes($data);
                }
                $pedidosTerrestres = PedidoTerrestre::getList($data);
            }
            return response($pedidosTerrestres, Response::HTTP_OK);
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
                'cliente_id' => 'integer|required|exists:clientes,id',
                'tipo_producto_id' => 'integer|required|exists:tipos_productos_terrestres,id',
                'cantidad_producto' => 'integer|required|min:0',
                'fecha_registro' => 'date|required',
                'fecha_entrega' => 'date|required|after_or_equal:fecha_registro',
                'bodega_id' => 'integer|required|exists:bodegas,id',
                'precio_envio' => 'numeric|required',
                'descuento' => 'numeric|required',
                'vehiculo_id' => 'integer|required|exists:vehiculos,id',
                'estado' => 'string|required|in:'.join(',', EstadoPedidoEnum::OPTIONS),
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $pedidoTerrestre = PedidoTerrestre::modifyOrCreate($data);
            
            if ($pedidoTerrestre) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El pedido ha sido creado.", 2], $pedidoTerrestre),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el pedido."]), Response::HTTP_CONFLICT);
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
                'id' => 'integer|required|exists:bodegas,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(PedidoTerrestre::show($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:bodegas,id',
                'cliente_id' => 'integer|required|exists:clientes,id',
                'tipo_producto_id' => 'integer|required|exists:tipos_productos_terrestres,id',
                'cantidad_producto' => 'integer|required|min:0',
                'fecha_registro' => 'date|required',
                'fecha_entrega' => 'date|required|after_or_equal:fecha_registro',
                'bodega_id' => 'integer|required|exists:bodegas,id',
                'precio_envio' => 'numeric|required',
                'descuento' => 'numeric|required',
                'vehiculo_id' => 'integer|required|exists:vehiculos,id',
                'estado' => 'string|required|in:'.join(',', EstadoPedidoEnum::OPTIONS),
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $pedidoTerrestre = PedidoTerrestre::modifyOrCreate($data);
            if($pedidoTerrestre){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El pedido ha sido modificado.", 1], $pedidoTerrestre),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar la bodega."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:bodegas,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = PedidoTerrestre::destroy($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El pedido ha sido eliminado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar la bodega."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
