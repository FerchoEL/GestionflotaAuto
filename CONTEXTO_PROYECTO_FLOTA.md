# Contexto Técnico del Proyecto Flota

## 1. Propósito del sistema

Este proyecto es una plataforma interna de gestión de flota construida en Laravel 11 con panel administrativo en Filament 3. El sistema concentra en una sola aplicación:

- Administración de vehículos y su información maestra.
- Asignación histórica y activa de responsables, choferes, localidades, departamentos, cuentas analíticas y tarjetas de combustible.
- Registro y control de cargas de combustible.
- Control de fondeo operativo en litros y fondeo financiero en pesos.
- Monitoreo del saldo financiero de tarjetas One Card y sus movimientos.
- Cálculo de rendimiento real por vehículo.
- Generación de alertas automáticas por bajo rendimiento, rendimiento anormal y vencimiento documental.
- Control documental por vehículo, incluyendo pólizas de seguro.
- Exportación de reportes operativos y documentales a Excel.

## 2. Estado actual de documentación y planeación

### Documentación existente

- `README.md`: no documenta el sistema real; sigue siendo el README genérico de Laravel.
- `TUTORIAL_FONDEO_FINANCIERO.md`: sí documenta un módulo real del sistema, específicamente el dashboard de fondeo financiero.
- `resources/markdown/terms.md` y `resources/markdown/policy.md`: contenido legal, no técnico.

### Planeación encontrada

No existe una planeación funcional o técnica formal dentro del repositorio en forma de roadmap, especificación de arquitectura, backlog o documento de alcance. La principal fuente de verdad actual es el código.

## 3. Stack y dependencias principales

- Backend: Laravel 11
- PHP: 8.2+
- Panel administrativo: Filament 3
- Componentes reactivos: Livewire 3
- Autenticación: Laravel Jetstream + Fortify + Sanctum
- Roles y permisos: `spatie/laravel-permission`
- Exportación/importación Excel: `maatwebsite/excel` y `PhpSpreadsheet`
- Frontend build: Vite + Tailwind CSS

## 4. Arquitectura general

La aplicación es mayormente un sistema server-rendered con panel administrativo Filament. No usa una API de negocio extensa; la lógica vive sobre todo en:

- `app/Models`: entidades del dominio y relaciones.
- `app/Filament/Resources`: CRUDs y pantallas de administración.
- `app/Filament/Pages`: dashboards y reportes operativos.
- `app/Services`: lógica de negocio reusable.
- `app/Observers`: disparadores automáticos de notificaciones por alertas.
- `app/Exports`: exportaciones a Excel.
- `app/Console/Commands`: automatizaciones por consola.

La entrada principal del sistema para usuarios internos es el panel Filament publicado en `/menu`.

## 5. Módulos funcionales del sistema

### 5.1 Módulo 1: Combustible

Es el módulo más desarrollado del sistema. Controla operación, saldo de tarjetas, cargas, fondeo y reportes.

#### Submódulo: Mis Vehículos

Archivo principal: `app/Filament/Pages/MisVehiculos.php`

Objetivo:

- Mostrar al usuario los vehículos que tiene asignados como chofer o responsable.
- Consultar historial de rendimiento, documentos y alertas abiertas del vehículo seleccionado.

Funciones relevantes:

- `vehiculosAsignados()`: resuelve qué unidades puede ver el usuario.
- `vehiculoSeleccionado()`: carga relaciones principales del vehículo.
- `historialRendimiento()`: usa `ReporteCombustibleService` para mostrar rendimiento histórico real.
- `documentosVehiculo()`: lista documentos asociados a la unidad.
- `alertasAbiertas()`: muestra alertas de rendimiento abiertas.

#### Submódulo: Carga de combustible

Archivos principales:

- `app/Filament/Resources/CargaCombustibleResource.php`
- `app/Models/CargaCombustible.php`
- `app/Services/RendimientoService.php`
- `app/Services/TarjetaMovimientoService.php`

Objetivo:

- Registrar cada carga de combustible.
- Asociarla a un vehículo, tarjeta, cuenta analítica y evidencia fotográfica.
- Calcular rendimiento y afectar saldo financiero de tarjeta cuando corresponde.

Reglas importantes:

- Valida secuencia histórica del odómetro.
- Puede sugerir la cuenta analítica activa del vehículo.
- Permite captura con acceso por rol.
- Mantiene evidencia fotográfica de odómetro, ticket y bomba.

