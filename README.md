# EUROGLAS rest (eurorest)
## Servidor REST para EUROGLAS

Modulo base (CORE) para los servidores REST de EUROGLAS. 

Define:
- Manejador de rutas URL mediante AltoRouter
- Una interfaz para los modulos que se van a implementar
- Una interfaz para los modulos que validan el acceso (auth)

### Archivos

    eurorest
    ├───src
    │   ├───auth.php
    │   ├───authInterface.php
    │   ├───restModuleInterface.php
    │   ├───restServer.php
    │   └───home
    │       └─── index.php
    ├───.gitignore
    ├───composer.json
    ├───index.php
    ├───iniciaServidorDePruebas.php
    ├───servidor.ini
    ├───LICENSE
    └───README.md

### Directorio `src`

Contiene los archivo que implementan el proyecto

Interfaces
- authInterface | Validacion de los usuarios
- restModuleInterface | Modulos REST

Clases
- auth | Clase abstracta como base para Clases de Usuarios
- restServer | Implementación del servidor REST

### Directorio `src/home`
Contiene la pagina que se muestra cuando se accesa a la URL base `/` que informa al usuario que se esta accediendo a una API, no a una pagina regular.

### Directorio Raiz

 Contiene los archivos para pruebas del modulo.


| Archivo  | Descripcion   |
|---|---|
| .gitIgnore | blah |
| composer.json| Manejo de requerimientos |
| index.php | Implementacion del servidor de pruebas
| servidor.ini | Configuracion del servidor | 
| iniciaServidorDePruebas.bat | Script para arrancar el servidor usando el servidor interno de PHP |
| LICENSE | Licencia de uso de este paquete |
| README .md | éste archivo |

## Configuración

| Llave  | Explicación   |
|---|---|
| ServerName="" | Nombre del servidor | 
| ModoDebug = 1 | Habilita el modo de desarrollo | 
| SkipUserAuth = 1 | Permite iniciar sin usuarios, sin generar error | 
| [Modulos] | Grupo de Modulos a habilitar (Vacio porque estamos en CORE) |

## URLs

| Metodo | URL | Descripción   |
|---|---|---|
| OPTIONS | _[todas]_ | Devuelve los metodos aceptados por el servidor |
| GET | / | Raiz (imprime contenido de HOME) |
| GET | /ping | Prueba rápida para saber si el servidor esta funcionando |

## URLs para desarrollo

Estas URLs (excepto la primera) estarán disponibles SOLO cuando el servidor se encuentra en modo de desarrollo (`ModoDebug=1`).

| Metodo | URL | Descripción   |
|---|---|---|
| GET | /debug | Consulta si el servidor esta en modo de desarrollo |
| GET | /debug/config | Muestra el contenido del archivo de configuración (ya parseado)
| GET | /debug/serverName | Muestra el nombre del servidor (`ServerName`)
| GET | /debug/modulos | Muestra lista de modulos habilitados |
| GET | /debug/permisos | Lista de permisos definidos por cada modulo |
| GET | /debug/urls | Lista de URLs definidas por cada modulo |
| GET | /debug/authProvider | Nombre del modulo registrado para validar usuarios y entregar el Token |

#
# Uso del servidor REST
Para crear un servidor que use este proyecto para servir URLs REST

- Instala composer en tu ambiente de desarrollo
- Crea un directorio para tu proyecto
- Crea un archivo `composer.json` para definir los requerimientos
- Agrega este proyecto como requerimiento de tu servidor
```json
        {
            "require": {
                "euroglas/eurorest": "^1.0.0"
            }
        }
```
- Ejecuta composer para instalar las dependencias (esto va a generar el archivo `composer.lock` y el directorio `vendor`)
```
php composer.phar install
```
Si quieres hacer uso de la utileria de autocarga de clases de composer, incluye esto en tu script php (`index.php`):
```php
require 'vendor/autoload.php';
```

## Redirigir las solicitudes de URLs a tu aplicación

Necesitamos que todas las solicitudes de ubicaciones no-existentes, sean procesadas por tu aplicacion (para que sean interpretadas como URLs del servidor Rest). 

Si estas usando APACHE (`.htaccess`):
```apache
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
```
Si estas usando Nginx (`nginx.conf`):
```nginx
try_files $uri /index.php;
```

# 
# Implementacion de modulos

Hay varios detalles que debes tener presentes a la hora de hacer tu modulo, y no pierdas incontables horas (y maldiciendo al autor de éste sistema) tratando de entender porque no funcionan las cosas.

## Rutas y nombres

El sistema esta basado en el autoloader de composer (que a su vez sigue las especificaciones de [PSR4](https://www.php-fig.org/psr/psr-4/)), esto significa que:

### Usa src
Los archivos que implementan tu modulo, deberán estar dentro del directorio src de tu proyecto.

### El nombre del archivo importa
Para que la clase que implementa tu modulo, sea accesible al autoloader, el archivo se debe llamar IGUAL que la clase. Y esto es esctricto, respeta minusculas y mayuscular.

La clase `class ejemploConCamel` debe estar en el archivo `ejemploConCamel.php`

## Espacio de nombres

```php
namespace euroglas\pedidos;
```

Supongo que cualquier espacio de nombres es tan valido como otro,
pero para evitar sorpresas, usa el espacio de nombres de EUROGLAS.

### Herencia de tus mayores

Las clases que implementan los submodulos, deben implementar la interfaz restModuleInterface

```php
class ejemplo implements \euroglas\eurorest\restModuleInterface
```

### Accesibilidad de las funciones

Las funciones que implementan la funcionalidad de las URLs (los callback), deben poder ser llamados desde el servidor, por lo que necesitan ser funciones publicas.

```php
public function miEjemplo()
```

#
# Implementacion de modulos de usuario (auth)

Los modulos que proveen los token de acceso, a cambio de algo de informacion, los llamamos Modulos de Acceso de Usuarios.

Siguen todas las caracteristicas de los modulos normales, EXCEPTO que deben heredar de la clase abstracta `auth`.

La clase abstracta `auth`, implementa tanto `restModuleInterface` como `authInterface`. Y proporciona algunas implementaciones default para algunas de las funciones. 

```php
class authEjemplo extends \euroglas\eurorest\auth
```

**Nota** Cuando implementes un modulo *auth*, ten en cuenta que un servidor solo puede implementar un metodo de authenticacion (por ahora). Asegurate que tu modulo esta habilitado en la configuracion, y comprueba que esta listado como validador de usuarios, usando la url `/debug/authProvider`