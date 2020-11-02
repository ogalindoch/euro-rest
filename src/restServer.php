<?php

namespace euroglas\eurorest;

class restServer 
{
    private $configPath;
    private $config;
    private $router;

    // Instancias de cada uno de los modulos
    private $modulos = array();

    // Permisos definidos por los modulos
    private $permisos = array();

    // Rutas definidas por los modulos
    private $rutas = array();

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
        // Encabezados básicos (antes de enviar cualquier cosa, para evitar el error de que ya se había mandado algo)
        header('Access-Control-Allow-Origin: *'); // CORS (Cross-Origin Resource Sharing) desde cualquier origen
        header('Access-Control-Expose-Headers: content-type, Authorization, ETag, If-None-Match'); // Algunos otros encabezados que necesitamos

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

        print("Cargando configuración desde {$this->configPath}".PHP_EOL);
        $this->config= parse_ini_file($this->configPath,true);

        print("Configuración:\n");
        print("<pre>\n");
        print_r($this->config);
        print("</pre>\n");

        // Inicializa el ruteador
        $this->router = new \AltoRouter();

        $this->cargaModulos();
    }

    // Obten una copia de la configuracion (posiblemente para pasar a otros modulos)
    public function Config()
    {
        return $this->config;
    }

    private function cargaModulos()
    {
        $listaDeModulos = array_merge(array('core'=>1), $this->config['Modulos']);

        foreach ($listaDeModulos as $modName => $isEnabled) {
            
            if( $isEnabled )
            {
                // Valida que existe una clase con el nombre del modulo (en minusculas)
                if( class_exists( $modName ) === false )
                {
                    error_log("No esta definida la clase {$modName}.");
                }

                // Crea una instancia de la clase del modulo
                $className = "\\euroglas\\eurorest\\" . $modName;
                $modInstance = new $className();

                // ok, sí existe, pero implementa la interfaz de modulos?
                if( ($modInstance instanceof restModuleInterface) === false )
                {
                    error_log("La clase {$modName} no implementa restModuleInterface, no la podemos usar.");
                }

                cargaPermisos( $modName, $modInstance );

                cargaRutas( $modName, $modInstance );

                // Guarda una referencia a la instancia del modulo
                $this->modulos[$modName] = $modInstance;

                print( $modInstance->name() . " : " . $modInstance->description()  );
            }
        }

        print_r($this->permisos);
        print_r($this->rutas);
    }

    //
    private function cargaPermisos( $modName, $modInstance )
    {
        if( $modInstance instanceof restModuleInterface )
        {
            $this->permisos[$modName] = $modInstance->permisos();
        } else {
            // No hay nada que hacer, el objeto no es del tipo esperado
        }
    }

    //
    private function cargaRutas( $modName, $modInstance )
    {
        if( $modInstance instanceof restModuleInterface )
        {
            $this->rutas[$modName] = $modInstance->rutas();
        } else {
            // No hay nada que hacer, el objeto no es del tipo esperado
        }
    }
}
