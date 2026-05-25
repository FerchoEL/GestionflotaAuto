# Tutorial de uso: Fondeo Financiero

## 1. Introducción

El panel de `Fondeo Financiero` es la vista operativa para controlar el saldo financiero de las tarjetas de combustible One Card, balancear recursos y generar solicitudes de recarga. Está pensado para usuarios con roles `admin` o `fondeo`.

## 2. Acceso

Desde el menú lateral de Filament:

- Módulo: `MÓDULO 1 Combustible`
- Sección: `Operación`
- Página: `Fondeo Financiero`

## 3. Qué muestra el dashboard

La página tiene dos bloques principales:

1. Indicadores generales:
   - `Críticas`: tarjetas con saldo operativo o porcentaje menor o igual a 40%
   - `Atención`: tarjetas con saldo operativo entre 40% y 69%
   - `Saludables`: tarjetas con saldo operativo igual o mayor a 70%

2. Tabla de tarjetas financieras con indicadores y acciones operativas.

## 4. Indicadores principales

Los tres contadores de color muestran el estado de las tarjetas con vehículo activo y asignación de litros:

- `Críticas`: saldo disponible en litros menor o igual a 0, o porcentaje < 40%
- `Atención`: saldo disponible > 0 y porcentaje entre 40% y 69%
- `Saludables`: saldo disponible > 0 y porcentaje >= 70%

El porcentaje se calcula como:

- saldo operativo en litros / litros asignados

## 5. Columnas de la tabla

Cada fila representa una tarjeta de combustible asociada a un vehículo activo.

- `Tarjeta`: número de tarjeta One Card.
- `No. Económico`: número económico del vehículo asignado.
- `Placa`: placa del vehículo.
- `Marca`: marca del vehículo.
- `Modelo`: modelo del vehículo.
- `Localidad`: localidad operativa del vehículo.
- `Departamento`: departamento responsable.
- `Asignado (L)`: litros asignados al vehículo para fondeo.
- `Precio $/L`: precio último por litro, calculado a partir de la última carga con precio válido o del último fondeo.
- `Objetivo $`: presupuesto financiero objetivo, igual a `Asignado (L)` × `Precio $/L`.
- `Movimientos One Card $`: sumatoria de movimientos financieros registrados para la tarjeta.
- `Saldo Financiero $`: el saldo actual de la tarjeta en pesos.
- `Saldo Operativo (L)`: saldo financiero convertido a litros según el precio actual.
- `Reposición $`: monto que falta para alcanzar el objetivo financiero.

## 6. Filtros disponibles

- `Asignacion`: permite ver únicamente tarjetas con vehículo asignado.

## 7. Acciones disponibles por tarjeta

### 7.1 Fondear

`Fondear` permite registrar un fondeo directo para la tarjeta activa.

- Visible sólo cuando la tarjeta tiene un vehículo activo.
 - Requiere que el vehículo tenga litros pendientes. No es necesario que exista un precio por litro registrado: el importe puede capturarse manualmente.
- Campos:
  - `Litros a fondear`
  - `Importe a fondear`
  - `Comentario`

Los valores por defecto se calculan con base en la asignación pendiente del vehículo y el saldo faltante para alcanzar el objetivo en pesos.

### 7.2 Retirar

`Retirar` permite extraer saldo de la tarjeta y devolverlo a una `Cuenta concentradora`.

- Visible sólo cuando hay saldo financiero positivo.
- No permite retirar más que el saldo disponible.
- Campos:
  - `Cuenta concentradora destino`
  - `Monto`
  - `Fecha`
  - `Referencia`
  - `Comentario`

### 7.3 Transferir

`Transferir` traslada dinero financiero entre tarjetas activas.

- Visible sólo si existe al menos otra tarjeta activa disponible.
- No permite transferir más que el saldo disponible.
- Campos:
  - `Tarjeta destino`
  - `Monto`
  - `Fecha`
  - `Referencia`
  - `Comentario`

### 7.4 Ajustar / Registrar Saldo Inicial

`Ajustar` registra correcciones manuales al saldo financiero de la tarjeta. Si la tarjeta aún no tiene movimientos (saldo = $0), el botón cambia automáticamente a `Registrar Saldo Inicial` y pre-completa la referencia con "Saldo Inicial - Tarjeta [número]".

