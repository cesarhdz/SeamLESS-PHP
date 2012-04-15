<?php
/*
 * Archivo que permite trabajar con LESS
 * Incluye clase Less_compiler que realiza todo el trabajo de preparar los archivos, realizar la cache y mostrar el css
 * http://leafo.net/lessphp/docs/
 */

//Incluimos primero less
include_once  '_phpless/lessc.inc.php';

//Definimos la variable ENVIRONMENT, que nos permite saber si confiamos en el caché, además de mostrar errores
//Puede ser production o development
define('ENVIRONMENT', 'development');

//@TODO Hacer un extend para compartir los métodos
//@TODO Permitir variables get, para incorporarlas a less
//@TODO Una función para el log o la variable get show_errors o log
//@TODO Agregar una opción para minificar
class Less_compiler {
    
    var $default_file = 'index.less';
    var $file;
    
    var $cache_dir = '_phpless/cache/';
    var $cache_lifetime; //Todavía no funciona
    
    private $_css;
    private $_cache;
    
    function __construct()
    {
        //Buscamos el archivo que se está cargando, para mostrar el estilo adecuado
        $file = $this->guess_file();
        $this->file = ($file) ? $file . '.less' : $this->default_file;
    }
    
    /*
    * Función que permite conocer cual es el archivo que se desea cargar
    * Requiere de .htaccess y rewrite rule on
    * @return Mixed FALSE, si no existe una forma de detectar el archivo que se pide
    *                  Array con el siguiente orden: [0] nombre del archivo, [1] extensión   
    */
    function guess_file()
    {
        //Si existe REDIRECT URL
        if(isset($_SERVER['REDIRECT_URL']) AND $_SERVER['REDIRECT_UR2L'])
        {
            $url = $_SERVER['REDIRECT_URL'];
        }
        else if(isset($_SERVER['REDIRECT_URL']) AND $_SERVER['REQUEST_URI'])
        {
            $url = $_SERVER['REQUEST_URI'];
        }

        //Si no tenemos url para trabajar, regresamos falso
        if(! isset($url) OR !$url) return FALSE;

        //Primero eliminamos el GET
        $url = preg_replace('#\?.*#', '', $url);

        //Convertimos en array para trabajar con el último
        //No tenemeos un mayor control porque sólo se trabajarán con los archivos que esten en el directorio base
        //@TODO Permitir archivos dentro de directorios
        $url = explode('/', $url);

        //Detectamos el archivo y comprobamos si se trata de un archivo CSS
        $file = end($url);
        $file = explode('.', $file);

        //Devolvemos el nombre del archivo o false si no existe
        return (strtolower($file[1]) == 'css') ? $file[0] : FALSE;
    }
    
    /*
     * Función que pérmite compilar automáticamente
     */
    function show_css() {
        
        //Si el archivo no existe no tiene caso regresar nada
        if(! file_exists($this->file)) return '';
        
        //Si no existe la clase lessc, paque le seguimos
        if(!class_exists('lessc')) return 'ccece';
        
        //Si ya estamos en producción utilizamos el cache
        if(ENVIRONMENT == 'production')
        {
            $this->_get_cache();
        }
        
        //Pero si estamos dearrollando o no hay caché hay que generarlo
        if(ENVIRONMENT == 'development' OR ! $this->_cache)
        {
            //Generamos el CSS de manera dinámica
            //El true es para forzar que se genere uno nuevo
            $this->_cache = lessc::cexecute($this->file, true);
            
            
            //Escribimos el cache
            $this->_write_cache();
        }
            
        //Sólo necesitamos el indice compiled para mostrarlo
        $this->_css =& $this->_cache['compiled'];
        
        return $this->_print();
    }
    
    /*
     * Muestra el archivo css con la configuración adecuada
     */
    function _print()
    {
        if($this->_css)
        {
            //Agregamos el header adecuado
            header ('Content-type: text/css; charset=utf-8');
            
            //@TODO Buscar headers para mejorar el cache
            echo $this->_css;
        }
    }
    
    
    /*
     * Buscar cache
     */
    function _get_cache()
    {
        // Busca el cache, según el directorio
        $file = $this->cache_dir . $this->file . '.cache';
        $this->_cache (file_exists($file))
            ? unserialize(file_get_contents($file))
            : false;
    }
    
    /*
     * Escribir cache
     */
    function _write_cache()
    {
       $filename = $this->cache_dir . $this->file . '.cache';
       file_put_contents($filename, serialize($this->_cache));
    }
}

//Generamos el nuevo objeto y lo mostramos
$OBJ = new Less_compiler();
$OBJ->show_css();