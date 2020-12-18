<?php

namespace euroglas\eurorest;

use Emarref\Jwt\Claim;

class restServer 
{
    //private $configPath;

    // Configuración del servidor
    private $config;

    // Administrador de rutas (URLs)
    private $router;

    // Nombre de la API/Servidor
    private $ApiName;

    // Objeto que maneja la autenticacion (debe implementar authInterface)
    private $authHandler;

    // Instancias de cada uno de los modulos
    private $modulos = array();

    // Permisos definidos por los modulos
    private $permisos = array();

    // Rutas definidas por los modulos
    private $rutas = array();

    private $modoDebug = FALSE;

    // Este es un arreglo que normalmente debería estar vacio.
    // se usa para agregar rutas que temporalmente no verifican autorizacion, posiblemente en desarrollo
    private $testSkipAuth = array(
    );

    // Estas son las rutas que Nunca requieren autorizacion 
    // (ojo, son los 'nombres' de las rutas, que se asignan en AltoRouter, no las rutas propiamente dicho)
    private $realskipAuth = array(
        'home', // Una pagina HTML a donde 'caemos' cuando no hay una ruta, es la ruta de inicio
        'optionsCatchAllNoSlash', // necesaria para funcioamiento XORS
        'PingPong', // Prueba basica para probar si estamos disponibles
        'Estatus de depuracion', // Muestra el estatus del Modo Debug
    );

    function __construct($serverMode = "") {
        // Encabezados básicos (antes de enviar cualquier cosa, para evitar el error de que ya se había mandado algo)
        header('Access-Control-Allow-Origin: *'); // CORS (Cross-Origin Resource Sharing) desde cualquier origen
        header('Access-Control-Expose-Headers: content-type, Authorization, ETag, If-None-Match'); // Algunos otros encabezados que necesitamos

        //print "In RestServer constructor\n";
        $this->authHandler = NULL;

        //
        // Carga la configuración del servidor
        $this->ApiName = $serverMode;
        if( !empty($serverMode) )
        {
            $configPath = "servidor.{$serverMode}.ini";
        }
        else
        {
            $configPath = 'servidor.ini';
        }

        //print("Cargando configuración desde {$configPath}".PHP_EOL);
        $this->config= parse_ini_file($configPath,true);

        // Guarda el path en la configuración misma
        $this->config['ConfigPath'] = $configPath;

        //print("Configuración:\n");
        //print("<pre>\n");
        //print_r($this->config);
        //print("</pre>\n");

        // error_log( print_r($this->config,true) );

        if( !empty( $this->config['ModoDebug']) && $this->config['ModoDebug'] )
        {
            error_log( "Modo DEBUG activado" );
            $this->modoDebug = TRUE;
        }

        if(!empty($this->config['ServerName']))
        {
            $this->ApiName = $this->config['ServerName'];
        }

        $this->cargaModulos();

        if( empty($this->authHandler) )
        {
            http_response_code(500); // 500 Internal Server Error
            header('Access-Control-Allow-Origin: *');
            header('content-type: application/json');
            die(json_encode( array(
                'codigo' => 500001,
                'mensaje' => 'Servidor mal configurado',
                'descripcion' => 'El servidor no tiene definido un Manejador de Autenticacion'
            )));

        }

        // Inicializa el ruteador
        $this->router = new \AltoRouter();

        /// Define parametros que se pueden aceptar
        {
            //Se agrego que pueda aceptar Variables
            $this->router->addMatchTypes(array('K' => '[_0-9A-Za-z]++'));

            // Un SKU puede tener numeros, letras, puntos, guiones y espacios.
            // El espacio podría estar URLEncoded, por eso el "%20"
            $this->router->addMatchTypes(array('SKU' => '([A-Za-z0-9 \.\-]|%20)++'));
        }

        /// Mapea las URLs absolutamente básicas
        {
            $this->router->map( 'OPTIONS', '[**]', 'optionsCatchAll', 'optionsCatchAllNoSlash' );
            $this->router->map( 'GET', '/', 'render_home', 'home' );
            $this->router->map( 'GET', '/ping', 'ping', 'PingPong');
            $this->router->map( 'GET', '/debug', 'debugOn', 'Estatus de depuracion');

            if( $this->modoDebug )
            {
                $this->router->map( 'GET', '/debug/modulos', 'showModulos', 'Lista de Modulos' );
                $this->router->map( 'GET', '/debug/urls', 'showRutas', 'Lista de Rutas' );
                $this->router->map( 'GET', '/debug/permisos', 'showPermisos', 'Lista de Permisos' );
                $this->router->map( 'GET', '/debug/serverName', 'showApiName', 'Nombre del Servidor' );
                $this->router->map( 'GET', '/debug/config', 'showConfig', 'Muestra la configuracion' );
                $this->router->map( 'GET', '/debug/authProvider', 'showAuthProvider', 'Nombre del modulo Auth');

                // Agrega las rutas debug, a la lista de rutas que NO requieren Token
                $this->realskipAuth[] = 'Lista de Modulos';
                $this->realskipAuth[] = 'Lista de Rutas';
                $this->realskipAuth[] = 'Lista de Permisos';
                $this->realskipAuth[] = 'Nombre del Servidor';
                $this->realskipAuth[] = 'Muestra la configuracion';
                $this->realskipAuth[] = 'Nombre del modulo Auth';

            }
        }

        /// Mapea las rutas definidas por los modulos
        {
            foreach( $this->rutas as $modName => $modRutas )
            {
                foreach ($modRutas as $ruta => $metodos) {
                    foreach ($metodos as $metodo => $values) {
                        $callback = $modName . '|' . $values['callback'];

                        $this->router->map( $metodo, $ruta, $callback, $values['name'] );

                        // Actualiza la lista de URLs que no requieren validacion
                        if( $values['token_required'] == FALSE )
                        {
                            $this->realskipAuth[] = $values['name'];
                        }
                    }
                }
            }

            //print("Rutas:\n");
            //print("<pre>\n");
            //print_r($this->router->getRoutes());
            //print("</pre>\n");

        }
    }