Funciones relevantes:

- `scopeOrderedChronologically()` y `scopeOrderedChronologicallyDesc()` en `CargaCombustible`.
- `RendimientoService::procesarCarga()`: calcula rendimiento por secuencia de cargas.
- `RendimientoService::recalcularDesdeCarga()`: recompone historial si una carga afecta la secuencia.
- `TarjetaMovimientoService::sincronizarCarga()`: convierte una carga en movimiento financiero negativo de tarjeta.

#### Submódulo: Carga extemporánea

Archivo principal: `app/Filament/Pages/RegistrarCargaExtemporanea.php`

Objetivo:

- Insertar una carga histórica fuera del flujo normal sin romper la secuencia de kilometraje.

Reglas:

- Solo accesible para `admin` y `responsable`.
- Exige motivo de corrección.
- Valida que el odómetro quede entre la carga anterior y la siguiente.
- Marca la carga como `es_extemporanea`.

#### Submódulo: Fondeo operativo

Archivo principal: `app/Filament/Pages/FondeoDashboard.php`

Objetivo:

- Controlar, por vehículo, cuántos litros tiene asignados, cuánto saldo operativo conserva y cuánto falta por reponer.

Conceptos calculados:

- Asignado en litros.
- Precio por litro.
- Objetivo financiero en pesos.
- Movimientos One Card.
- Saldo financiero.
- Saldo operativo en litros.
- Impacto One Card en litros.
- Porcentaje de fondo disponible.
- Pendiente de reposición.

Funciones relevantes:

- `obtenerAsignado()`
- `calcularSaldo()`
- `obtenerImpactoOneCard()`
- `calcularPendiente()`
- `calcularPorcentaje()`
- `colorSemaforo()`
- `iconoSemaforo()`

Acción principal:

- `fondear`: crea un registro en `Fondeo` validando que no exceda el pendiente.

#### Submódulo: Fondeo financiero

Archivo principal: `app/Filament/Pages/FondeoFinancieroDashboard.php`

Objetivo:

- Administrar el saldo financiero real de tarjetas One Card en pesos.
- Permitir fondeos, retiros, transferencias y ajustes.
- Traducir el saldo financiero a capacidad operativa en litros.

Acciones operativas:

- `Fondear`
- `Retirar`
- `Transferir`
- `Ajustar`
- `Registrar saldo inicial`
- `Exportar solicitud de recarga`

Funciones clave:

- `exportSolicitudRecarga()`
- `calcularImporteDeCarga()`
- `buildMovimientoForm()`
- `buildRetiroForm()`
- `puedeRetirarTarjeta()`
- `puedeFondearTarjeta()`
- `puedeTransferirTarjeta()`
- `obtenerPendienteLitrosTarjeta()`
- `esBalanceInicial()`

Notas:

- Este módulo sí tiene documento específico en `TUTORIAL_FONDEO_FINANCIERO.md`.
- Usa SQL enriquecido para presentar indicadores financieros ya agregados por tarjeta.

#### Submódulo: Tarjetas y movimientos One Card

Archivos principales:

- `app/Models/TarjetaCombustible.php`
- `app/Models/TarjetaSaldoMovimiento.php`
- `app/Services/OneCardTarjetaImportService.php`
- `app/Services/TarjetaMovimientoService.php`
- `app/Services/TarjetaSaldoService.php`

Objetivo:

- Mantener catálogo de tarjetas.
- Asociarlas a vehículos.
- Llevar ledger financiero por movimientos.
- Importar tarjetas desde archivo Excel de One Card.

Funciones principales:

- `TarjetaCombustible::normalizarNumero()`: homogeniza números de tarjeta.
- `OneCardTarjetaImportService::importarDesdeArchivo()`: importa tarjetas desde la hoja `BD`.
- `TarjetaMovimientoService::resolverTarjetaIdVehiculoEnFecha()`: ubica la tarjeta activa de un vehículo en una fecha.
- `TarjetaMovimientoService::sincronizarFondeo()`: registra fondeo como movimiento positivo.
- `TarjetaMovimientoService::sincronizarCarga()`: registra consumo como movimiento negativo.
- `TarjetaMovimientoService::eliminarMovimientoDeOrigen()`: borra movimiento sincronizado cuando ya no aplica.
- `TarjetaSaldoService`: calcula saldo financiero, saldo operativo, precio histórico y semáforo.

