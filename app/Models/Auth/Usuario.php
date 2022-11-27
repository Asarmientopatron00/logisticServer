<?php

namespace App\Models\Auth;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Auth\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Model
{
    use HasFactory;

    protected $fillable = [
        'identificacion_usuario',
        'nombre',
        'correo_electronico',
        'user_id',
    ];

    public static function show($id)
    {
        $usuario = Usuario::find($id);
        return [
            'id' => $usuario->id,
            'identificacion_usuario' => $usuario->identificacion_usuario,
            'nombre' => $usuario->nombre,
            'email' => $usuario->correo_electronico,
            'fecha_creacion' => (new Carbon($usuario->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($usuario->updated_at))->format("Y-m-d H:i:s"),
        ];
    }

    public static function modifyOrCreate($dto)
    {
        $user = Auth::user(); 
        $usuario = isset($dto['id']) ? Usuario::find($dto['id']) : new Usuario();
        if(!isset($dto['id'])){
            $userSesion = User::create([
                'name' => $dto['nombre'],
                'email' => $dto['identificacion_usuario'],
                'password' => Hash::make($dto['clave'])
            ]);
            if(isset($userSesion)){
                $dto['user_id'] = $userSesion->id;
            }else{
                throw new Exception("Ocurrió un error al intentar guardar el usuario.", $userSesion);
            }
        }else{
            $userSesion = $usuario->user;
            $userSesion->fill([
                'name' => $dto['nombre'],
                'email' => $dto['identificacion_usuario']
            ]);
            $userSesion->save();
        }
        $usuario->fill($dto);
        $usuario->save();
        return Usuario::show($usuario->id);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public static function findBy($identificacionUsuario){
        return Usuario::where('identificacion_usuario', $identificacionUsuario)->first();
    }

    public static function getLightList($dto){        
        $query = DB::table('usuarios')
            ->select(
                'id', 
                'nombre',
            );
        return $query->get();
    }

    public static function getList($dto){
        $query = DB::table('usuarios')
            ->select(
                'usuarios.id',
                'usuarios.identificacion_usuario',
                'usuarios.nombre',
                'usuarios.correo_electronico',
                'usuarios.created_at AS fecha_creacion',
                'usuarios.updated_at AS fecha_modificacion',
            );
        
        if(isset($dto['nombre'])){
            $query->where('usuarios.nombre', 'like', '%' . $dto['nombre'] . '%');
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'id'){
                    $query->orderBy('usuarios.id', $value);
                }
                if($attribute == 'identificacion_usuario'){
                    $query->orderBy('usuarios.identificacion_usuario', $value);
                }
                if($attribute == 'nombre'){
                    $query->orderBy('usuarios.nombre', $value);
                }
                if($attribute == 'email'){
                    $query->orderBy('usuarios.correo_electronico', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('usuarios.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('usuarios.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("usuarios.updated_at", "desc");
        }

        $usuarios = $query->paginate($dto['limite'] ?? 100);
        $datos = [];
        foreach ($usuarios ?? [] as $usuario){
            array_push($datos, $usuario);
        }

        $cantidadUsuarios = count($usuarios ?? []);
        $to = isset($usuarios) && $cantidadUsuarios > 0 ? $usuarios->currentPage() * $usuarios->perPage() : null;
        $to = isset($to) && isset($usuarios) && $to > $usuarios->total() && $cantidadUsuarios > 0 ? $usuarios->total() : $to;
        $from = isset($to) && isset($usuarios) && $cantidadUsuarios > 0 ?
            ( $usuarios->perPage() > $to ? 1 : ($to - $cantidadUsuarios) + 1 )
            : null;
        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($usuarios) && $cantidadUsuarios > 0 ? +$usuarios->perPage() : 0,
            'pagina_actual' => isset($usuarios) && $cantidadUsuarios > 0 ? $usuarios->currentPage() : 1,
            'ultima_pagina' => isset($usuarios) && $cantidadUsuarios > 0 ? $usuarios->lastPage() : 0,
            'total' => isset($usuarios) && $cantidadUsuarios > 0 ? $usuarios->total() : 0
        ];
    }

    public static function cambiarClave($id, $dto){
        $usuario = Usuario::find($id);
        $user = $usuario->user;
        $user->fill([
            'id' => $id,
            'password' => Hash::make($dto['nueva_clave'])
        ]);
        return $user->save();
    }

    public static function destroy($id)
    {
        $usuario = Usuario::find($id);

        // Borrar User
        $user = $usuario->user;
        $usuario->delete();
        return $user->delete();
    }
}
