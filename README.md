Taskboard Semana 18

📌 ¿Qué se implementó en esta semana?

En esta práctica se reestructuró la aplicación Pasarela de Pagos · TaskBoard para mejorar su mantenibilidad y escalabilidad aplicando una Capa de Servicios y principios SOLID:
Responsabilidad Única (SRP): Se cortaron los controladores delegando la lógica de negocio y registro de operaciones al servicio `TransaccionService`.

Principio Abierto/Cerrado (OCP): El cálculo de comisiones se desacopló mediante el contrato `ReglaComision` y el gestor `CalculadoraComision`. Esto permite incorporar nuevos métodos de pago (ej. tarjeta, transferencia) sin modificar el código existente.

Persistencia y Datos de Prueba: Se actualizaron las migraciones (`metodo` y `comision` en la tabla `transacciones`) junto con los seeders (`DatabaseSeeder`) para poblar datos realistas con sus respectivas comisiones calculadas.

Interfaz de Usuario (Blade): Se actualizó la vista de detalle del comercio (`show.blade.php`) para visualizar el método de pago y la comisión aplicada en cada transacción.

Red de Seguridad (Testing): Se validó el comportamiento completo mediante pruebas automatizadas (`RegistrarTransaccionTest`), asegurando que toda la lógica funcione correctamente antes y después de la refactorización.

⚙️ Instalación y Recuperación de Archivos (`.gitignore`)

1. Instalar dependencias
```
composer install
npm install

```
2. Recuperar el archivo de entorno (`.env`)
Copiar la plantilla de ejemplo y generar la clave de cifrado de Laravel:
```
cp .env.example .env
php artisan key:generate

```
3. Crear y poblar la base de datos SQLite
Crear el archivo vacío de SQLite (si no existe) y ejecuta las migraciones junto con los seeders para tener los usuarios (`ana@taskboard.test`, `carlos@taskboard.test`), comercios y transacciones de prueba listos:
```
touch database/database.sqlite
php artisan migrate:fresh --seed

```
4. Ejecutar las pruebas automatizadas
Para comprobar que la capa de servicios y el cálculo OCP funcionan correctamente:
```
php artisan test
