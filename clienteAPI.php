<?php
///////////////////////////////////////////////////////////////////////////////
// 	 Classe cliente para interactuar con la API de ejemplo JSONPlaceholder   //
// 						    Bernat Pericàs Serra 						 	 //
// 					   bernatpericasserra97@gmail.com 						 //
///////////////////////////////////////////////////////////////////////////////

// Usamos la librería Httpful para interactuar con la API
// Web: http://phphttpclient.com
// Descargar archivo y mover-lo al mismo directorio desde:
// http://phphttpclient.com/downloads/httpful.phar
include('./httpful.phar');

class Cliente {

	/* Constantes */

	//URL de la API
	private const 	BASE = 'https://jsonplaceholder.typicode.com';
	//Posibles nombres de los recursos de la API
	private const 	RECURSOS 		= array('posts','comments','albums',
												'photos','todos', 'users');
	//Claves de los recursos
	private const 	CLAVESPOSTS 	= array('userId','title','body');
	private const 	CLAVESCOMMENTS 	= array('postId','name','email', 'body');
	private const 	CLAVESALBUMS 	= array('userId','title');
	private const 	CLAVESPHOTOS 	= array('albumId','title','url',
												'thumbnailUrl');
	private const 	CLAVESTODOS 	= array('userId','title','completed');
	private const 	GEO 			= array('lat','lng');
	private const 	ADDRESS 		= array('street','suite','city','zipcode',
												'geo');
	private const 	COMPANY 		= array('name','catchPhrase','bs');
	private const 	CLAVESUSERS 	= array('name','username','email',
												'address','phone','website',
												'company');

	/* Métodos */

	/**
 	* Obtener todos los recursos, un recurso o recursos anidados (1 nivel).
 	*
	* @param String 		$resource 	required:	Recurso principal
	* @param String/Integer $id 		optional: 	Id de $resource
	* @param String 		$nested 	optional: 	Recurso anidado
	*/
	function get($resource, $id = null, $nested = null) {
		try{
			// Comprobar que el resource sea correcto
			self::comprobar($resource);
			// Obtener todos los recursos
			$url = self::BASE.'/'.$resource;
			if (!is_null($id)) {
				// Obtener un recurso
				$url = $url.'/'.$id;
				if (!is_null($nested)) {
					// Comprobar el segundo parámetro
					self::comprobar($nested);
					// Obtener los recursosanidados
					$url = $url.'/'.$nested;
				}
			}
			// Realizar la llamada
			$response = \Httpful\Request::get($url)
			->expectsJson()
			->send();
			// Devolver los resultados
			return $response;

		}catch(Exception $e){
			// Imprimir el error
			echo("Error: ".$e->getMessage());
		}
	}

	/**
	* Comprobar si el parmámetro de entrada es un String y, en caso afirmativo,
 	* comprobar que es uno de los valores de la constante RECURSOS.
 	*
	* @param String 		$par 		required: 	Parámetro a comprobar.
	*/
	private function comprobar($par){
		if (!is_string($par)) {
			throw new Exception("El parámetro '".$par."' no es un String.\n");
		}else if (!in_array($par, self::RECURSOS)) {
			//Obtener valores del array
			$recursos = self::valores(self::RECURSOS);
			$mensaje = "El parámetro '".$par."' no es un recurso de la API.\n".
						"Los posibles recursos son: ".$recursos.".";
			throw new Exception($mensaje);
		}
	}

	/**
	* Devuelve un String con los valores de un array listados de manera 
	* humanamente legible.
 	*
	* @param String 		$array 		required: 	Array a listar.
	*/
	private function valores($array){
		$valores="";
		foreach ($array as $key => $value) {
			$valores = $valores.", '".$value."'";
		}
		// Quitar la primera coma {,}
		return substr($valores,2);
	}

	/**
	* Crear un recurso a partir de un nombre de un recurso y un array de
	* valores.
 	*
	* @param String 		$resource 	required: 	Recurso a crear
	* @param String 		$body 		required: 	Valores del body
	*/
	function post($resource, $valores){
		try{
			// Preparar el array introducido por el usuario para la llamada
			$body = self::construirBody($resource, $valores);
			// Realizar la llamada
			// La librería serializa el 'body' automáticamente a JSON
			$response = \Httpful\Request::post(self::BASE.'/'.$resource)
			->body($body)
			->sendsJson()
			->send();
			// Devolver resultados.
			return $response;
		}catch(Exception $e){
			// Imprimir el error
			echo("Error: ".$e->getMessage());
		}
	}

	/**
	* Transforma un array de valores $valores en un array 
	* clave => valor con las constantes CLAVES{recurso}.
 	*
	* @param String 		$resource 	required: 	Recurso a crear
	* @param Array 			$valores 	required: 	Valores para el body
	*/
	private function construirBody($resource, $valores){
		// Comprobar que el resource sea correcto
		self::comprobar($resource);
		// Comprobar errores en el 'body' introducido por el usuario
		self::comprobarBody($valores);
		// Construimos el cuerpo del POST según el tipo de recurso
		$body="";
		switch ($resource) {
			case 'posts':
			$body = self::combinar(self::CLAVESPOSTS, $valores);
			break;

			case 'comments':
			$body = self::combinar(self::CLAVESCOMMENTS, $valores);
			break;

			case 'albums':
			$body = self::combinar(self::CLAVESALBUMS, $valores);
			break;

			case 'photos':
			$body = self::combinar(self::CLAVESPHOTOS, $valores);
			break;

			case 'todos':
			$body = self::combinar(self::CLAVESTODOS, $valores);
			break;

			default: //case 'users':
			//Más complicado por que es un array multidimensional
			$geo = self::combinar(self::GEO, $valores[3][4]);
			
			//Insertamos $geo en $address
			$address = array($valores[3][0],$valores[3][1],$valores[3][2],
								$valores[3][3], $geo);
			$address = self::combinar(self::ADDRESS, $address);

			$company = self::combinar(self::COMPANY, $valores[6]);
			
			// Construir array multidimensional
			$body = array($valores[0],$valores[1],$valores[2],$address,
							$valores[4],$valores[5],$company);
			$body = self::combinar(self::CLAVESUSERS, $body);
			break;
		}
		return $body;
	}
	/**
	* Controla los posibles errores que haya podido cometer el usuario al 
	* introducir parámetros en un array para los métodos POST, PUT o PATCH.
 	* Se realizará recorriendo el array.
 	* 
	* @param Array 			$valores 	required: 	Valores del body
	*/
	private function comprobarBody($valores){
		// Comprobar que el primer parámetro sea correcto
		foreach ($valores as $key => $value) {
			// Comprobar los valores que ha introducido el usuario
			// TODO
			// Se deja sin hacer ya que no se conopcen los requisitos 
			// de cada valor ni es necesario para esta prueba
			if (false) {
				// TODO
				throw new Exception("TODO");
			}
		}
	}

