# Implementación de permisos por rol

## 1. Etapas del trabajo

1. **ETAPA 1 — Diagnóstico del comportamiento actual**: inventario de roles, comprobaciones rígidas, módulos, páginas, acciones, alcance de datos e inconsistencias. No cambia autorización.
2. **ETAPA 2 — Diseño y registro técnico**: confirmar el catálogo, decidir la equivalencia de roles y registrar permisos/relaciones en Spatie.
3. **ETAPA 3 — Administración de permisos**: incorporar la interfaz de consulta/asignación de permisos dentro de `RoleResource`, manteniendo las mismas decisiones efectivas y sin mover `FlotaScope`.
4. **ETAPA 4 — Navegación y acciones Filament**: alinear sidebar, páginas, recursos y acciones con permisos; revisar acciones `visible()` sin convertirlas en alcance de datos.
5. **ETAPA 5 — Pruebas y estabilización**: pruebas por rol, regresión de acciones, revisión de acceso directo y validación de que el alcance de registros permanece intacto.

## 2. Etapa actual

**ETAPA 3 — Administración de permisos en RoleResource completada.**

Se revisaron con búsquedas `rg` las apariciones de `hasRole()`, `hasAnyRole()`, `canAccess()`, `canViewAny()`, `canCreate()`, `canEdit()`, `canDelete()`, `visible()` y `FlotaScope`, además de los recursos, páginas, sidebar, modelo `User`, `RoleSeeder`, proveedores y pruebas directamente relacionadas.

La ETAPA 1 fue corregida con la decisión funcional aprobada para `chofer`, la ETAPA 2 creó el catálogo central y la ETAPA 3 incorporó la administración de permisos dentro de `RoleResource`. Sólo se modificó la autorización de la administración de roles; no se migraron los demás recursos ni páginas. `FlotaScope` continúa siendo el mecanismo que determina qué vehículos puede consultar cada usuario.

### Roles detectados

| Rol | Situación actual |
|---|---|
| `admin` | Rol privilegiado; ve/gestiona la mayoría de módulos y es el único que puede eliminar en los recursos con controles explícitos. |
| `activos` | Rol operativo de configuración y activos; puede consultar/crear/editar buena parte de catálogos y configuración. |
| `administracion` | Se conserva en `database/seeders/RoleSeeder.php` sin permisos nuevos; la consulta de usuarios que lo tienen queda pendiente por indisponibilidad de la base local. |
| `fondeo` | Controla fondeo, tarjetas y funciones financieras; ahora también se crea en `RoleSeeder` y recibe 17 permisos iniciales. |
| `responsable` | Acceso operativo limitado y, en varios casos, edición únicamente de registros propios/asignados. |
| `chofer` | Sólo “Mis vehículos” y sus cargas de combustible; el alcance de vehículos y cargas depende de `FlotaScope` y filtros actuales. |

## 3. Archivos modificados

- `PERMISOS_IMPLEMENTACION.md` — actualizado con la corrección funcional, catálogo definitivo, matriz aplicada, pruebas y pendientes.
- `config/permissions.php` — catálogo central y asignaciones declarativas por rol.
- `database/seeders/PermissionSeeder.php` — creación idempotente de permisos, roles y relaciones rol-permiso.
- `database/seeders/RoleSeeder.php` — registro del rol `fondeo`.
- `database/seeders/DatabaseSeeder.php` — inclusión de `PermissionSeeder`.
- `tests/Feature/PermissionSeederTest.php` — pruebas focalizadas de catálogo, idempotencia y asignaciones.
- `app/Filament/Resources/RoleResource.php` — interfaz agrupada, controles de acceso y sincronización de permisos.
- `app/Filament/Resources/RoleResource/Pages/CreateRole.php` — sincronización de permisos al crear roles.
- `app/Filament/Resources/RoleResource/Pages/EditRole.php` — hidratación, sincronización y protecciones del rol `admin`.
- `tests/Feature/RoleResourceTest.php` — pruebas de acceso, edición, protección y relación de permisos.

