<?php

namespace euroglas\eurorest;

use Emarref\Jwt\Claim;

abstract class auth implements restModuleInterface , authInterface
{

    // Nombre oficial del modulo
    public function name() { return "auth"; }

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
        $items['/testoken']['GET'] = array(
            'name' => 'Valida un token',
            'callback' => 'testToken',
            'token_required' => TRUE,
        );

        return $items;
    }

    /**
     * Genera un error si se trata de validar credenciales usando el metodo GET
     */
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

    /**
     * Valida las credenciales recibidas
     */
    public function postValidaCredenciales() 
    {
        try {
            // Trata de autenticar al usuario usando los parametros recibidos
            $this->auth( $_REQUEST );
        } catch (\Exception $ex) {
            http_response_code(400); // 400 Bad Request
            header('Access-Control-Allow-Origin: *');
            header('content-type: application/json');
            die(json_encode( array(
                'codigo' => 400,
                'mensaje' => 'No se pudo validar su identidad, asegurate de enviar los parametros necesarios',
                'descripcion' => $ex->getMessage(),
                'detalles' => $_REQUEST
            )));
        }
    }

    /**
     * Implementacion default.
     * 
     * Permitimos acceso a todo mundo (útil para pruebas)
     * 
     * Nota, otras clases deben sobrecargar ésta función para usar otros metodos de autenticación.
     * 
     * @param array $args Arreglo con la información necesaria para autenticar al usuario.
     * 
     * @return string El token generado para el usuario
     */
    public function auth( $args = NULL )
    {
        $uData = array();
        $uData['login'] = 'Autenticacion Sin Implementar';

        die($this->generaToken( $uData ));
    }

    /**
     * Define "El Secreto"
     * 
     * (Usado para la encriptacion del Token)
     * 
     * Necesita ser publico, para que pueda ser definido por el servidor que usa la clase
     */
    public function SetSecret( $newSecret )
    {
        $this->_Secreto = $newSecret;
    }
    protected $_Secreto = '8C29B73D40DC05B7E5076AD18A338CC6'; // Secreto por default (generado aleatoriamente)
    //private $_Secreto = 'Mi Secreto'; // Para pruebas

    /**
     * Genera un JWT Token usando la información en Options
     * 
     * Opciones:
     *      Expiration - DateTime que indica cuando expira el token.
     *                   Ejemplo: $options['Expiration'] = new \DateTime('10 minutes');
     * 
     */
    protected function generaToken( $options = array() )
    {
        $token = new \Emarref\Jwt\Token();

        // Cuando se genera el Token
        $token->addClaim(new Claim\IssuedAt(new \DateTime('now')));
        // Valido a partir de (ahora mismo)
        $token->addClaim(new Claim\NotBefore(new \DateTime('now')));
        // Donde se expide
        $token->addClaim(new Claim\Issuer($_SERVER["SERVER_NAME"]));
        
		$token->addClaim(new Claim\Subject('eurorest'));

        //
        // Expiración del token
        //
        if( !empty( $options['Expiration']))
        {
            $token->addClaim(new Claim\Expiration($options['Expiration']));
        } else {
            // Usa un valor default de 10 minutos
            $token->addClaim(new Claim\Expiration(new \DateTime('10 minutes')));
        }

        // Agrega el resto de la información en las opciones
        foreach ($options as $key => $value) {
			if( $key == 'Expiration' ) continue; // ya checamos expiration arriba
			$token->addClaim(new Claim\PrivateClaim($key, $value));
        }
        
        // Prepara la encriptacion
        $algorithm = new \Emarref\Jwt\Algorithm\Hs256($this->_Secreto);
		$encryption = \Emarref\Jwt\Encryption\Factory::create($algorithm);

		$jwt = new \Emarref\Jwt\Jwt();
		$serializedToken = $jwt->serialize($token, $encryption);

		return($serializedToken);
    }

    public function authFromJWT( $serializedToken )
    {
        $jwt = new \Emarref\Jwt\Jwt();
        $token = $jwt->deserialize($serializedToken);

        // Prepara la encriptacion
        $algorithm = new \Emarref\Jwt\Algorithm\Hs256($this->_Secreto);
        $encryption = \Emarref\Jwt\Encryption\Factory::create($algorithm);
        
        // Este es el contexto con el que se va a validar el Token
        $context = new \Emarref\Jwt\Verification\Context( $encryption );
        $context->setIssuer($_SERVER["SERVER_NAME"]);
		$context->setSubject('eurorest');
		$options = array();

        // Normalmente aqui usaría un try/catch,
		// pero al final de nuevo lanzaría una excepcion.
		// Mejor voy a dejar que la excepcion se propague.

        $jwt->verify($token, $context);

        // Lista los claims del token
        //$jsonPayload = $token->getPayload()->getClaims()->jsonSerialize();
        //print($jsonPayload);

	    $autoRenewClaim = $token->getPayload()->findClaimByName('Autorenew');
	    if($autoRenewClaim !== null)
	    {
	    	$options["Autorenew"] = $autoRenewClaim->getValue();
        }
        $options["Autorenew"] = true;

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

		$options['login'] = $token->getPayload()->findClaimByName('login');

	    // Autorenew debe ser el ultimo, ya que tengamos todo lo necesario en Options
	    if( isset( $options["Autorenew"] ) && $options["Autorenew"] == true )
	    {
	    	$newToken = $this->generaToken($options);
	    	//header("Access-Control-Expose-Headers","New-JWT-Token");
	    	header("Authorization: {$newToken}");
	    }
    }

    public function testToken()
    {
        die("Token Tested");
    }
}
