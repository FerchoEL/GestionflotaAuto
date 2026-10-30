# Exportación de flota total

En **Reporte Combustible Copia**, **Exportar Excel** conserva el resumen habitual
o el detalle de cargas al seleccionar un vehículo. **Exportar flota total** genera
`reporte_combustible_flota_total.xlsx` con las mismas 16 columnas del resumen y
una fila por unidad, incluso cuando se selecciona un vehículo específico.

El reporte mantiene primero las filas con cargas y agrega las unidades registradas
sin capturas que coincidan con los filtros, incluidas activas e inactivas. Abarca
tanto unidades que nunca han capturado como aquellas sin cargas en el periodo
seleccionado. Las filas adicionales tienen KM, litros e importe en cero; odómetros
y rendimiento real vacíos. Los datos de identificación y asignaciones se toman
del catálogo actual, igual que en el reporte habitual.

Se conservan los cálculos y totales existentes. En particular, los litros del
resumen corresponden a cargas con un recorrido calculable, mientras el importe
incluye todas las cargas filtradas. Tener litros en cero no prueba por sí solo
que una unidad no haya capturado: una primera carga sin odómetro previo tampoco
tiene recorrido calculable.

Se respetan vehículo, departamento, localidad, combustible, fechas y alcance por
usuario. Al filtrar por cuenta analítica, las cargas usan su cuenta histórica y
las unidades adicionales usan su asignación activa. Por ello, una fila en cero
significa que no hay cargas que coincidan con los filtros, no necesariamente que
no existan cargas en otras cuentas o fechas.

La descarga requiere `reporte-combustible.export`. Las tarjetas se exportan como
texto para conservar sus dígitos; los ceros se escriben como números y los datos
desconocidos permanecen vacíos. No requiere migraciones.