No se modificaron recursos Filament distintos de `RoleResource`, navegación de otros módulos, políticas, consultas, filtros ni `FlotaScope`.

## 4. Decisiones tomadas

- El diagnóstico separa **autorización de acciones** de **alcance de registros**. En ETAPA 2/3 los permisos deberán controlar acceso a módulos, consulta, creación, edición y eliminación; `FlotaScope` seguirá controlando los vehículos consultables.
- Se conserva como línea base el comportamiento actual de las listas rígidas de roles. `administracion` se conserva sin permisos nuevos y `fondeo` se registra con los permisos financieros aprobados.
- La decisión funcional aprobada más reciente reemplaza la conclusión anterior de ETAPA 1 sobre `chofer`: no recibe permisos de reportes, documentos, vehículos generales, cargas generales, catálogos, usuarios, roles ni configuración. Sólo recibe `pagina.mis-vehiculos.view`, `carga-combustible.view` y `carga-combustible.create`; el alcance de datos no se modifica.
- La administración de roles usa `rol.view`, `rol.create`, `rol.update` y `rol.delete`; no se agregó un permiso fuera del catálogo aprobado.
- La pantalla usa `config/permissions.php` como única fuente de opciones, agrupa casillas en módulos, páginas, recursos y operaciones, genera etiquetas comprensibles y permite búsqueda/selección masiva por grupo.
- La relación de Spatie se actualiza mediante `syncPermissions`; no se asignan permisos directos a usuarios.
- No se propuso instalar Filament Shield ni ningún paquete adicional. El proyecto ya requiere `spatie/laravel-permission` 6.x y `User` usa `HasRoles`.
- La matriz siguiente representa controles explícitos encontrados en el código. `V`, `C`, `E` y `D` significan `canViewAny`, `canCreate`, `canEdit` y `canDelete`; `—` significa que ese método no tiene una comprobación rígida explícita en el recurso, no que el acceso efectivo quede automáticamente resuelto.

### Matriz actual de acciones explícitamente protegidas en recursos

| Recurso / módulo | `admin` | `activos` | `administracion` | `fondeo` | `responsable` | `chofer` |
|---|---|---|---|---|---|---|
| `CargaCombustibleResource` | V C E D | V C | — | — | V C E* | V C |
| `SolicitudCargaCombustibleResource` | V | V | — | — | V | — |
| `TarjetaCombustibleResource` | acceso | — | — | acceso | — | — |
| `TarjetaSaldoMovimientoResource` | V | V | — | V | — | — |
| `FondeoResource` | V C E D | — | — | V C E | — | — |
| `VehiculoFondeoConfigResource` | V C E D | V C E | — | V C E | — | — |
| `VehiculoResource` | V C E D | V C E | — | V | — | — |
| `VehiculoDocumentoResource` | V C E D | V C E | — | — | V | — |
| `AlertaDocumentoResource` | V E D | V E | — | — | V E* | — |
| `AlertaRendimientoResource` | V E D | V | — | — | V E* | — |
| `CentroCostoResource` | V C E D | V C E | — | V | — | — |
| `CuentaConcentradoraResource` | V C E D | V C E | — | V | — | — |
| `DepartamentoResource` | V | V | — | — | — | — |
| `LocalidadResource` | V C E D | V C E | — | — | — | — |
| `MarcaVehiculoResource` | V C E D | V C E | — | — | — | — |
| `TipoDocumentoResource` | V C E D | V C E | — | — | — | — |
| `TipoVehiculoResource` | V C E D | V C E | — | — | — | — |
| `VehiculoEstatusResource` | V C E D | V C E | — | — | — | — |
| `VehiculoLocalidadResource` | V C E D | V C E | — | — | — | — |
| `VehiculoDepartamentoResource` | V | V | — | — | — | — |
| `VehiculoCuentaAnaliticaResource` | V | V | — | — | — | — |
| `VehiculoTarjetaResource` | V C E D | V C E | — | — | — | — |
| `VehiculoResponsableResource` | V C E D | V C E | — | — | — | — |
| `VehiculoChoferResource` | V | V | — | — | — | — |
| `UserResource` | V C E D | V C E | — | — | — | — |
| `RoleResource` | V | — | — | — | — | — |

