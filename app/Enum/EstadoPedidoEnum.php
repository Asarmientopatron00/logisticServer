<?php namespace App\Enum;

use ReflectionClass;

class EstadoPedidoEnum{
  const OPTIONS = ['P', 'F'];

  public static function obtenerOpciones() {
    $oClass = new ReflectionClass(EstadoPedidoEnum::class);
    $constants = $oClass->getConstants();
    return array_values($constants);
  }
}