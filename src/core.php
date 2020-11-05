<?php

namespace euroglas\eurorest;

class core implements restModuleInterface
{
    // Nombre oficial del modulo
    public function name() { return "Core"; }

    // Descripcion del modulo
    public function description() { return "Módulo base"; }

    // Regresa un arreglo con los permisos del modulo
    // (Si el modulo no define permisos, debe regresar un arreglo vacío)
    public function permisos()
    {
        $permisos = array();

        $permisos['_test_'] = 'Permiso para pruebas';

        return $permisos;
    }

    // Regresa un arreglo con las rutas del modulo
    public function rutas()
    {

        $items['/ping']['GET'] = array(
            'name' => 'Are we up?',
            'callback' => 'ping',
            'token_required' => FALSE,
        );
        $items['/core/modulos']['GET'] = array(
            'name' => 'lista modulos',
            'callback' => 'listaModulos',
            'token_required' => TRUE,
        );
        $items['/core/permisos']['GET'] = array(
            'name' => 'lista permisos',
            'callback' => 'listaPermisos',
            'token_required' => TRUE,
        );
        $items['/core/urls']['GET'] = array(
            'name' => 'lista URLs',
            'callback' => 'listaURLs',
            'token_required' => TRUE,
        );

        $items['/buscar/[K:needle]']['GET'] = array(
            'name' => 'Buscar',
            'callback' => 'buscar',
            'token_required' => TRUE,
        );
        $items['/core/server/name']['GET'] = array(
            'name' => 'Nombre del servidor',
            'callback' => 'serverName',
            'token_required' => FALSE,
        );

        return $items;
    }

    public function ping()
    {
        die( "pong" );
    }

}