\* `canEdit()` tiene además una condición por registro: `responsable` sólo edita su propia asignación/alerta. En `CargaCombustibleResource`, `admin` edita todo y `responsable` sólo cargas del vehículo que tiene asignado.

#### Recursos con autorización explícita incompleta

- `AseguradoraResource`, `PolizaSeguroResource` y `TipoPagoResource` no sobrescriben `canViewAny`, `canCreate`, `canEdit` ni `canDelete`, aunque exponen formularios, edición, eliminación y eliminación masiva. El catálogo ya registra la decisión aprobada para `admin` y `activos`; la aplicación efectiva de esos permisos queda para ETAPA 3.
- `SolicitudCargaCombustibleResource` fuerza `canCreate()` y `canDelete()` a `false`, pero no define `canEdit()` aunque tiene páginas de edición. Esto requiere validar el comportamiento real antes de migrarlo.
- `TarjetaCombustibleResource` usa `canAccess()` en lugar del conjunto usual de acciones de recurso.
- `DepartamentoResource`, `VehiculoDepartamentoResource`, `VehiculoCuentaAnaliticaResource` y `VehiculoChoferResource` sólo tienen `canViewAny()` explícito; sus acciones de formulario no están expresamente restringidas en el recurso.

### Matriz actual de páginas

| Página | Roles con `canAccess()` |
|---|---|
| `FondeoDashboard` | `admin`, `fondeo` |
| `FondeoFinancieroDashboard` | `admin`, `fondeo`, `activos` |
| `MisVehiculos` | `admin`, `responsable`, `chofer` |
| `RegistrarCargaExtemporanea` | `admin`, `responsable` |
| `ReporteCombustible` | `admin`, `responsable` |
| `ReporteCombustibleCopia` | `admin`, `responsable`, `activos` |
| `ReporteDocumentos` | `admin`, `activos`, `responsable`, `chofer` en la lógica actual; el permiso inicial de `chofer` queda revocado por la decisión funcional aprobada y no se cambiará `canAccess()` hasta ETAPA 3. |

Además, el sidebar de `app/Livewire/Admin/Sidebar.php` sólo agrega el módulo **Configuración** para `admin` o `activos`. Los elementos de navegación se vuelven a filtrar mediante `User::can('viewAny', ...)` o `Resource::canAccess()`, y las páginas mediante `User::can('viewAny', ...)` o `Page::canAccess()`.

### Acciones `visible()` y controles adicionales

- En carga de combustible, la acción de registrar carga extemporánea se muestra sólo a `admin` y `responsable`; otras acciones dependen de que exista registro y/o de que sea extemporáneo.
- En formularios de documentos y tipos de documento, algunos campos `visible()` dependen del tipo de documento o valores del formulario, no de roles.
- En `FondeoFinancieroDashboard`, las acciones de fondear, retirar y transferir dependen de métodos de negocio del dashboard y del registro de tarjeta; no deben sustituirse automáticamente por un permiso global.
- `RegistrarCargaExtemporanea` ejecuta `abort_unless(static::canAccess(), 403)` al montar, por lo que tiene protección directa además de navegación.
- Los observers notifican a usuarios con roles `admin`/`activos` y a responsables; estas consultas de destinatarios no son permisos de interfaz y deberán revisarse separadamente si el catálogo de permisos cambia.

### `FlotaScope` y separación de responsabilidades

`app/Support/FlotaScope.php` actualmente hace lo siguiente:

