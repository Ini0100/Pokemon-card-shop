# TCG Market

Tienda online de cartas coleccionables (sobres, cartas sueltas y accesorios) desarrollada en **PHP 8 y MySQL sin frameworks**. Empezó como práctica universitaria y la he reescrito desde cero con una arquitectura en capas, buenas prácticas de seguridad y un código mantenible.

## Funcionalidades

- **228 cartas reales** de Edición Básica, Jungla y Fósil (1999), importadas desde la API abierta [TCGdex](https://tcgdex.dev) con su rareza, tipo y precio de mercado de Cardmarket.
- **Catálogo con filtros facetados y paginación**: búsqueda por texto, categoría, tipo de carta, rareza, tipo de energía, expansión, rango de precio y ordenación.
- **Carrito con reserva de stock**: al añadir un producto se reserva durante un tiempo configurable; si el carrito se abandona, las unidades vuelven al stock automáticamente.
- **Checkout y pedidos**: el carrito se convierte en un pedido con precios congelados en el momento de la compra e historial consultable por el cliente.
- **Álbum de coleccionista**: los sobres comprados se guardan en el álbum y se abren como un sobre real: 1 carta rara (o holo), 1 poco común y 1 común de su expansión.
- **Registro con activación por email** (SMTP vía PHPMailer).
- **Panel de administración**: gestión de usuarios y productos (con subida de imágenes) y revisión/vaciado de carritos.
- **API REST pública de solo lectura** sobre el catálogo.

## Stack y arquitectura

PHP 8.1+ · MySQL / MariaDB · HTML + CSS sin dependencias · Composer (opcional, solo para PHPMailer)

```
├── public/              Único directorio expuesto por el servidor web
│   ├── index.php        Front controller: todas las peticiones entran por aquí
│   └── assets/
│       ├── css/         Estilos modulares con cascade layers (ver abajo)
│       ├── js/          Mejoras progresivas mínimas (sin dependencias)
│       └── img/         Imágenes de producto
├── src/
│   ├── Core/            Mini-framework propio: router, contenedor DI, vistas, sesión, CSRF…
│   ├── Controllers/     Coordinan petición → servicio → vista (sin SQL ni HTML)
│   ├── Repositories/    Todo el acceso a datos (consultas preparadas con PDO)
│   └── Services/        Lógica de negocio: carrito, checkout, álbum, auth, emails, uploads
│       └── Tcgdex/      Cliente de la API de TCGdex e importador de cartas
├── views/               Plantillas PHP (layout + parciales)
├── config/app.php       Configuración leída desde variables de entorno
├── bin/                 Scripts de consola: instalación, importación y exportación de cartas
├── database/            schema.sql, seed.sql y cards.sql (instantánea del catálogo)
├── bootstrap.php        Autoload PSR-4, configuración y contenedor
└── routes.php           Tabla de rutas
```

Decisiones destacables:

- **Inyección de dependencias** con autowiring por reflexión: los controladores declaran lo que necesitan en el constructor.
- **Transacciones con bloqueo de fila** (`SELECT … FOR UPDATE`) en todas las operaciones que tocan stock, para evitar sobreventa con peticiones concurrentes.
- **Filtros facetados**: valores de la misma propiedad se combinan con OR y propiedades distintas con AND, igual que en cualquier tienda real.

### CSS

Sin preprocesadores ni frameworks. `app.css` es el punto de entrada: declara las **cascade layers** e importa cada módulo en su capa, de modo que la prioridad la decide la capa y no la especificidad de los selectores.

```
reset < base < layout < components < pages < utilities

css/
├── base/        tokens (colores, tipografía, espaciado, modo oscuro) · reset · typography
├── layout/      container · header · footer
├── components/  button · form · search-field · card · alert · tag · table · summary · empty-state · pagination
├── pages/       home · catalog · product · cart · auth · album · admin · error
└── utilities.css
```

Todos los valores visuales salen de los tokens de `base/tokens.css`, y el modo oscuro se activa automáticamente según la preferencia del sistema.

## Seguridad

- Consultas preparadas en toda la aplicación (sin SQL dinámico con datos del usuario).
- Escapado de toda la salida HTML y token **CSRF** validado en cada petición POST.
- Contraseñas con `password_hash`, regeneración del ID de sesión al iniciar sesión y cookies `HttpOnly` + `SameSite`.
- Tokens de activación de 256 bits almacenados como hash SHA-256.
- Subida de imágenes validada por tipo MIME real y tamaño, con nombre aleatorio y ejecución de PHP bloqueada en `uploads/`.
- Credenciales fuera del código (`.env`) y solo `public/` expuesto al servidor web.

## Instalación

### Requisitos

PHP 8.1+ con `pdo_mysql`, `mbstring` y `fileinfo`, y MySQL 8 o MariaDB 10.4+ (XAMPP incluye todo).

### Instalación rápida

Con MySQL en marcha:

```bash
cp .env.example .env
php bin/setup.php
php -S localhost:8000 -t public public/index.php
```

Abre <http://localhost:8000>. `bin/setup.php` crea la base de datos (según `.env`) y carga el esquema, los datos de demostración y el catálogo de 228 cartas en unos segundos, **sin necesidad de conexión a internet**.

- Si la base de datos ya tiene tablas, el script se detiene para no borrar nada; usa `php bin/setup.php --fresh` para reinstalar desde cero.
- En Windows con XAMPP, si `php` no está en el PATH, usa `C:\xampp\php\php.exe` en su lugar.

### Otras formas de instalar

- **phpMyAdmin**: crea la base de datos `tcg_market` (cotejamiento `utf8mb4_unicode_ci`) e importa, en este orden, `database/schema.sql`, `database/seed.sql` y `database/cards.sql`.
- **XAMPP / Apache**: copia el proyecto a `htdocs/tcg-market` y abre <http://localhost/tcg-market/>. El `.htaccess` de la raíz redirige a `public/`.
- **Emails reales (opcional)**: `composer install` y rellena las variables `MAIL_*` de `.env`.

### Catálogo de cartas

Las cartas vienen de la API abierta [TCGdex](https://tcgdex.dev) (gratuita y sin clave). `database/cards.sql` es una instantánea del catálogo que permite instalar sin conexión; para actualizarla:

```bash
php bin/import-cards.php        # Descarga datos y precios actuales de TCGdex (2-3 min)
php bin/export-cards.php        # Regenera database/cards.sql
```

`import-cards.php` importa por defecto Edición Básica, Jungla y Fósil (`base1 base2 base3`) y acepta otros IDs de expansión como argumentos. Es idempotente: al repetirlo actualiza datos y precios sin duplicar cartas ni modificar el stock. `php bin/setup.php --import` instala y refresca los precios en un solo paso.

### Cuentas de demostración

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@tcgmarket.test` | `admin1234` |
| Cliente | `demo@tcgmarket.test` | `demo1234` |

### Emails en local

Si `MAIL_HOST` está vacío, los emails no se envían: el enlace de activación se escribe en el log de PHP y, con `APP_ENV=local`, también se muestra en pantalla tras registrarse. Para enviarlos de verdad, rellena las variables `MAIL_*` (por ejemplo con una contraseña de aplicación de Gmail) y ejecuta `composer install`.

## API

Todas las respuestas son JSON con la forma `{"data": …}`. El listado de productos está paginado (24 por página) e incluye `meta` con `page`, `per_page`, `total` y `last_page`.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/categories` | Lista de categorías |
| GET | `/api/categories/{id}/filters` | Propiedades y valores filtrables de una categoría |
| GET | `/api/products` | Búsqueda: `category`, `q`, `values[]`, `min_price`, `max_price`, `sort` (`name_asc`, `name_desc`, `price_asc`, `price_desc`), `page` |
| GET | `/api/products/{id}` | Detalle de un producto con sus atributos |

Ejemplo: cartas holográficas de tipo Fuego, de la más cara a la más barata (los IDs de los valores se obtienen de `/api/categories/{id}/filters`).

```
GET /api/products?category=2&values[]=7&values[]=9&sort=price_desc
```

## Créditos y licencias

- **Código**: MIT.
- **Datos de cartas**: [TCGdex](https://github.com/tcgdex/cards-database) (licencia MIT). Los precios proceden de Cardmarket a través de TCGdex.
- **Imágenes de cartas**: se sirven directamente desde el CDN de TCGdex y **no se incluyen en este repositorio**.
- **Ilustraciones de sobres y accesorios**: diseños SVG originales de este proyecto.

Pokémon y todos sus nombres, personajes e imágenes son marcas registradas y propiedad de Nintendo, Creatures Inc., GAME FREAK inc. y The Pokémon Company. Este es un proyecto de portfolio sin ánimo de lucro, no está afiliado, patrocinado ni respaldado por ninguna de ellas, y no vende productos reales.
