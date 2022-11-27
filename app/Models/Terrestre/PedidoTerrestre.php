<?php

namespace App\Models\Terrestre;

use Carbon\Carbon;
use App\Models\Cliente;
use App\Models\Terrestre\Vehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Terrestre\PedidoTerrestre;
use App\Models\Terrestre\TipoProductoTerrestre;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoTerrestre extends Model
{
    use HasFactory;

    protected $table = 'pedidos_terrestres';

    protected $fillable = [
        'cliente_id',
        'tipo_producto_id',
        'cantidad_producto',
        'fecha_registro',
        'fecha_entrega',
        'bodega_id',
        'precio_envio',
        'descuento',
        'vehiculo_id',
        'guia',
        'estado',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoProducto(){
        return $this->belongsTo(TipoProductoTerrestre::class, 'tipo_producto_id');
    }

    public function bodega(){
        return $this->belongsTo(PedidoTerrestre::class, 'bodega_id');
    }

    public function vehiculo(){
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public static function getLightList(){
        $list = PedidoTerrestre::select(
                'id',
                'guia AS nombre',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('pedidos_terrestres AS mt')
            ->join('clientes AS t1', 't1.id', 'mt.cliente_id')
            ->join('tipos_productos_terrestres AS t2', 't2.id', 'mt.tipo_producto_id')
            ->join('bodegas AS t3', 't3.id', 'mt.bodega_id')
            ->join('vehiculos AS t4', 't4.id', 'mt.vehiculo_id')
            ->select(
                'mt.id',
                'mt.guia',
                'mt.cantidad_producto',
                'mt.fecha_registro',
                'mt.fecha_entrega',
                'mt.precio_envio',
                'mt.descuento',
                'mt.estado',
                't1.nombre AS cliente',
                't1.id AS cliente_id',
                't2.nombre AS tipo_producto',
                't2.id AS tipo_producto_id',
                't3.nombre AS bodega',
                't3.id AS bodega_id',
                't4.placa AS vehiculo',
                't4.id AS vehiculo_id',
                'mt.created_at AS fecha_creacion',
                'mt.updated_at AS fecha_modificacion'
            );

        if(isset($dto['guia'])){
            $query->where('mt.guia', 'like', '%' . $dto['guia'] . '%');
        }
        if(isset($dto['fechaInicial'])){
            $query->where('mt.fecha_registro', '>=', $dto['fechaInicial']);
        }
        if(isset($dto['fechaFinal'])){
            $query->where('mt.fecha_registro', '<=', $dto['fechaFinal']);
        }
        if(isset($dto['estado'])){
            $query->where('mt.estado', $dto['estado']);
        }
        if(isset($dto['cliente'])){
            $query->where('mt.cliente_id', $dto['cliente']);
        }
        if(isset($dto['producto'])){
            $query->where('mt.tipo_producto_id', $dto['producto']);
        }
        $query->orderBy("mt.updated_at", "desc");

        $pedidos = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($pedidos ?? [] as $pedido){
            array_push($data, $pedido);
        }

        $cantidadPedidos = count($pedidos);
        $to = isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->currentPage() * $pedidos->perPage() : null;
        $to = isset($to) && isset($pedidos) && $to > $pedidos->total() && $cantidadPedidos> 0 ? $pedidos->total() : $to;
        $from = isset($to) && isset($pedidos) && $cantidadPedidos > 0 ?
            ( $pedidos->perPage() > $to ? 1 : ($to - $cantidadPedidos) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($pedidos) && $cantidadPedidos > 0 ? +$pedidos->perPage() : 0,
            'pagina_actual' => isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->currentPage() : 1,
            'ultima_pagina' => isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->lastPage() : 0,
            'total' => isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->total() : 0
        ];
    }

    public static function show($id)
    {
        $pedido = PedidoTerrestre::find($id);
        $cliente = $pedido->cliente;
        $tipoProducto = $pedido->tipoProducto;
        $bodega = $pedido->bodega;
        $vehiculo = $pedido->vehiculo;

        return [
            'id' => $pedido->id,
            'cantidad_producto' => $pedido->cantidad_producto,
            'fecha_registro' => $pedido->fecha_registro,
            'fecha_entrega' => $pedido->fecha_entrega,
            'precio_envio' => $pedido->precio_envio,
            'descuento' => $pedido->descuento,
            'estado' => $pedido->estado,
            'guia' => $pedido->guia,
            'fecha_creacion' => (new Carbon($pedido->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($pedido->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
            'tipoProducto' => isset($tipoProducto) ? [
                'id' => $tipoProducto->id,
                'nombre' => $tipoProducto->nombre
            ] : null,
            'bodega' => isset($bodega) ? [
                'id' => $bodega->id,
                'nombre' => $bodega->nombre
            ] : null,
            'vehiculo' => isset($vehiculo) ? [
                'id' => $vehiculo->id,
                'placa' => $vehiculo->placa
            ] : null,
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $pedido = isset($dto['id']) ? PedidoTerrestre::find($dto['id']) : new PedidoTerrestre();
        if(!isset($dto['id'])){
            while(true){
                $guia = strtoupper(substr(uniqid(), 0, 10));
                $exist = PedidoTerrestre::where('guia', $guia)->first();
                if(!$exist){
                    break;
                }
            }
            $dto['guia'] = $guia;
        }
        $pedido->fill($dto);
        $pedido->save();
        return PedidoTerrestre::show($pedido->id);
    }

    public static function destroy($id)
    {
        // find the object
        $pedido = PedidoTerrestre::find($id);
        return $pedido->delete();
    }
}