#### Submódulo: Reporte de combustible

Archivos principales:

- `app/Filament/Pages/ReporteCombustible.php`
- `app/Services/ReporteCombustibleService.php`
- `app/Exports/ReporteCombustibleExport.php`

Objetivo:

- Consultar detalle de cargas con rendimiento real calculado.
- Resumir consumo por vehículo.
- Exportar a Excel.

Particularidad técnica:

- El rendimiento real se calcula usando los litros de la carga anterior, no los litros de la carga actual.
- Usa funciones de ventana SQL (`LAG`) para obtener odómetro y litros previos por vehículo.

### 5.2 Módulo 2: Documentación

Controla expedientes documentales por unidad y sus alertas de vigencia.

#### Submódulo: Tipos de documento

Archivos principales:

- `app/Models/TipoDocumento.php`
- `app/Filament/Resources/TipoDocumentoResource.php`

Objetivo:

- Configurar clases de documento.
- Definir si requieren vigencia y si representan póliza de seguro.

#### Submódulo: Documentos por vehículo

Archivos principales:

- `app/Models/VehiculoDocumento.php`
- `app/Filament/Resources/VehiculoDocumentoResource.php`
- `app/Models/PolizaSeguro.php`
- `app/Filament/Resources/PolizaSeguroResource.php`

Objetivo:

- Registrar documentos ligados a una unidad.
- Guardar fechas de emisión, vencimiento y archivo.
- Asociar información adicional de póliza cuando el tipo de documento corresponde.

Funciones relevantes en `VehiculoDocumento`:

- `requiereVigencia()`
- `colorEstadoVigencia()`
- `estadoAlertaDocumento()`

#### Submódulo: Alertas documentales

Archivos principales:

- `app/Models/AlertaDocumento.php`
- `app/Filament/Resources/AlertaDocumentoResource.php`
- `app/Console/Commands/VerificarAlertasDocumentos.php`
- `app/Observers/AlertaDocumentoObserver.php`
- `app/Notifications/AlertaDocumentoMailNotification.php`

Objetivo:

- Detectar documentos vencidos o próximos a vencer.
- Crear y cerrar alertas automáticas.
- Notificar por correo a responsables, usuarios de activos y administradores.

Flujo:

1. El comando `documentos:verificar-alertas` recorre documentos.
2. Calcula si están `vigente`, `por_vencer` o `vencido`.
3. Cierra alertas abiertas que ya no aplican.
4. Genera nuevas alertas si corresponde.
5. El observer dispara correo al crearse una alerta abierta.

#### Submódulo: Reporte documental

Archivos principales:

- `app/Filament/Pages/ReporteDocumentos.php`
- `app/Services/ReporteDocumentosService.php`
- `app/Exports/ReporteDocumentosExport.php`

Objetivo:

- Consultar documentos por filtros de unidad, departamento, localidad, tipo y vigencia.
- Aplicar visibilidad por rol.
- Exportar reporte a Excel.

### 5.3 Módulo 3: Mantenimiento

Actualmente no tiene implementación funcional. En `Sidebar.php` se define como “Disponible para futuras secciones”.

## 6. Configuración maestra y catálogos

La parte de configuración del sistema vive casi por completo en recursos Filament. Los catálogos principales son:

- `VehiculoResource`: altas y edición de vehículos.
- `UserResource`: usuarios del sistema.
- `RoleResource`: roles.
- `DepartamentoResource`
- `LocalidadResource`
- `TipoVehiculoResource`
- `VehiculoEstatusResource`
- `MarcaVehiculoResource`
- `CuentaConcentradoraResource`
- `CentroCostoResource` que realmente administra `CuentaAnalitica`.
- `AseguradoraResource`
- `TipoPagoResource`

## 7. Asignaciones históricas y activas

Este es uno de los patrones más importantes del dominio.

Modelos implicados:

- `VehiculoResponsable`
- `VehiculoChofer`
- `VehiculoTarjeta`
- `VehiculoDepartamento`
- `VehiculoLocalidad`
- `VehiculoCuentaAnalitica`
- `VehiculoFondeoConfig`

Idea central:

