<?php
namespace euroglas\eurorest;

interface authInterface 
{
    /**
     * Autenticación del usuario 
     * 
     * @param array $args Arreglo con los parametros necesarios para autenticar al usuario
     * 
     * @return string El token generado para el usuario
     * @access public
     */
    function auth( $args = NULL );

    /**
     * Valida que el token recibido sea valido
     * 
     * @param string El token recibido
     * 
     * @return void Si la funcion se ejecuta exitosamente, el token era valido, de forma contraria, termina la solicitud.
     */
    function authFromJWT( $serializedToken );
    
    /**
     * Define "El Secreto"
     * 
     * (Usado para la encriptacion del Token)
     */
    function setSecret( $newSecret );
}