	/**
	* Combina dos arrays de igual longitud para formar un array clave => valor.
 	*
	* @param Array 			$a 			required: 	Array de claves
	* @param Array 			$b 			required: 	Array de valores
	*/
	private function combinar($a, $b){
		$combinado="";
		if (count($a) == count($b)) {
			$combinado = array_combine($a, $b);
		}else{
			throw new Exception("Arrays de distinta longitud.");
		}
		return $combinado;
	}
	/**
	* Actualiza un recurso $resource con identificador $id con los 
	* valores $valores que ha especificado el usuario.
 	*
	* @param String 		$resource 	required: 	Recurso a actualizar
	* @param String/Integer	$id 		required: 	Identificador del recu.
	* @param Array 			$valores 	required: 	Array de valores
	*/
	function put($resource, $id, $valores){
		try{
			//construirBody ya comprueba los errores del usuario
			// Transformar los valores del usuario en el 'body' para la llamada
			$body = self::construirBody($resource, $valores);
			// Realizar la llamada
			$response = \Httpful\Request::put(self::BASE.'/'.$resource.'/'.$id)
			->sendsJson()
			->body($body)
			->send();
			return $response;
		}catch(Exception $e){
			echo("Error: ".$e->getMessage());
		}
	}

	/**
	* Actualiza solo los campos de un recurso especificados mediante un array 
	* clave=>valor $cambios.
 	*
	* @param String 		$resource 	required: 	Recurso a actualizar
	* @param String/Integer	$id 		required: 	Identificador del recu.
	* @param Array 			$cambios 	required: 	Array de cambios
	*/
	function patch($resource, $id, $cambios){
		try{
			// Comprobar errores en el array introducido por el usuario
			self::comprobarBody($cambios);
			// Realizar la llamada
			$response = \Httpful\Request::patch(self::BASE.'/'.$resource.'/'.$id)
			->sendsJson()
			->body($cambios)
			->send();
			return $response;
		}catch(Exception $e){
			echo("Error: ".$e->getMessage());
		}
	}

	/**
	* Borra el recurso especificado en los dos parámetros de entrada.
 	*
	* @param String 		$resource 	required: 	Recurso a borrar
	* @param String/Integer	$id 		required: 	Identificador del recu.
	*/		
	function delete($resource, $id){
		try{
			self::comprobar($resource);
			$response = \Httpful\Request::delete(self::BASE.'/'.$resource.'/'.$id)
			->send();
			return $response;
		}catch(Exception $e){
			echo("Error: ".$e->getMessage());
		}
	}
}

////////////////////////////////// EJEMPLOS ///////////////////////////////////

// //Indicamos al navegador que recibirá JSON para una mejor visualización
header('Content-Type: application/json; charset=UTF-8');

$c = new Cliente();
////								- GET -								   ////
//https://jsonplaceholder.typicode.com/posts
$r = $c->get('posts');
echo $r."\n";

//https://jsonplaceholder.typicode.com/posts/45
$r = $c->get('posts',45);
echo $r."\n";

//https://jsonplaceholder.typicode.com/posts/45/comments
$r = $c->get('posts',45,"comments");
echo $r."\n";

////							    - POST -							   ////
$valores = array(1,'Hola', 'Mundo');
$r = $c->post('posts', $valores);
echo $r."\n";

$valores = array(2,'nombre de usuario','correo@correo.com','cuerpo');
$r = $c->post('comments', $valores);
echo $r."\n";

$valores = array(3,'Titulo del album');
$r = $c->post('albums', $valores);
echo $r."\n";

$valores = array(4,'Titulo de la foto','url.com','urlminiatura.com');
$r = $c->post('photos', $valores);
echo $r."\n";

$valores = array(5,'tarea','false');
$r = $c->post('todos', $valores);
echo $r."\n";

$valores = array('Bernat Pericas',
	'berenar',
	'bernatpericasserra97@gmail.com',
	array('Manresa','99','Palma','07015',array('39.569599','2.650160')),
	'+34648963195',
	'bernatpericas.com',
	array('PecerasYmas','Eslogan por definir','Custom fish tanks')
);
$r = $c->post('users', $valores);
echo $r."\n";

////								-  PUT -							   ////
$valores = array(1,'Hola', 'Mundo');
$r = $c->put('posts', '1', $valores);
echo $r."\n";

////								- PATCH-							   ////
$cambios = array('body'=>'Palabras palabras...');
$r = $c->patch('posts', '1', $cambios);
echo $r."\n";

////								- DELETE -							   ////
$r = $c->delete('posts', 9);
echo $r."\n";

?>
