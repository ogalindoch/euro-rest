<?php
namespace euroglas\eurorest;

interface restModuleInterface 
{
    /**
     * Nombre oficial del modulo
     */
    public function name();

    /**
     * Descripcion del modulo
     */
    public function description(); 

    /**
     * Define los permisos del modulo
     * 
     * Regresa un arreglo con los permisos del modulo
     * (Si el modulo no define permisos, debe regresar un arreglo vacío)
     * 
     * @return array arreglo con los permisos del modulo (o arreglo vacío)
     */
    public function permisos();

    /**
     * Define las rutas del modulo
     * 
     * @return array arreglo con las rutas del modulo
     */
    public function rutas();
    

    //
    // Las siguientes dos funciones se usan en conjunto:
    //
    // 1 - El servidor obtiene una lista de secciones requeridas
    //     por el módulo, usando requiereConfig().
    // 2 - El servidor alimenta cada una de las secciones requeridas
    //     por el módulo, usando cargaConfig().

    /**
     * Define que secciones de configuracion requiere
     * 
     * @return array Lista de secciones requeridas
     */
    public function requiereConfig();

    /**
     * Carga UNA seccion de configuración
     * 
     * Esta función será llamada por cada seccion que indique "requiereConfig()"
     * 
     * @param string $sectionName Nombre de la sección de configuración
     * @param array $config Arreglo con la configuracion que corresponde a la seccion indicada
     * 
     */
    public function cargaConfig($sectionName, $config);
}