- `admin`, `activos` y `fondeo`: consultan vehículos activos sin restricción por asignación.
- `chofer` y `responsable`: consultan la unión de vehículos asignados como chofer activo o responsable activo.
- Otros roles: reciben una consulta vacía.
- Usuario no autenticado: recibe una consulta vacía.

Los servicios de reportes y varios recursos aplican la misma idea mediante `FlotaScope::idsVehiculosUsuario()`. Este comportamiento queda fuera de la ETAPA 1 de migración de permisos y no debe alterarse al convertir acciones a permisos.

### Inconsistencia `administracion` vs. `fondeo` y resolución en ETAPA 2

La inconsistencia es material:

1. `administracion` se conserva en `RoleSeeder` y permanece con cero permisos; no se eliminó, renombró ni migró ningún usuario.
2. `fondeo` aparece en `FlotaScope`, dashboards, fondeos, tarjetas, configuraciones de fondeo y catálogos financieros, y ahora también está registrado en `RoleSeeder`.
3. No se fusionaron ambos roles. `fondeo` conserva su alcance global de `FlotaScope`; `administracion` no recibe permisos nuevos.

### Catálogo técnico definitivo

El catálogo aprobado quedó centralizado en `config/permissions.php`, con nombres estables en minúsculas y con punto como separador. El seeder no contiene una segunda lista de permisos: genera los cuatro permisos CRUD por cada recurso declarado en el catálogo y sincroniza las relaciones por rol. No se creó ningún permiso por registro.

**Permisos de módulo/página**

- `modulo.combustible.view`
- `modulo.documentacion.view`
- `modulo.configuracion.view`
- `pagina.mis-vehiculos.view`
- `pagina.fondeo-operativo.view`
- `pagina.fondeo-financiero.view`
- `pagina.carga-extemporanea.view`
- `pagina.reporte-combustible.view`
- `pagina.reporte-documentos.view`

**Permisos CRUD por recurso**

Para cada recurso aplicable, usar el patrón `<recurso>.view`, `<recurso>.create`, `<recurso>.update`, `<recurso>.delete`. El inventario mínimo es:

`carga-combustible`, `solicitud-carga-combustible`, `tarjeta-combustible`, `tarjeta-saldo-movimiento`, `fondeo`, `vehiculo-fondeo-config`, `vehiculo`, `vehiculo-documento`, `alerta-documento`, `alerta-rendimiento`, `centro-costo`, `cuenta-concentradora`, `departamento`, `localidad`, `marca-vehiculo`, `tipo-documento`, `tipo-vehiculo`, `vehiculo-estatus`, `vehiculo-localidad`, `vehiculo-departamento`, `vehiculo-cuenta-analitica`, `vehiculo-tarjeta`, `vehiculo-responsable`, `vehiculo-chofer`, `usuario`, `rol`, `aseguradora`, `poliza-seguro` y `tipo-pago`.

**Permisos de operación específicos**

- `carga-combustible.create-extemporanea`
- `carga-combustible.update-own-assignment`
- `alerta-documento.update-own`
- `alerta-rendimiento.update-own`
- `fondeo-financiero.fondear`
- `fondeo-financiero.retirar`
- `fondeo-financiero.transferir`
- `reporte-combustible.export`
- `reporte-documentos.export`

Los permisos `*.update-own` sólo deben controlar la acción; la pertenencia del registro y el conjunto de vehículos deben seguir validándose con las reglas actuales y `FlotaScope`.

### Matriz aplicada en ETAPA 2

