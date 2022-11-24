<?php

if (!function_exists('format_messages_validator')) {
    /**
     * Formatea los mensajes devueltos el validador de una petición
     *
     * @param $validator
     * Validador
     *
     * @return array
     * Lista de mensajes
     */
    function format_messages_validator($validator){
        $messages = [];
        $errorsAttributes = $validator->messages();
        if(isset($errorsAttributes)){
            foreach ($errorsAttributes->messages() as $errorsAttribute){
                foreach ($errorsAttribute as $error){
                    array_push($messages, $error);
                }
            }
        }

        return $messages;
    }
}

if (!function_exists('get_response_body')) {
    /**
     * Formatea el cuerpo de la respuesta para una petición http
     *
     * @param  array|mixed  $messages
     * Mensajes de respuesta
     *
     * @param null $data
     * Datos de respuesta
     *
     * @return array Lista de mensajes
     * Retorna cuerpo para respuesta http
     */
    function get_response_body($messages, $data = null){
        $response = [];
        if(isset($data)){
            $response['datos'] = $data;
        }
        $response['mensajes'] = is_array($messages) ? $messages : [$messages];
        return $response;
    }
}

if (!function_exists('format_order_by_attributes')) {
    /**
     * Formatea los atributos de ordenamiento de una colección
     *
     * @param $data
     * Atributos de ordenamiento
     *
     * @return array
     * Lista de atributos para ordenar
     */
    function format_order_by_attributes($data){
        $orderBys = [];
        $orderBysAux = explode(",", $data['ordenar_por']);
        foreach ($orderBysAux as $orderByExplode){
            $orderByAux = explode(":", $orderByExplode);
            $orderBys[$orderByAux[0]] = $orderByAux[1];
        }

        return $orderBys;
    }
}
