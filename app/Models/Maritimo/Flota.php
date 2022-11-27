<?php

namespace App\Models\Maritimo;

use Carbon\Carbon;
use App\Models\Maritimo\Flota;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flota extends Model
{
    use HasFactory;

    protected $table = 'flotas';

    protected $fillable = [
        'nombre',
        'numero',
    ];

    public static function getLightList(){
        $list = Flota::select(
                'id',
                'numero AS nombre',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('flotas')
            ->select(
                'id',
                'nombre',
                'numero',
                'created_at AS fecha_creacion',
                'updated_at AS fecha_modificacion'
            );
        if(isset($dto['numero'])){
            $query->where('numero', 'like', '%' . $dto['numero'] . '%');
        }
        $query->orderBy("updated_at", "desc");

        $flotas = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($flotas ?? [] as $flota){
            array_push($data, $flota);
        }

        $cantidadFlotas = count($flotas);
        $to = isset($flotas) && $cantidadFlotas > 0 ? $flotas->currentPage() * $flotas->perPage() : null;
        $to = isset($to) && isset($flotas) && $to > $flotas->total() && $cantidadFlotas> 0 ? $flotas->total() : $to;
        $from = isset($to) && isset($flotas) && $cantidadFlotas > 0 ?
            ( $flotas->perPage() > $to ? 1 : ($to - $cantidadFlotas) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($flotas) && $cantidadFlotas > 0 ? +$flotas->perPage() : 0,
            'pagina_actual' => isset($flotas) && $cantidadFlotas > 0 ? $flotas->currentPage() : 1,
            'ultima_pagina' => isset($flotas) && $cantidadFlotas > 0 ? $flotas->lastPage() : 0,
            'total' => isset($flotas) && $cantidadFlotas > 0 ? $flotas->total() : 0
        ];
    }

    public static function show($id)
    {
        $flota = Flota::find($id);
        return [
            'id' => $flota->id,
            'nombre' => $flota->nombre,
            'numero' => $flota->numero,
            'fecha_creacion' => (new Carbon($flota->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($flota->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $flota = isset($dto['id']) ? Flota::find($dto['id']) : new Flota();
        $flota->fill($dto);
        $flota->save();
        return Flota::show($flota->id);
    }

    public static function destroy($id)
    {
        // find the object
        $flota = Flota::find($id);
        return $flota->delete();
    }
}
