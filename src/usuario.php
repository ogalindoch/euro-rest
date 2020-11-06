<?php

namespace euroglas\eurorest;

class core implements restModuleInterface
{
    // Nombre oficial del modulo
    public function name() { return "Usuario"; }

    // Descripcion del modulo
    public function description() { return "Módulo usuarios"; }

    // Regresa un arreglo con los permisos del modulo
    // (Si el modulo no define permisos, debe regresar un arreglo vacío)
    public function permisos()
    {
        $permisos = array();

        // $permisos['_test_'] = 'Permiso para pruebas';

        return $permisos;
    }

    // Regresa un arreglo con las rutas del modulo
    public function rutas()
    {
        $items['/auth']['GET'] = array(
            'name' => 'Auth vía GET',
            'callback' => 'getValidaCredenciales',
            'token_required' => FALSE,
        );
        $items['/auth']['POST'] = array(
            'name' => 'Valida las credenciales',
            'callback' => 'postValidaCredenciales',
            'token_required' => FALSE,
        );

        return $items;
    }

    public function getValidaCredenciales()
    {
        http_response_code(405); // 405 Method Not Allowed
        header('Access-Control-Allow-Origin: *');
        header('content-type: application/json');
        die(json_encode( array(
            'codigo' => 405,
            'mensaje' => 'Metodo no permitido',
            'descripcion' => "No puedes usar GET para validar credenciales. Prueba usando POST."
        )));
    }

    public function postValidaCredenciales() 
    {
        // Determina cómo vamos a autenticar al usuario:
        // key - Usando una llave pre-definida (un GUID de cliente)
        // usermail+password - Correo y contraseña
        // username+password - Usuario y contraseña

        if(isset($_REQUEST['key']))
        {
            // Validamos usando un LicenseKey
            $key = $_REQUEST['key'];

            $userName = null;

            switch (trim($key)) {
                case 'GYSTZ-U724P-TJB3W-BQ48R-6ZORU':
                    $userName = 'Octavio Galindo';
                    break;
                case 'IRZAJ-IJL5B-Z1MGJ-RJSL8-O0BMA':
                    $userName = 'TheRing';
                    break;
                case 'UGIWM-W7JEX-T6YBH-4WNLH-BGV7F':
                    $userName = 'Kevin Torruco';
                    break;
                case 'IRZAJ-IJL5B-Z1MGJ-RJSL8-O0BMA':
                    $userName = 'Vigilancia';
                    break;
                case 'JMWC0-70LXL-62RLC-7SNJD-G7XTR':
                    $userName = 'EuroApiClient';
                    break;
                case '50KXS-8ER8P-C6CLJ-5RBJ0-ZU08O':
                    $userName = 'Intranet';
                    break;
                case 'U1TOB-JDJHN-178YQ-EFPIT-NY89S':
                    $userName = 'ClienteVIP';
                    break;
                case 'HIJ96-2NHZJ-SUFZJ-J4JS4-UBFWM-LOY5N':
                    $userName = 'NO USAR';
                    break;
                case 'DE8VC-3413X-GESYY-F2B2C-HLWGR-ME7BQ':
                    $userName = 'AUTOMATED TESTING';
                    break;
                default:
                    $userName = false;
                    break;
            }
            if( empty($userName) )
            {
                throw new ErrorException("Invalid Serial Key: [{$key}]");
            }
    
            $uData = array();
            $uData['login'] = $userName;
            $uData['vrfy'] = 'key';
    
        
        } elseif ( isset($_REQUEST['usermail']) && isset($_REQUEST['password']) ) {

        } elseif ( isset($_REQUEST['username']) &&  isset($_REQUEST['password'])) {

        } else {
            http_response_code(400); // 400 Bad Request
            header('Access-Control-Allow-Origin: *');
            header('content-type: application/json');
            die(json_encode( array(
                'codigo' => 400,
                'mensaje' => 'No se pudo validar su identidad',
                'descripcion' => 'Insuficiente información para validar su identidad. ¿Olvidaste incluir el parametro key ó username/password ó usermail/password?',
                'detalles' => $_REQUEST
            )));
        }
    }
}