- El sistema no solo guarda el valor actual, sino el historial de asignaciones.
- Solo puede existir una asignación activa a la vez según el contexto.
- Al crear una nueva asignación activa, la anterior se cierra automáticamente.

Servicio crítico:

- `app/Services/VehiculoAsignacionActivaService.php`

Funciones:

- `guardarDepartamento()`
- `guardarLocalidad()`
- `guardarResponsable()`
- `guardarTarjeta()`
- `guardarAsignacion()`

Las pruebas muestran además que la base de datos impone integridad para evitar dos asignaciones activas simultáneas en ciertos casos.

## 8. Modelos de dominio principales

### Núcleo de flota

- `Vehiculo`: entidad central del sistema.
- `TipoVehiculo`
- `VehiculoEstatus`
- `MarcaVehiculo`

`Vehiculo` concentra relaciones hacia:

- tipo
- estatus
- responsables
- choferes
- documentos
- cargas
- rendimientos
- fondeos
- tarjeta activa
- localidad activa
- departamento activo
- cuenta analítica activa

Accesores importantes en `Vehiculo`:

- `getDisplayNameAttribute()`
- `getUsuariosAsignadosTextoAttribute()`
- `getUsuarioResponsableTextoAttribute()`

### Combustible y fondeo

- `CargaCombustible`
- `Fondeo`
- `TarjetaCombustible`
- `TarjetaSaldoMovimiento`
- `VehiculoFondeoConfig`
- `CuentaConcentradora`
- `CuentaAnalitica`

### Documentación

- `VehiculoDocumento`
- `TipoDocumento`
- `PolizaSeguro`
- `Aseguradora`
- `TipoPago`

### Alertas y auditoría

- `AlertaRendimiento`
- `AlertaDocumento`
- `AlertaFondeo`
- `AuditoriaAlerta`
- `MotivoAuditoria`

## 9. Servicios de negocio y responsabilidad de cada uno

### `RendimientoService`

Responsabilidad:

- Procesar cargas y generar registros de rendimiento.
- Detectar desviaciones respecto al rendimiento óptimo del vehículo.
- Crear alertas automáticas.

Reglas relevantes:

- La primera carga se marca como base.
- El rendimiento se calcula con litros de la carga anterior.
- Usa tolerancia por vehículo o parámetro global.
- Detecta dos tipos de alerta:
  - `bajo_rendimiento`
  - `rendimiento_anormal_alto`

### `ReporteCombustibleService`

Responsabilidad:

- Armar vistas analíticas de combustible con rendimiento real.
- Generar detalle y resumen por filtros.

### `ReporteDocumentosService`

Responsabilidad:

- Listar documentos con estado de vigencia interpretado.
- Aplicar restricción de acceso por rol.
- Generar resumen agregado por estatus.

### `TarjetaMovimientoService`

Responsabilidad:

- Mantener sincronizados los movimientos financieros de tarjeta con fondeos y cargas.

Tipos de movimiento usados:

- `fondeo_tarjeta`
- `consumo_combustible`
- además existen ajustes, retiros y transferencias generados desde la UI financiera

### `TarjetaSaldoService`

Responsabilidad:

- Traducir movimientos de tarjeta a saldos financieros y operativos.
- Calcular objetivo, pendiente, porcentaje y semáforo.

### `VehiculoAsignacionActivaService`

Responsabilidad:

- Garantizar consistencia en asignaciones activas y su histórico.

### `OneCardTarjetaImportService`

Responsabilidad:

- Importar catálogo de tarjetas desde Excel de proveedor One Card.

## 10. Alertas, observers y notificaciones

Observers registrados en `AppServiceProvider`:

- `AlertaDocumentoObserver`
- `AlertaRendimientoObserver`
- `AlertaFondeoObserver`

Comportamiento:

- Escuchan creación de alertas.
- Usan `DB::afterCommit()` para evitar disparos antes de confirmar transacción.
- Validan correos con `App\Support\EmailGuard`.
- Envían notificaciones por mail a usuarios objetivo.

Notificaciones:

- `AlertaDocumentoMailNotification`
- `AlertaRendimientoMailNotification`
- `AlertaFondeoMailNotification`

## 11. Roles y visibilidad funcional

Roles observados en código:

- `admin`
- `activos`
- `fondeo`
- `responsable`
- `chofer`

Resumen operativo:

