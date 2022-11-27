<?php

namespace App\Models\Maritimo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Maritimo\TipoProductoMaritimo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoProductoMaritimo extends Model
{
    use HasFactory;

    protected $table = 'tipos_productos_maritimos';

    protected $fillable = [
        'codigo',
        'nombre',
        'precio_unitario',
    ];

    public static function getLightList(){
        $list = TipoProductoMaritimo::select(
                'id',
                'codigo',
                'nombre',
                'precio_unitario',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('tipos_productos_maritimos')
            ->select(
                'id',
                'codigo',
                'nombre',
                'precio_unitario',
                'created_at AS fecha_creacion',
                'updated_at AS fecha_modificacion'
            );
        if(isset($dto['nombre'])){
            $query->where('nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        $query->orderBy("updated_at", "desc");

        $tiposProductosMaritimos = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($tiposProductosMaritimos ?? [] as $tipoProductoMaritimo){
            array_push($data, $tipoProductoMaritimo);
        }

        $cantidadTPT = count($tiposProductosMaritimos);
        $to = isset($tiposProductosMaritimos) && $cantidadTPT > 0 ? $tiposProductosMaritimos->currentPage() * $tiposProductosMaritimos->perPage() : null;
        $to = isset($to) && isset($tiposProductosMaritimos) && $to > $tiposProductosMaritimos->total() && $cantidadTPT> 0 ? $tiposProductosMaritimos->total() : $to;
        $from = isset($to) && isset($tiposProductosMaritimos) && $cantidadTPT > 0 ?
            ( $tiposProductosMaritimos->perPage() > $to ? 1 : ($to - $cantidadTPT) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($tiposProductosMaritimos) && $cantidadTPT > 0 ? +$tiposProductosMaritimos->perPage() : 0,
            'pagina_actual' => isset($tiposProductosMaritimos) && $cantidadTPT > 0 ? $tiposProductosMaritimos->currentPage() : 1,
            'ultima_pagina' => isset($tiposProductosMaritimos) && $cantidadTPT > 0 ? $tiposProductosMaritimos->lastPage() : 0,
            'total' => isset($tiposProductosMaritimos) && $cantidadTPT > 0 ? $tiposProductosMaritimos->total() : 0
        ];
    }

    public static function show($id)
    {
        $tipoProductoMaritimo = TipoProductoMaritimo::find($id);
        return [
            'id' => $tipoProductoMaritimo->id,
            'codigo' => $tipoProductoMaritimo->codigo,
            'nombre' => $tipoProductoMaritimo->nombre,
            'precio_unitario' => $tipoProductoMaritimo->precio_unitario,
            'fecha_creacion' => (new Carbon($tipoProductoMaritimo->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($tipoProductoMaritimo->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $tipoProductoMaritimo = isset($dto['id']) ? TipoProductoMaritimo::find($dto['id']) : new TipoProductoMaritimo();
        $tipoProductoMaritimo->fill($dto);
        $tipoProductoMaritimo->save();
        return TipoProductoMaritimo::show($tipoProductoMaritimo->id);
    }

    public static function destroy($id)
    {
        // find the object
        $tipoProductoMaritimo = TipoProductoMaritimo::find($id);
        return $tipoProductoMaritimo->delete();
    }
}
