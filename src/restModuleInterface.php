<?php
namespace euroglas\eurorest;

interface restModuleInterface 
{
    // Nombre oficial del modulo
    public function name();

    // Descripcion del modulo
    public function description(); 

    // Regresa un arreglo con los permisos del modulo
    // (Si el modulo no define permisos, debe regresar un arreglo vacío)
    public function permisos();

    // Regresa un arreglo con las rutas del modulo
    public function rutas();
    
}