- `admin`: acceso amplio al sistema, reportes y configuración.
- `activos`: acceso amplio de operación/configuración operativa.
- `fondeo`: visibilidad operativa asociada a combustible/fondeo.
- `responsable`: ve y opera sobre vehículos bajo su supervisión.
- `chofer`: ve información ligada a sus unidades asignadas.

Archivos relevantes:

- `app/Support/FlotaScope.php`
- validaciones `canAccess`, `canViewAny`, `canCreate`, `canEdit` en recursos y páginas Filament.

## 12. Exportaciones e integraciones de archivos

Exportaciones:

- `ReporteCombustibleExport`
- `ReporteDocumentosExport`
- `SolicitudRecargaExport`

Importación:

- `OneCardTarjetaImportService` lee Excel del proveedor One Card.

Dependencia importante:

- La exportación de solicitud de recarga usa una plantilla Excel en `storage/app/templates/onecard-solicitud-recarga.xlsx`.

## 13. Rutas y acceso

Rutas HTTP tradicionales:

- `/`: landing pública.
- `/presentacion/junta-directiva`: vista de presentación ejecutiva.
- `/dashboard`: dashboard básico protegido de Jetstream.

Acceso real a operación:

- Panel Filament en `/menu`.

No hay una API de negocio robusta; `routes/api.php` solo contiene el endpoint estándar de usuario autenticado.

## 14. Cobertura de pruebas encontrada

Pruebas de negocio relevantes:

- `RendimientoServiceTest`
- `ReporteCombustibleServiceTest`
- `AlertaFondeoObserverTest`
- `VehiculoAsignacionActivaIntegrityTest`

Pruebas de framework/autenticación:

- login
- registro
- reset de contraseña
- verificación de email
- tokens API
- 2FA
- perfil

Lectura de cobertura real:

- Sí existe validación automatizada de partes críticas del dominio.
- La cobertura de negocio todavía es parcial.
- No se observaron pruebas extensas para documentación, dashboard financiero, importación One Card ni flujo completo de alertas documentales.

## 15. Riesgos y observaciones para futuras IAs

- El `README.md` no representa el proyecto; no debe usarse como fuente funcional.
- El sistema mezcla conceptos operativos en litros y financieros en pesos; es importante no confundirlos.
- Muchas reglas de negocio viven en recursos Filament y no solo en servicios.
- Las asignaciones activas son históricas; no deben modelarse como simples campos planos.
- El cálculo de rendimiento depende del orden cronológico de cargas y del odómetro previo.
- Las alertas documentales dependen de un comando programable, no solo de eventos directos del CRUD.
- El dashboard de fondeo financiero tiene bastante lógica embebida en consultas SQL y acciones de tabla.

## 16. Resumen ejecutivo para otra IA

Si otra IA necesita trabajar sobre este proyecto, debe asumir primero lo siguiente:

1. La entidad central es `Vehiculo`.
2. El sistema gira alrededor de asignaciones activas e históricas por vehículo.
3. El módulo más maduro es combustible/fondeo.
4. El saldo de tarjetas se administra en `TarjetaSaldoMovimiento`.
5. El rendimiento real se deriva de secuencia histórica de `CargaCombustible`.
6. La vigencia documental se interpreta desde `VehiculoDocumento` y se automatiza con alertas.
7. La UI principal es Filament, no controladores web tradicionales.
8. Los permisos por rol alteran fuertemente la visibilidad de datos.

## 17. Archivos clave para entender el sistema rápido

- `app/Models/Vehiculo.php`
- `app/Models/CargaCombustible.php`
- `app/Models/TarjetaCombustible.php`
- `app/Models/TarjetaSaldoMovimiento.php`
- `app/Models/VehiculoDocumento.php`
- `app/Services/RendimientoService.php`
- `app/Services/TarjetaSaldoService.php`
- `app/Services/TarjetaMovimientoService.php`
- `app/Services/VehiculoAsignacionActivaService.php`
- `app/Services/ReporteCombustibleService.php`
- `app/Services/ReporteDocumentosService.php`
- `app/Filament/Pages/FondeoFinancieroDashboard.php`
- `app/Filament/Pages/FondeoDashboard.php`
- `app/Filament/Pages/MisVehiculos.php`
- `app/Filament/Pages/RegistrarCargaExtemporanea.php`
- `app/Livewire/Admin/Sidebar.php`

