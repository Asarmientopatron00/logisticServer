<?php namespace App\Enum;

use ReflectionClass;

class TipodocumentoEnum{
  const OPTIONS = ['CC', 'NI', 'PS', 'CE', 'TI'];

  public static function obtenerOpciones() {
    $oClass = new ReflectionClass(TipodocumentoEnum::class);
    $constants = $oClass->getConstants();
    return array_values($constants);
  }
}