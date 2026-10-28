# Reporte de tarjetas de combustible

Acceso: menú **Reportes → Reporte de tarjetas de combustible**, `/menu/reporte-tarjetas-combustible`.
Historial: `/menu/historial-tarjeta-combustible?tarjeta=ID`, desde cada fila.

Requiere ambos permisos existentes: `tarjeta-combustible.view` y `vehiculo-tarjeta.view`. La descarga reutiliza los mismos permisos de lectura y vuelve a autorizarlos al ejecutarse. No se crean permisos, roles, migraciones ni registros.

## Fuentes y reglas

- `TarjetaCombustible.vehiculoTarjetas`: catálogo completo; una fila por tarjeta, incluso inactiva o sin relaciones.
- `VehiculoTarjeta.vehiculo`: unidad, placa, marca y modelo. Los periodos son `fecha_inicio`/`fecha_fin`; el historial permanece en `vehiculo_tarjetas`.
- Vigencia: `activo = true`, conforme a `TarjetaCombustible.vehiculoActivo`, `Vehiculo.tarjetaActiva` y `VehiculoAsignacionActivaService`. No se sustituye esta regla con la de la exportación de catálogo preexistente, que además compara fechas.
- Conflictos: varias asignaciones activas por tarjeta, una unidad con varias tarjetas activas, asignación activa con fecha fin, inicio futuro o vehículo faltante. Se conservan todas las asignaciones activas y se concatenan sus datos en orden de ID.
- `Activo` refleja la asignación (1); queda vacío si no existe asignación vigente. En el historial se muestra el booleano real de cada registro.
- `Vehiculo.localidadActiva.localidad`: localidad actual conforme a la relación existente.
- `Vehiculo.cuentaAnaliticaActiva.cuentaAnalitica`: cuenta actual de la unidad, que es la regla predeterminada de `ResolverCuentaAnaliticaCarga`. No hay cuenta propia en `vehiculo_tarjetas`. Las selecciones particulares de cargas no se atribuyen a la tarjeta.
- Historial: `Vehiculo.cuentasAnaliticas.cuentaAnalitica`, buscando un único periodo que incluya el inicio de la asignación: inicio inclusivo y fin exclusivo, para no confundir dos cuentas en el día del cambio. Si falta, es ambiguo o no existe la cuenta relacionada, se muestra `SIN INFORMACIÓN`. Es una reconstrucción por periodo, no una cuenta almacenada en la asignación. La cuenta pudo cambiar durante una asignación larga.
- Departamento se revisó: se administra mediante `Vehiculo.departamentoActivo.departamento`, independientemente de tarjetas y cuentas; no determina la cuenta analítica de este reporte.

## Consulta y exportación

Tabla Filament paginada (25/50/100). Los filtros se muestran encima de la tabla en una cuadrícula adaptable de una a tres columnas, sin un menú flotante. El selector «Mostrar tarjetas» permite elegir «Solo asignadas», «Solo sin asignar» o «Con posibles duplicidades». Búsqueda parcial de tarjeta, económico y placa; filtros de campos, fechas y estados. Ordenamiento de tarjeta, económico, placa, marca, modelo y fecha de inicio; en conflictos se ordena por la primera asignación activa según ID.
Los indicadores cubren el catálogo completo (indicado en pantalla); los filtros afectan la tabla y la exportación. Los conflictos cuentan tarjetas afectadas, no filas históricas.

Exportación XLSX con las diez columnas solicitadas y los filtros, búsqueda y orden de la tabla. `FromQuery` consulta por lotes; `StringValueBinder` conserva tarjetas con ceros iniciales y evita interpretar fórmulas. Solo se incluye la hoja del estado actual; el historial está disponible en su vista paginada.

Las consultas usan conteos y existencia en SQL, relaciones precargadas para la página/lote y ninguna escritura. No se modifican captura de combustible, fondeos, rendimientos ni los archivos preexistentes de exportación de catálogo.

## Validación

```
php artisan test --compact tests/Feature/EstadoTarjetasReportTest.php tests/Feature/CatalogoTarjetasCombustibleReportTest.php tests/Feature/VehiculoAsignacionActivaIntegrityTest.php tests/Feature/ResolverCuentaAnaliticaCargaTest.php
```

Resultado: 17 pruebas, 86 aserciones, todas correctas. Incluye catálogo completo, tarjetas sin asignación, historial, reasignaciones, varias tarjetas por unidad en distintos periodos, duplicidades por tarjeta/unidad, fechas incompatibles, relaciones incompletas, cuentas presentes/ausentes/históricas, tabla Livewire, filtros y búsqueda, ordenamiento, XLSX real con número como texto y acceso denegado a página/historial/exportación.

Las pruebas usan SQLite en memoria, no datos de producción. Para simular duplicidades de esquemas legados se retiran y restauran únicamente los índices de la base de pruebas. La migración existente de integridad protege el esquema actual contra esas duplicidades. No se ejecutaron pruebas contra MySQL de producción ni validación visual en navegador.
