<?php

namespace App\Models\Maritimo;

use Carbon\Carbon;
use App\Models\Maritimo\Puerto;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Puerto extends Model
{
    use HasFactory;

    protected $table = 'puertos';

    protected $fillable = [
        'nombre',
        'direccion',
    ];

    public static function getLightList(){
        $list = Puerto::select(
                'id',
                'nombre',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('puertos')
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

        $puertos = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($puertos ?? [] as $puerto){
            array_push($data, $puerto);
        }

        $cantidadPuertos = count($puertos);
        $to = isset($puertos) && $cantidadPuertos > 0 ? $puertos->currentPage() * $puertos->perPage() : null;
        $to = isset($to) && isset($puertos) && $to > $puertos->total() && $cantidadPuertos> 0 ? $puertos->total() : $to;
        $from = isset($to) && isset($puertos) && $cantidadPuertos > 0 ?
            ( $puertos->perPage() > $to ? 1 : ($to - $cantidadPuertos) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($puertos) && $cantidadPuertos > 0 ? +$puertos->perPage() : 0,
            'pagina_actual' => isset($puertos) && $cantidadPuertos > 0 ? $puertos->currentPage() : 1,
            'ultima_pagina' => isset($puertos) && $cantidadPuertos > 0 ? $puertos->lastPage() : 0,
            'total' => isset($puertos) && $cantidadPuertos > 0 ? $puertos->total() : 0
        ];
    }

    public static function show($id)
    {
        $puerto = Puerto::find($id);
        return [
            'id' => $puerto->id,
            'nombre' => $puerto->nombre,
            'direccion' => $puerto->direccion,
            'fecha_creacion' => (new Carbon($puerto->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($puerto->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $puerto = isset($dto['id']) ? Puerto::find($dto['id']) : new Puerto();
        $puerto->fill($dto);
        $puerto->save();
        return Puerto::show($puerto->id);
    }

    public static function destroy($id)
    {
        // find the object
        $puerto = Puerto::find($id);
        return $puerto->delete();
    }
}
