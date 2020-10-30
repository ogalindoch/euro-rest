<?php

interface restModuleInterface 
{
    // Nombre oficial del modulo
    public function name();

    // Descripcion del modulo
    public function description(); 

    // Regresa un arreglo con las rutas del modulo
    public function rutas();
    
}