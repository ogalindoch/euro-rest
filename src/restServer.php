<?php

namespace euroglas\eurorest;

use altorouter\altorouter;
use Emarref\Jwt;

class restServer 
{
    private $configPath;
    private $config;
    private $router;

    // Este es un arreglo que normalmente debería estar vacio.
    // se usa para agregar rutas que temporalmente no verifican autorizacion, posiblemente en desarrollo
    private $testSkipAuth = array(
    );

    // Estas son las rutas que Nunca requieren autorizacion 
    // (ojo, son los 'nombres' de las rutas, que se asignan en AltoRouter, no las rutas propiamente dicho)
    private $realskipAuth = array(
        'home', // Una pagina HTML a donde 'caemos' cuando no hay una ruta, es la ruta de inicio
        //'optionsCatchAll',
        'optionsCatchAllNoSlash',
    );


    function __construct($serverMode = "") {
        print "In RestServer constructor\n";

        //
        // Carga la configuración del servidor
        $ApiName = '';
        if( !empty($serverMode) )
        {
            $this->configPath = "servidor.{$serverMode}.ini";
        }
        else
        {
            $this->configPath = 'servidor.ini';
        }

        //print("Cargando configuración desde {$this->configPath}".PHP_EOL);
        $this->config= parse_ini_file($this->configPath,true);

        // Encabezados básicos
        header('Access-Control-Allow-Origin: *'); // CORS (Cross-Origin Resource Sharing) desde cualquier origen
        header('Access-Control-Expose-Headers: content-type, Authorization, ETag, If-None-Match'); // Algunos otros encabezados que necesitamos

        // Inicializa el ruteador
        $this->router = new AltoRouter();

    }

    // Obten una copia de la configuracion (posiblemente para pasar a otros modulos)
    public function Config()
    {
        return $this->config;
    }
}