- Esta acción crea un movimiento de ajuste financiero.
- Campos:
  - `Monto`
  - `Fecha`
  - `Referencia` (auto-sugerida si es primer movimiento)
  - `Comentario`

**Uso para saldo inicial:**
Si la tarjeta tiene efectivo físico de inicio (ej. $300) y aún no tiene movimientos financieros registrados:
1. Click en `Registrar Saldo Inicial` (botón que aparece cuando saldo = $0).
2. Captura `Monto = 300`.
3. `Referencia` estará pre-llenada con "Saldo Inicial - Tarjeta XXXXXXXXX".
4. Agrega `Comentario` si es necesario (ej. "Efectivo entregado en caja").
5. Guarda.

El sistema crea un movimiento `ajuste_one_card` con referencia clara de que fue un saldo inicial, y la auditoría quedará registrada.

### 7.5 Fondeo inicial / registrar saldo existente

**Caso: Tarjeta con saldo inicial pero sin movimientos registrados aún.**

Si la tarjeta ya tiene efectivo físico (ej. $300 MXN) y quieres reflejarlo en el sistema sin crear un "fondeo" ficticio:

- Abre la tarjeta en `Fondeo Financiero` (mostrará `Saldo Financiero $ = 0`).
- Verás el botón `Registrar Saldo Inicial` (em lugar del habitual `Ajustar`).
- Click y se abrirá un formulario con:
  - `Monto`: captura 300.
  - `Fecha`: hoy (por defecto).
  - `Referencia`: pre-llenada con "Saldo Inicial - Tarjeta [número tarjeta]" (puedes modificarla si quieres).
  - `Comentario`: opcional (ej. "Efectivo entregado en caja el 25/05/2026").
- Guarda.

El sistema registra un movimiento de ajuste con tipo `ajuste_one_card` y referencia clara. La auditoría quedará documentada como "saldo inicial".

**Notas:**
- Se crea un movimiento, no un `Fondeo` (que requeriría unidad de litros).
- Ideal para inicializar tarjetas sin movimientos previos.
- Después de registrar el saldo inicial, podrás usar `Fondear` normalmente para recargas posteriores.

## 8. Exportar solicitud de recarga

El botón `Exportar Solicitud de Recarga` genera un archivo Excel con la plantilla `one-card-template.xlsx` y el importe estimado de recarga.

- Sólo se exportan tarjetas con vehículo activo.
- El importe se calcula como la diferencia entre el `Objetivo $` y el `Saldo Financiero $`.

## 9. Reglas importantes

- Si una tarjeta no tiene un vehículo activo, no puede fondearse.
- El estado de color en la columna `Saldo Financiero $` se basa en el porcentaje de saldo operativo en litros:
  - `danger` / rojo: saldo <= 0 o porcentaje < 40%
  - `warning` / amarillo: porcentaje entre 40% y 69%
  - `success` / verde: porcentaje >= 70%
  - `gray` / gris: tarjeta sin vehículo asignado o sin asignación.

## 10. Flujo recomendado de uso

1. Abrir `Fondeo Financiero`.
2. Revisar los indicadores `Críticas`, `Atención` y `Saludables`.
3. Filtrar por `Solo con vehiculo asignado` si se requiere limpiar la vista.
4. Ubicar la tarjeta o vehículo a revisar.
5. Si el vehículo necesita recarga, usar `Fondear` con los litros faltantes y el importe correspondiente.
6. Si hay exceso de dinero, usar `Retirar` para devolverlo a una cuenta concentradora.
7. Si se requiere mover saldo entre tarjetas, usar `Transferir`.
8. Registrar ajustes manuales con `Ajustar` cuando el saldo contable no coincide.
9. Descargar `Exportar Solicitud de Recarga` para generar la requisición financiera.

## 11. Consejos prácticos

- Priorice tarjetas en estado `Críticas` para evitar desabasto.
- Use `Movimientos One Card $` para auditar qué operaciones han afectado el saldo.
- Compare `Objetivo $` con `Saldo Financiero $` antes de fondear o retirar.
- Mantenga `Comentario` y `Referencia` en los movimientos para facilitar la trazabilidad.

---

Este documento describe el uso operativo del dashboard `Fondeo Financiero` tal como está implementado en el sistema.