| Rol | Permisos asignados | Resultado funcional de esta etapa |
|---|---:|---|
| `admin` | 134 | Todos los permisos del catálogo, conservando además el bypass actual. |
| `activos` | 70 | Configuración, catálogos, activos, documentación, combustible y reportes conforme a la matriz. |
| `fondeo` | 17 | Fondeo operativo/financiero, tarjetas, movimientos, configuración financiera, vehículos y catálogos financieros necesarios. También conserva el alcance global de `FlotaScope`; no se modificó. |
| `responsable` | 17 | Mis vehículos, cargas, carga extemporánea, reportes y alertas/documentos relacionados con sus asignaciones; sin configuración administrativa. |
| `chofer` | 4 | Sólo módulo combustible, Mis vehículos y consulta/creación de cargas; sin reportes, documentos, vehículos generales ni configuración. |
| `administracion` | 0 | Rol conservado sin permisos nuevos, tal como fue aprobado. |

La asignación se realiza exclusivamente mediante roles; no se asignaron permisos directos a usuarios.

### Mapeo futuro del sidebar

Este mapeo queda documentado para etapas posteriores y no se aplicó todavía en la navegación:

| Opción/sidebar | Permiso que deberá controlar |
|---|---|
| Módulo Combustible | `modulo.combustible.view` |
| Módulo Documentación | `modulo.documentacion.view` |
| Módulo Configuración | `modulo.configuracion.view` |
| Mis Vehículos | `pagina.mis-vehiculos.view` |
| Fondeo Operativo | `pagina.fondeo-operativo.view` |
| Fondeo Financiero | `pagina.fondeo-financiero.view` |
| Carga extemporánea | `pagina.carga-extemporanea.view` |
| Reporte Combustible | `pagina.reporte-combustible.view` |
| Reporte Documentos | `pagina.reporte-documentos.view` |
| Recursos Filament | `<recurso>.view` |

### Diseño implementado en RoleResource

- El listado muestra la cantidad de permisos mediante `permissions_count`.
- El formulario de creación y edición muestra cuatro secciones plegables: Módulos, Páginas, Recursos y Operaciones.
- Las secciones usan `CheckboxList` con búsqueda, selección/deselección masiva y tres columnas para evitar una lista plana de 134 casillas.
- En edición, las casillas se hidratan desde `Role::permissions`; al guardar se sincroniza la relación many-to-many de Spatie.
- Las opciones se construyen desde `config/permissions.php`; los nombres técnicos no se duplican en `RoleResource`.

### Protecciones implementadas

- El acceso al catálogo requiere `rol.view`; crear, editar y eliminar requieren `rol.create`, `rol.update` y `rol.delete`, respectivamente.
- La protección se aplica en el recurso y, por tanto, también al acceso directo por URL; no depende sólo del sidebar.
- El rol `admin` no puede eliminarse individualmente ni mediante eliminación masiva.
- El nombre `admin` queda bloqueado y cualquier intento de guardarlo con otro nombre se restaura a `admin`.
- El rol `admin` mantiene siempre los 134 permisos, aunque el estado enviado no los incluya; se muestra una notificación explicativa.
- Si el usuario que edita un rol pertenece a ese mismo rol, se conservan sus permisos `rol.*` para evitar perder la administración accidentalmente.
- `administracion` permanece visible y con cero permisos hasta que un administrador decida modificarlo explícitamente.

## 5. Pruebas realizadas

