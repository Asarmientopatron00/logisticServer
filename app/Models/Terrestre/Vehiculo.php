<?php

namespace App\Models\Terrestre;

use Carbon\Carbon;
use App\Models\Terrestre\Vehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'placa',
        'marca',
        'modelo',
    ];

    public static function getLightList(){
        $list = Vehiculo::select(
                'id',
                'placa AS nombre',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('vehiculos')
            ->select(
                'id',
                'placa',
                'marca',
                'modelo',
                'created_at AS fecha_creacion',
                'updated_at AS fecha_modificacion'
            );
        if(isset($dto['placa'])){
            $query->where('placa', 'like', '%' . $dto['placa'] . '%');
        }
        $query->orderBy("updated_at", "desc");

        $vehiculos = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($vehiculos ?? [] as $vehiculo){
            array_push($data, $vehiculo);
        }

        $cantidadBodegas = count($vehiculos);
        $to = isset($vehiculos) && $cantidadBodegas > 0 ? $vehiculos->currentPage() * $vehiculos->perPage() : null;
        $to = isset($to) && isset($vehiculos) && $to > $vehiculos->total() && $cantidadBodegas> 0 ? $vehiculos->total() : $to;
        $from = isset($to) && isset($vehiculos) && $cantidadBodegas > 0 ?
            ( $vehiculos->perPage() > $to ? 1 : ($to - $cantidadBodegas) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($vehiculos) && $cantidadBodegas > 0 ? +$vehiculos->perPage() : 0,
            'pagina_actual' => isset($vehiculos) && $cantidadBodegas > 0 ? $vehiculos->currentPage() : 1,
            'ultima_pagina' => isset($vehiculos) && $cantidadBodegas > 0 ? $vehiculos->lastPage() : 0,
            'total' => isset($vehiculos) && $cantidadBodegas > 0 ? $vehiculos->total() : 0
        ];
    }

    public static function show($id)
    {
        $vehiculo = Vehiculo::find($id);
        return [
            'id' => $vehiculo->id,
            'placa' => $vehiculo->placa,
            'marca' => $vehiculo->marca,
            'modelo' => $vehiculo->modelo,
            'fecha_creacion' => (new Carbon($vehiculo->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($vehiculo->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $vehiculo = isset($dto['id']) ? Vehiculo::find($dto['id']) : new Vehiculo();
        $vehiculo->fill($dto);
        $vehiculo->save();
        return Vehiculo::show($vehiculo->id);
    }

    public static function destroy($id)
    {
        // find the object
        $vehiculo = Vehiculo::find($id);
        return $vehiculo->delete();
    }
}