    public function matchAndProcess()
    {
        $match = $this->router->match();
        $skipAuth = array_merge($this->testSkipAuth,$this->realskipAuth);

        //print_r($skipAuth);

        if( is_array($match) ) // altoRouter regresa un arreglo, si se encontro una ruta coincidente
        {
            /*
                array(3) { 
                    ["target"]	=> modName|function 
                    ["params"]	=> array(0) { } 
                    ["name"] 	=> 'home' 
                }
            */

            $callPieces = explode("|", $match["target"]);

            if( count($callPieces) == 2 )
            { 
                // La instancia del modulo (se creo cuando se cargaron los modulos)
                $modObj = $this->modulos[$callPieces[0]];

                $callArray = array( $this->modulos[$callPieces[0]], $callPieces[1]); 
            } else {
                $callArray = array( $this, $match["target"] ) ;
            }

            // intenta hacer la llamada
            if( is_callable($callArray) )
            {
                if( in_array($match['name'], $skipAuth) )
                {
                    // Encontramos una ruta que omite Auth, no hay nada que hacer aquí
                } else {
                    //
                    // La ruta requiere Auth
                    //

                    // Lista de headers
                    //   en minusculas
                    $headers = array_change_key_case(apache_request_headers());

                    if( isset($headers['authorization']) )
                    {
                        if( stripos($headers['authorization'], 'Bearer') === false )
                        {
                            http_response_code(401); // 401 Unauthorized
                            header('Access-Control-Allow-Origin: *');
                            header('content-type: application/json');
                            die(json_encode( array(
                                'codigo' => 401100,
                                'mensaje' => 'Solicitud invalida',
                                'descripcion' => 'El header Authorization debe iniciar con "Bearer"',
                                'detalles' => $headers['authorization']
                            )));

                        }

                        list($serializedToken) = sscanf($headers['authorization'], "Bearer %s");
            
                        try {
                            // Si la siguiente llamada no genera excepcion, 
                            // el token se valido correctamente
                            $this->authHandler->authFromJWT( $serializedToken );

                        } catch (\Exception $e) {
                            $errCode = 401101;
                            $errMsg = "Token Error";
                            $errDesc = str_replace('"', '', $e->getMessage() );

                            // Si expiro el token, usamos codigo 401102
                            if( strpos($errDesc,'Token expired') !== false )
                            {
                                $errCode = 401102;
                            }

                            http_response_code(401); // 401 Unauthorized
                            header('Access-Control-Allow-Origin: *');
                            header('content-type: application/json');
                            die(json_encode( array(
                                'codigo' => $errCode,
                                'mensaje' => 'Token Error',
                                'descripcion' => $errDesc,
                                'detalles' => $serializedToken
                            )));

                        }
                    }
                    else
                    {
                        // Hace falta el header 'authorization', que debería traer el Token
                        http_response_code(401); // 401 Unauthorized
                        header('Access-Control-Allow-Origin: *');
                        header('content-type: application/json');
                        die(json_encode( array(
                            'codigo' => 401001,
                            'mensaje' => 'No autorizado',
                            'descripcion' => 'La solicitud no contenia el Token requerido',
                            'detalles' => $headers
                        )));
                    }            
                }
                // Termina validacion de auth, ahora si llamamos la funcion relacionada a la URL

                call_user_func_array( $callArray, $match['params'] );


            } else {
                http_response_code(501); // 501: Not Implemented
                die("El callback definido no parece ser ejecutable: ".$match['target']."\n<br>". print_r($callArray,true) );
            }

        } else {
            // no route was matched
            http_response_code(404);

            die("No se encontro una ruta para: ".$_SERVER['REQUEST_URI']);
        }

    }

