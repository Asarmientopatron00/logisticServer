<?php

namespace App\Models\Terrestre;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Terrestre\TipoProductoTerrestre;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoProductoTerrestre extends Model
{
    use HasFactory;

    protected $table = 'tipos_productos_terrestres';

    protected $fillable = [
        'codigo',
        'nombre',
        'precio_unitario',
    ];

    public static function getLightList(){
        $list = TipoProductoTerrestre::select(
                'id',
                'codigo',
                'nombre',
                'precio_unitario',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('tipos_productos_terrestres')
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

        $tiposProductosTerrestres = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($tiposProductosTerrestres ?? [] as $tipoProductoTerrestre){
            array_push($data, $tipoProductoTerrestre);
        }

        $cantidadTPT = count($tiposProductosTerrestres);
        $to = isset($tiposProductosTerrestres) && $cantidadTPT > 0 ? $tiposProductosTerrestres->currentPage() * $tiposProductosTerrestres->perPage() : null;
        $to = isset($to) && isset($tiposProductosTerrestres) && $to > $tiposProductosTerrestres->total() && $cantidadTPT> 0 ? $tiposProductosTerrestres->total() : $to;
        $from = isset($to) && isset($tiposProductosTerrestres) && $cantidadTPT > 0 ?
            ( $tiposProductosTerrestres->perPage() > $to ? 1 : ($to - $cantidadTPT) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($tiposProductosTerrestres) && $cantidadTPT > 0 ? +$tiposProductosTerrestres->perPage() : 0,
            'pagina_actual' => isset($tiposProductosTerrestres) && $cantidadTPT > 0 ? $tiposProductosTerrestres->currentPage() : 1,
            'ultima_pagina' => isset($tiposProductosTerrestres) && $cantidadTPT > 0 ? $tiposProductosTerrestres->lastPage() : 0,
            'total' => isset($tiposProductosTerrestres) && $cantidadTPT > 0 ? $tiposProductosTerrestres->total() : 0
        ];
    }

    public static function show($id)
    {
        $tipoProductoTerrestre = TipoProductoTerrestre::find($id);
        return [
            'id' => $tipoProductoTerrestre->id,
            'codigo' => $tipoProductoTerrestre->codigo,
            'nombre' => $tipoProductoTerrestre->nombre,
            'precio_unitario' => $tipoProductoTerrestre->precio_unitario,
            'fecha_creacion' => (new Carbon($tipoProductoTerrestre->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($tipoProductoTerrestre->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $tipoProductoTerrestre = isset($dto['id']) ? TipoProductoTerrestre::find($dto['id']) : new TipoProductoTerrestre();
        $tipoProductoTerrestre->fill($dto);
        $tipoProductoTerrestre->save();
        return TipoProductoTerrestre::show($tipoProductoTerrestre->id);
    }

    public static function destroy($id)
    {
        // find the object
        $tipoProductoTerrestre = TipoProductoTerrestre::find($id);
        return $tipoProductoTerrestre->delete();
    }
}