- Búsqueda estática con `rg` de todas las funciones y símbolos solicitados.
- Revisión estática de `RoleSeeder`, `User`, `FlotaScope`, `Sidebar`, `AdminPanelProvider`, recursos y páginas que contienen autorización.
- Revisión de `tests/Feature/FlotaScopeVisibilityTest.php` y `tests/Feature/CargaCombustibleCreatePageTest.php` como evidencia del comportamiento actual de alcance y roles combinados.
- `php artisan test tests/Feature/PermissionSeederTest.php`: **4 pruebas, 16 aserciones, PASS**.
- `php artisan test tests/Feature/PermissionSeederTest.php tests/Feature/FlotaScopeVisibilityTest.php tests/Feature/CargaCombustibleCreatePageTest.php`: **11 pruebas, 28 aserciones, PASS**.
- `php artisan test tests/Feature/RoleResourceTest.php tests/Feature/PermissionSeederTest.php`: **9 pruebas, 33 aserciones, PASS**.
- Las pruebas de `RoleResource` cubren acceso autorizado, rechazo por URL directa, hidratación de permisos, actualización sin duplicados, ausencia de permisos directos a usuarios, protección de `admin` y permanencia de `administracion` con cero permisos.
- Se verificó sintaxis PHP de `config/permissions.php`, `PermissionSeeder.php`, `RoleSeeder.php` y `PermissionSeederTest.php`.
- Se comprobó idempotencia mediante dos ejecuciones del seeder dentro de la prueba, sin duplicar permisos, roles ni relaciones.
- Se verificó la existencia de `fondeo`, los 134 permisos de `admin`, los 0 permisos de `administracion` y la ausencia de permisos administrativos en `responsable` y `chofer`.
- La consulta segura de usuarios con `administracion` permanece pendiente: la base MySQL local no pudo conectar; no se ejecutó ningún seeder contra esa base.
- No se ejecutaron migraciones, seeders ni cambios en producción. No se ejecutaron comandos destructivos.

## 6. Trabajo pendiente para ETAPA 4

- Migrar los recursos y páginas restantes para utilizar el catálogo, sin modificar `FlotaScope` ni los filtros.
- Aplicar el mapeo de permisos al sidebar y a la navegación de los módulos restantes.
- Revisar la discrepancia entre la lógica actual de `ReporteDocumentos::canAccess()` y el permiso inicial restringido de `chofer`.
- Alinear `canAccess()`, `canViewAny()`, `canCreate()`, `canEdit()`, `canDelete()` y `visible()` en los módulos autorizados.
- Revisar el acceso directo a rutas de recursos y páginas restantes.
- Añadir pruebas de autorización por rol sin alterar el alcance de registros.
- Revisar notificaciones basadas en `User::role()` cuando se confirme el nuevo catálogo.
- Consultar usuarios con `administracion` cuando la base local esté disponible; no se asignarán permisos nuevos sin una decisión posterior.

## 7. Posibles riesgos o inconsistencias encontradas

- `fondeo` ya fue agregado a `RoleSeeder`; la asignación de permisos sólo se ejecutó en pruebas con SQLite en memoria, no en producción.
- `administracion` se conserva sin permisos; no fue posible verificar cuántos usuarios lo tienen porque la base MySQL local no está disponible.
- Hay recursos con acciones CRUD expuestas sin métodos de autorización explícitos; el acceso efectivo puede depender de defaults de Filament y no está expresado en el código del proyecto.
- `SolicitudCargaCombustibleResource` tiene páginas de edición pero no define `canEdit()`, mientras que creación y eliminación están deshabilitadas explícitamente.
- `TarjetaCombustibleResource` usa `canAccess()` y no el patrón CRUD habitual, por lo que no debe traducirse mecánicamente.
- Algunas reglas combinan rol y propiedad/asignación de registro. Sustituirlas sólo por permisos globales podría ampliar acceso indebidamente.
- `FlotaScope` considera `fondeo` un rol con acceso global a vehículos activos; esa lógica no se modificó.
- La lógica actual de `canAccess()` aún puede mostrar o permitir opciones a `chofer` que el catálogo ya no le asigna; la corrección de esas comprobaciones pertenece a ETAPA 4.
- La interfaz de `RoleResource` ya respeta permisos, pero los demás módulos todavía conservan comprobaciones rígidas por rol hasta ETAPA 4.
- Los usuarios con roles combinados (`chofer` + `responsable`) tienen comportamiento probado específicamente; la migración debe preservar la unión de sus asignaciones.

## Estado al cierre de ETAPA 3

La ETAPA 3 queda completada. No se inició la ETAPA 4: no se migraron recursos o páginas restantes, no se cambió la navegación de otros módulos y no se modificó `FlotaScope` ni ningún filtro de alcance.