    // Obten una copia de la configuracion (posiblemente para pasar a otros modulos)
    public function Config()
    {
        return $this->config;
    }

    public function SetSecret( $secret )
    {
        $this->authHandler->SetSecret($secret);
    }

    private function cargaModulos()
    {
        $listaDeModulos = $this->config['Modulos'];

        foreach ($listaDeModulos as $modName => $isEnabled) {
            
            if( $isEnabled )
            {
                $className = "\\euroglas\\" . $modName . "\\" . $modName;

                // Valida que existe una clase con el nombre del modulo (en minusculas)
                if( class_exists( $className ) === false )
                {
                    error_log("No esta definida la clase {$modName}.");
                }

                // Crea una instancia de la clase del modulo
                // print($className);
                $modInstance = new $className();

                // ok, sí existe, pero implementa la interfaz de modulos?
                if( ($modInstance instanceof restModuleInterface) === false )
                {
                    throw new \Exception("La clase {$modName} no implementa restModuleInterface, no la podemos usar.");
                }

                // Carga los permisos del modulo
                $this->cargaPermisos( $modName, $modInstance );

                // Carga las rutas del modulo
                $this->cargaRutas( $modName, $modInstance );

                // envía la(s) seccion(es) de configuracion del modulo
                $this->distribuyeConfig( $modName, $modInstance );

                // Guarda una referencia a la instancia del modulo
                $this->modulos[$modName] = $modInstance;

                // Checa si es nuestro administrador de Autenticacion
                if( $modInstance instanceof authInterface )
                {
                    $this->authHandler = $modInstance;
                }
                //print( $modInstance->name() . " : " . $modInstance->description()  );
            }
        }

        //print_r($this->permisos);
        //print_r($this->rutas);
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

    private function distribuyeConfig( $modName, $modInstance )
    {
        if( $modInstance instanceof restModuleInterface )
        {
            // Lista de secciones de config requeridas:
            $seccionesRequeridas = $modInstance->requiereConfig();

            foreach ($seccionesRequeridas as $nombreDeSeccion) {
                if( isset( $this->config[$nombreDeSeccion]))
                {
                    $modInstance->cargaConfig($nombreDeSeccion, $this->config[$nombreDeSeccion] );
                } else {
                    throw new \Exception("La seccion de configuración [{$nombreDeSeccion}] no existe");
                }
            }
        } else {
            // No hay nada que hacer, el objeto no es del tipo esperado
        }
    }

    private function optionsCatchAll($blah=null)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, DELETE, POST, PUT, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: content-type, Authorization, ETag, If-None-Match');
        header('Access-Control-Expose-Headers: content-type, Authorization, ETag, If-None-Match');
        //header('Access-Control-Allow-Headers: authorization');
        die($blah);
    }

    private function render_home() 
    {
        // Cuando estamos en modo de desarrollo, TODO se redirige a index.php, por lo que no podemos enviar al visitante a otra URL.
        // En lugar de eso, vamos a ejecutar el otro archivo desde aquí.
        include_once(__DIR__ . '/home/index.php');
        die();
    }

    public function ping() { die( "pong" ); }
    
    public function showModulos() { 
        $listaDeModulos = $this->config['Modulos'];

        header('content-type: application/json');
        die( json_encode(array_keys($listaDeModulos),true) );
    } 
    public function showConfig() { 
        header('content-type: application/json');
        die( json_encode($this->config,true) ); 
    }
    public function showRutas() { 
        header('content-type: application/json');
        die( json_encode( $this->router->getRoutes() ));
    }
    public function showPermisos() { 
        header('content-type: application/json');
        die( json_encode($this->permisos,true) ); 
    }
    public function showAuthProvider() {
        die( $this->authHandler->name() );
    }
    public function debugOn() { 
        if($this->modoDebug)
        {die( "DepuracionActivada" ); }
        else
        {die( "DepuracionDesActivada" ); }
        
    }
    public function showApiName() { die( $this->ApiName ); }
}
