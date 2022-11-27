<?php

namespace App\Models\Terrestre;

use Carbon\Carbon;
use App\Models\Terrestre\Bodega;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bodega extends Model
{
    use HasFactory;

    protected $table = 'bodegas';

    protected $fillable = [
        'nombre',
        'direccion',
    ];

    public static function getLightList(){
        $list = Bodega::select(
                'id',
                'nombre',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('bodegas')
            ->select(
                'id',
                'nombre',
                'direccion',
                'created_at AS fecha_creacion',
                'updated_at AS fecha_modificacion'
            );
        if(isset($dto['nombre'])){
            $query->where('nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        $query->orderBy("updated_at", "desc");

        $bodegas = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($bodegas ?? [] as $bodega){
            array_push($data, $bodega);
        }

        $cantidadBodegas = count($bodegas);
        $to = isset($bodegas) && $cantidadBodegas > 0 ? $bodegas->currentPage() * $bodegas->perPage() : null;
        $to = isset($to) && isset($bodegas) && $to > $bodegas->total() && $cantidadBodegas> 0 ? $bodegas->total() : $to;
        $from = isset($to) && isset($bodegas) && $cantidadBodegas > 0 ?
            ( $bodegas->perPage() > $to ? 1 : ($to - $cantidadBodegas) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($bodegas) && $cantidadBodegas > 0 ? +$bodegas->perPage() : 0,
            'pagina_actual' => isset($bodegas) && $cantidadBodegas > 0 ? $bodegas->currentPage() : 1,
            'ultima_pagina' => isset($bodegas) && $cantidadBodegas > 0 ? $bodegas->lastPage() : 0,
            'total' => isset($bodegas) && $cantidadBodegas > 0 ? $bodegas->total() : 0
        ];
    }

    public static function show($id)
    {
        $bodega = Bodega::find($id);
        return [
            'id' => $bodega->id,
            'nombre' => $bodega->nombre,
            'direccion' => $bodega->direccion,
            'fecha_creacion' => (new Carbon($bodega->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($bodega->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $bodega = isset($dto['id']) ? Bodega::find($dto['id']) : new Bodega();
        $bodega->fill($dto);
        $bodega->save();
        return Bodega::show($bodega->id);
    }

    public static function destroy($id)
    {
        // find the object
        $bodega = Bodega::find($id);
        return $bodega->delete();
    }
}
