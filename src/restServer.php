<?php

namespace euroglas\eurorest;

use Emarref\Jwt\Claim;

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

        //print("Cargando configuración desde {$this->configPath}".PHP_EOL);
        $this->config= parse_ini_file($this->configPath,true);

        //print("Configuración:\n");
        //print("<pre>\n");
        //print_r($this->config);
        //print("</pre>\n");

        $this->cargaModulos();

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
        }

        /// Mapea las rutas definidas por los modulos
        {
            foreach( $this->rutas as $modName => $modRutas )
            {
                foreach ($modRutas as $ruta => $metodos) {
                    foreach ($metodos as $metodo => $values) {
                        $callback = $modName . '|' . $values['callback'];

                        $this->router->map( $metodo, $ruta, $callback, $values['callback'] );

                        // Actualiza la lista de URLs que no requieren validacion
                        if( $values['token_required'] == FALSE )
                        {
                            $this->realskipAuth[] = $values['name'];
                        }
                    }
                }
            }

            print("Rutas:\n");
            print("<pre>\n");
            print_r($this->router->getRoutes());
            print("</pre>\n");

        }
    }

    public function matchAndProcess()
    {
        $match = $this->router->match();
        $skipAuth = array_merge($this->testSkipAuth,$this->realskipAuth);

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

            // La instancia del modulo (se creo cuando se cargaron los modulos)
            $modObj = $this->modulos[$callPieces[0]];

            $callArray = array( $this->modulos[$callPieces[0]], $callPieces[1]); 

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
                            static::authFromJWT( $serializedToken );
                        } catch (Exception $e) {
                            $errCode = 401101;
                            $errMsg = str_replace('"', '', $e->getMessage() );

                            // Si expiro el token, usamos codigo 401102
                            if( strpos($errMsg,'Token expired') !== false ) $errCode = 401102;

                            http_response_code(401); // 401 Unauthorized
                            header('Access-Control-Allow-Origin: *');
                            header('content-type: application/json');
                            die(json_encode( array(
                                'codigo' => $errCode,
                                'mensaje' => 'Token Error',
                                'descripcion' => $errMsg,
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
                die("El callback definido no parece ser ejecutable: ".$match['target']);
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

                $this->cargaPermisos( $modName, $modInstance );

                $this->cargaRutas( $modName, $modInstance );

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
        include_once('./home/index.php');
        die();
    }

    private static $_Secreto = 'B63D8E6CDE343BFAECD4F2C9161C63CB'; // Guid generado al azar, por seguridad

	private static function getEncription()
	{
		$algorithm = new Emarref\Jwt\Algorithm\Hs256(static::$_Secreto);
		$encryption = Emarref\Jwt\Encryption\Factory::create($algorithm);

		return $encryption;
	}

    private static function authFromJWT( $serializedToken )
    {
        $jwt = new Emarref\Jwt\Jwt();
        $token = $jwt->deserialize($serializedToken);

        // Este es el contexto con el que se va a validar el Token
        $context = new Emarref\Jwt\Verification\Context( static::getEncription() );
        $context->setIssuer($_SERVER["SERVER_NAME"]);
		$context->setSubject('eurorest');
		$options = array();

        // Normalmente aqui usaría un try/catch,
		// pero al final de nuevo lanzaría una excepcion.
		// Mejor voy a dejar que la excepcion se propague.

        $jwt->verify($token, $context);

        $nombreClaim = $token->getPayload()->findClaimByName('Name');

	    if($nombreClaim !== null)
	    {
	    	$UserName = $nombreClaim->getValue();
	    	$User["username"] = $nombreClaim->getValue();
	    }
	    else
	    {
	    	$UserName = null;
	    	$User = null;
	    }

	    $autoRenewClaim = $token->getPayload()->findClaimByName('Autorenew');
	    if($autoRenewClaim !== null)
	    {
	    	$options["Autorenew"] = $autoRenewClaim->getValue();
        }
	    $renewTimeClaim = $token->getPayload()->findClaimByName('RTime');
	    if($renewTimeClaim !== null)
	    {
	    	$options['RTime'] = $renewTimeClaim->getValue();
        }
        
		$options['vrfy'] = null;
	    $vrfyClaim = $token->getPayload()->findClaimByName('vrfy');
	    if($vrfyClaim !== null)
	    {
	    	$options['vrfy'] = $vrfyClaim->getValue();
	    	$vrfyClaimValue = $options['vrfy'];
	    	switch ($vrfyClaimValue) {
	    		case 'key':
	    		case 'email':
	    		case 'ldap':
	    			// Omite las validaciones por ahora
	    			break;

	    		default:
	    			http_response_code(401); // 401 Unauthorized
	    			header('content-type: application/json');
                    die(json_encode( array(
                        'codigo' => 401111,
                        'mensaje' => 'Vrfy Code Error',
                        'descripcion' => "El codigo VRFY no es reconocido",
                        'detalles' => $vrfyClaimValue
                    )));
	    			break;
	    	}
	    }

		$uData = array();
		$uData['vrfy'] = $options['vrfy'];
		$uData['login'] = $nombreClaim->getValue();
        static::$instance = new static($uData);

	    // Autorenew debe ser el ultimo, ya que tengamos todo lo necesario en Options
	    if( isset( $options["Autorenew"] ) && $options["Autorenew"] == true )
	    {
	    	$newToken = static::generaToken($nombreClaim->getValue(), $options);
	    	//header("Access-Control-Expose-Headers","New-JWT-Token");
	    	header("Authorization: {$newToken}");
	    }
    }

	public static function generaToken($clientName=null, $options = array() )
	{
		$token = new Emarref\Jwt\Token();

		if( ! empty( $options['RTime'] ) )
		{
			$token->addClaim(new Claim\Expiration(new \DateTime($options['RTime'])));
		} else {
			$expirationTime = '100 minutes';
			//if(!empty($Config['usuarios']['JWT_Expiration']))
			//{
			//	$expirationTime = $Config['usuarios']['JWT_Expiration'];
			//}
			$token->addClaim(new Claim\Expiration(new \DateTime($expirationTime)));
		}

		$token->addClaim(new Claim\IssuedAt(new \DateTime('now')));
		$token->addClaim(new Claim\Issuer($_SERVER["SERVER_NAME"]));
		$token->addClaim(new Claim\JwtId(time()));
		$token->addClaim(new Claim\NotBefore(new \DateTime('now')));
		$token->addClaim(new Claim\Subject('Euroglas'));

		//$token->addClaim(new Claim\PrivateClaim('ClientName', $clientName));
		$token->addClaim(new Claim\PrivateClaim('Name', $clientName));

		foreach ($options as $key => $value) {
			if( $key == 'Expiration' ) continue; // ya checamos expiration arriba
			$token->addClaim(new Claim\PrivateClaim($key, $value));
		}

		$encryption = static::getEncription();
		$jwt = new Emarref\Jwt\Jwt();
		$serializedToken = $jwt->serialize($token, $encryption);

		return($serializedToken);
	}

}
