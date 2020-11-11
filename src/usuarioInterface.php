<?php
namespace euroglas\eurorest;

interface usuarioInterface 
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
     * Define "El Secreto"
     * 
     * (Usado para la encriptacion del Token)
     */
    function setSecret( $newSecret );
}