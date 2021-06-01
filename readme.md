# Acerca del generador de BREADs

Este paquete genera todos los archivos necesarios para lanzar un BREAD básico en cuestión de segundos.

Funciona para los escenarios en que se quieren generar BREADs persistentes que puedan ser regenerados a través de seeder, sin necesidad de depender de la base de datos.

## Instrucciones

El generador de BREADs añade dos comandos al artisan: `bread:make` y `bread:rows`.

### Comando bread:make

Este es el primer comando que se debe de ejecutar.

Acepta un nombre como parámetro, que será el nombre del modelo:

    php artisan bread:make ModelName

Esto creará el model, controlador y vistas necesarias, además de actualizar las rutas web.php para añadir las nuevas rutas.

### Comando bread:rows

Este comando debe ejecutarse luego de `bread:make`.

Acepta como parámetro el nombre del modelo, al igual que el comando anterior, además de una serie de opciones para estructurar los archivos del BREAD:

- -i, --icon[=ICON] (El icono por defecto para el BREAD [default: "voyager-bread"])
-  -s, --singular[=SINGULAR] (El nombre en singular para el BREAD)
-  -p, --plural[=PLURAL] (El nombre en plural para el BREAD)
-  -o, --order[=ORDER] (La columna utilizada para organizar los registros en la lista del BREAD [default: "name"])
-  -x, --sort[=SORT] (El orden en que se organizarán los registros [default: "asc"])

```
    php artisan bread:rows ModelName -i voyager-x -s "Modelo" -p "Modelos" -o id -x desc
```

Al ejecutar el comando, este guiará al usuario a través de una serie de preguntas y selecciones para ir estructurando los archivos restantes del BREAD, pidiendo información como nombre y tipo de los distintos campos tanto para la migración como para el menú del BREAD.

Al terminar el proceso se generarán varios archivos adicionales, como un seeder con su trait, los requests y la migración.