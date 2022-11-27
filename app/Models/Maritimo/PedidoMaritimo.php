<?php

namespace App\Models\Maritimo;

use Carbon\Carbon;
use App\Models\Cliente;
use App\Models\Maritimo\Vehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Maritimo\PedidoMaritimo;
use App\Models\Maritimo\TipoProductoTerrestre;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoMaritimo extends Model
{
    use HasFactory;

    protected $table = 'pedidos_maritimos';

    protected $fillable = [
        'cliente_id',
        'tipo_producto_id',
        'cantidad_producto',
        'fecha_registro',
        'fecha_entrega',
        'puerto_id',
        'precio_envio',
        'descuento',
        'flota_id',
        'guia',
        'estado',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoProducto(){
        return $this->belongsTo(TipoProductoMaritimo::class, 'tipo_producto_id');
    }

    public function puerto(){
        return $this->belongsTo(Puerto::class, 'puerto_id');
    }

    public function flota(){
        return $this->belongsTo(Flota::class, 'flota_id');
    }

    public static function getLightList(){
        $list = PedidoMaritimo::select(
                'id',
                'guia AS nombre',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('pedidos_maritimos AS mt')
            ->join('clientes AS t1', 't1.id', 'mt.cliente_id')
            ->join('tipos_productos_maritimos AS t2', 't2.id', 'mt.tipo_producto_id')
            ->join('puertos AS t3', 't3.id', 'mt.puerto_id')
            ->join('flotas AS t4', 't4.id', 'mt.flota_id')
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
                't3.nombre AS puerto',
                't3.id AS puerto_id',
                't4.numero AS flota',
                't4.id AS flota_id',
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
        $pedido = PedidoMaritimo::find($id);
        $cliente = $pedido->cliente;
        $tipoProducto = $pedido->tipoProducto;
        $puerto = $pedido->puerto;
        $flota = $pedido->flota;

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
            'puerto' => isset($puerto) ? [
                'id' => $puerto->id,
                'nombre' => $puerto->nombre
            ] : null,
            'flota' => isset($flota) ? [
                'id' => $flota->id,
                'placa' => $flota->placa
            ] : null,
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $pedido = isset($dto['id']) ? PedidoMaritimo::find($dto['id']) : new PedidoMaritimo();
        if(!isset($dto['id'])){
            while(true){
                $guia = strtoupper(substr(uniqid(), 0, 10));
                $exist = PedidoMaritimo::where('guia', $guia)->first();
                if(!$exist){
                    break;
                }
            }
            $dto['guia'] = $guia;
        }
        $pedido->fill($dto);
        $pedido->save();
        return PedidoMaritimo::show($pedido->id);
    }

    public static function destroy($id)
    {
        // find the object
        $pedido = PedidoMaritimo::find($id);
        return $pedido->delete();
    }
}
