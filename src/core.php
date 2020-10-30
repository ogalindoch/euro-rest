<?php

namespace euroglas\eurorest;

class core implements restModuleInterface
{
        // Nombre oficial del modulo
        public function name() { return "Core"; }

        // Descripcion del modulo
        public function description() { return "Módulo base"; }
    
        // Regresa un arreglo con las rutas del modulo
        public function rutas()
        {
            $rutas = array();

            return $rutas;
        }
}
