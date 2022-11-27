<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
        'telefono',
        'email',
        'direccion',
    ];

    public static function getLightList(){
        $list = Cliente::select(
                'id',
                'nombre',
                'numero_documento',
            )
            ->orderBy('nombre', 'asc');
        return $list->get();
    }

    public static function getList($dto){
        $query = DB::table('clientes')
            ->select(
                'id',
                'nombre',
                'numero_documento',
                'tipo_documento',
                'telefono',
                'email',
                'direccion',
                'created_at AS fecha_creacion',
                'updated_at AS fecha_modificacion'
            );
        if(isset($dto['nombre'])){
            $query->where('nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        $query->orderBy("updated_at", "desc");

        $clientes = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($clientes ?? [] as $cliente){
            array_push($data, $cliente);
        }

        $cantidadClientes = count($clientes);
        $to = isset($clientes) && $cantidadClientes > 0 ? $clientes->currentPage() * $clientes->perPage() : null;
        $to = isset($to) && isset($clientes) && $to > $clientes->total() && $cantidadClientes> 0 ? $clientes->total() : $to;
        $from = isset($to) && isset($clientes) && $cantidadClientes > 0 ?
            ( $clientes->perPage() > $to ? 1 : ($to - $cantidadClientes) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($clientes) && $cantidadClientes > 0 ? +$clientes->perPage() : 0,
            'pagina_actual' => isset($clientes) && $cantidadClientes > 0 ? $clientes->currentPage() : 1,
            'ultima_pagina' => isset($clientes) && $cantidadClientes > 0 ? $clientes->lastPage() : 0,
            'total' => isset($clientes) && $cantidadClientes > 0 ? $clientes->total() : 0
        ];
    }

    public static function show($id)
    {
        $cliente = Cliente::find($id);
        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'tipo_documento' => $cliente->tipo_documento,
            'numero_documento' => $cliente->numero_documento,
            'telefono' => $cliente->telefono,
            'email' => $cliente->email,
            'direccion' => $cliente->direccion,
            'fecha_creacion' => (new Carbon($cliente->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($cliente->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        // If an Id is set, is an updte. Otherwise, is a new reg.
        $cliente = isset($dto['id']) ? Cliente::find($dto['id']) : new Cliente();
        $cliente->fill($dto);
        $cliente->save();
        return Cliente::show($cliente->id);
    }

    public static function destroy($id)
    {
        // find the object
        $cliente = Cliente::find($id);
        return $cliente->delete();
    }
}
