# Respuestas Teóricas

## 1. Explica la diferencia entre hasMany() y belongsToMany() en Eloquent. Proporciona ejemplos.

**R=**

`hasMany()` — relación **uno a muchos**: un registro del modelo padre tiene muchos registros hijos en otra tabla, ligados por una clave foránea que apunta al padre. Ejemplo: un usuario puede tener muchos préstamos, pero cada préstamo pertenece a un único usuario.

```php
class Usuario extends Model {
    public function prestamos() {
        return $this->hasMany(Prestamo::class); // prestamos.usuario_id -> usuarios.id
    }
}
// $usuario->prestamos  (colección de Prestamo)
```

`belongsToMany()` — relación **muchos a muchos**: la relación pasa por una **tabla pivote** que tiene las dos claves foráneas. Ejemplo: autores y libros comparten la tabla `autor_libro`:

```php
class Libro extends Model {
    public function autores() {
        return $this->belongsToMany(Autor::class, 'autor_libro', 'libro_id', 'autor_id')
                    ->withPivot('orden_autor')
                    ->orderByPivot('orden_autor');
    }
}
```

**Diferencia:** en `hasMany` la clave foránea está en la tabla del hijo; en `belongsToMany` está en una tabla pivote. Si el hijo solo tiene un padre uso `hasMany` (un préstamo, un usuario); si puede tener varios, `belongsToMany` (un libro, varios autores).

## 2. ¿Cuál es la diferencia entre all(), get() y find() en Eloquent? ¿Cuándo usarías cada uno?

**R=**

`all()` trae todos los registros de la tabla, sin condiciones, y devuelve una colección. Se llama directamente desde el modelo y ejecuta la consulta en el momento. No permite filtrar ni ordenar antes de traer los datos. Lo usaría en tablas pequeñas o catálogos, como una lista de países, categorías o roles, donde realmente necesito todo el contenido.

`get()` es el que cierra una consulta armada con el constructor de consultas. Primero se encadenan las condiciones (`where`, `orderBy`, `limit`, relaciones con `with`) y al final `get()` ejecuta todo y devuelve una colección con los resultados. Lo usaría siempre que necesite filtrar, ordenar o limitar, por ejemplo para traer solo los préstamos activos de un usuario. Es el más usado en la práctica:

```php
Prestamo::where('estado', 'activo')->orderBy('fecha', 'desc')->get();
```

`find()` busca un único registro por su clave primaria. Devuelve el modelo, o `null` si no existe; si se le pasa un arreglo de ids devuelve una colección. Existe también `findOrFail()`, que en lugar de devolver `null` lanza una excepción y Laravel responde con un error 404. Lo usaría cuando ya tengo el id, por ejemplo al mostrar el detalle de un registro.

## 3. Explica el patrón Repository en Laravel. ¿Cuándo lo implementarías?

**R=**

El patrón Repository es una capa entre la lógica de la aplicación y los datos. En vez de que el controlador consulte directo los modelos Eloquent, le pide los datos a un repositorio, así no le importa de dónde vienen (base de datos, API externa, caché).

Se arma con una interfaz que define las operaciones (buscar un préstamo, listar los activos, crear uno), una clase que la implementa con Eloquent, y el registro en el contenedor de servicios para que el controlador la reciba por inyección de dependencias.

Sirve para cambiar la fuente de datos sin tocar el resto, centralizar consultas complejas que se repiten y hacer pruebas con un repositorio falso, sin base de datos.

Lo implementaría en proyectos medianos o grandes, o cuando la fuente de datos puede cambiar. En un CRUD simple no, porque Eloquent ya cumple ese papel y el repositorio solo agregaría código de más; ahí bastan los query scopes o una clase de servicio. No lo usaría por defecto, sino cuando haga falta de verdad.

## 4. ¿Qué es el problema N+1 en Eloquent y cómo lo solucionarías?

**R=**

El problema N+1 ocurre cuando se hace una consulta para traer una lista de registros y después una consulta más por cada uno para cargar una relación. Pasa por el *lazy loading*: Eloquent solo carga la relación cuando se accede a ella. Por ejemplo, si listo 100 préstamos y muestro el usuario de cada uno, terminan siendo 101 consultas en lugar de 2, y con tablas grandes eso vuelve lenta la aplicación.

Lo soluciono con *eager loading* usando `with()`, que carga las relaciones desde el inicio en una sola consulta extra:

```php
Prestamo::with('usuario')->get();
```

Si ya tengo la colección uso `load()`, y si solo necesito el número de relacionados uso `withCount()`.

Para detectarlo uso Laravel Debugbar o Telescope, que muestran cuántas consultas hace cada petición, o activo `Model::preventLazyLoading()` en desarrollo para que lance una excepción cuando ocurra.

## 5. Describe las diferencias entre autenticación y autorización en APIs.

**R=**

La autenticación verifica **quién es** el usuario, por ejemplo, cuando inicia sesión y recibe un token. La autorización define **qué puede hacer** ese usuario según sus permisos o rol. En Laravel se puede manejar con Gates y Policies. Por ejemplo, un empleado puede iniciar sesión, pero si intenta eliminar un usuario y no tiene permiso, el sistema le devuelve un error `403`. La autenticación falla normalmente con un `401`.
