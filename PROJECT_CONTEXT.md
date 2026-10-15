# Rionet Solar: contexto del proyecto

Última verificación: 2026-10-14. Este archivo resume el estado confirmado de la aplicación; el código, las pruebas y la base de datos son la fuente de verdad si algo cambia.

## Objetivo y alcance

Aplicación Laravel para preparar el diseño y la cotización futura de proyectos solares fotovoltaicos. Actualmente incluye los catálogos maestros y proyectos solares con dos submódulos: cargas eléctricas y dimensionamiento inicial de paneles, inversores, baterías y strings.

No implementar todavía los siguientes submódulos que el usuario dejó pendientes ni cotizaciones, salvo solicitud explícita. No cargar productos comerciales ni precios ficticios. La interfaz y los mensajes están en español y los precios se expresan en COP.

## Entorno

- PHP 8.3; Laravel 13.36.
- MySQL en Laragon; URL local: `http://rionetsolar.test` (`APP_URL`).
- Vistas Blade, Vite y Tailwind CSS 4; no se usa Bootstrap.
- Pest 4 para pruebas; las pruebas usan SQLite en memoria mediante `phpunit.xml`.
- No hay autenticación instalada. Las rutas `admin.*` están actualmente sin protección.

## Estructura funcional

| Área | Implementación |
| --- | --- |
| Electrodomésticos | `Appliance`; `Admin/ApplianceController`; requests `StoreApplianceRequest` y `UpdateApplianceRequest`; vistas `resources/views/admin/appliances`. |
| Equipos eléctricos | `ElectricalEquipment` y `ElectricalEquipmentCategory`; `Admin/ElectricalEquipmentController`; requests `StoreElectricalEquipmentRequest` y `UpdateElectricalEquipmentRequest`; vistas `resources/views/admin/electrical-equipment`. |
| Materiales | `InstallationMaterial`; `Admin/InstallationMaterialController`; requests `StoreInstallationMaterialRequest` y `UpdateInstallationMaterialRequest`; vistas `resources/views/admin/installation-materials`. |
| Proyectos solares | `SolarProject`; `Admin/SolarProjectController`; requests `StoreSolarProjectRequest`/`UpdateSolarProjectRequest`; vistas `resources/views/admin/solar-projects`. El workspace por proyecto enlaza cargas y dimensionamiento. |
| Cargas del proyecto | `SolarProjectAppliance`; `Admin/SolarProjectApplianceController`; `StoreSolarProjectApplianceRequest`; vista `resources/views/admin/solar-projects/loads`. |
| Dimensionamiento inicial | `SolarProjectSystemDesign`; `Admin/SolarProjectSystemDesignController`; `SaveSolarProjectSystemDesignRequest`; vista `resources/views/admin/solar-projects/system-design`. Cálculos en `App\Services\SolarProjectCalculator`. |
| Fichas técnicas | `InverterSpec`, `SolarPanelSpec`, `BatterySpec`, `DcBreakerSpec`, `AcBreakerSpec` y `SolarCableSpec`. |
| Lógica de equipos | `App\Services\ElectricalEquipmentCatalog` define campos/reglas por categoría y guarda el equipo junto a su ficha en una transacción. |
| Interfaz común | `resources/views/layouts/app.blade.php`, `resources/views/components/input-field.blade.php`, `resources/views/dashboard.blade.php`; marca SVG en `public/rionet-solar.svg`; estilos/JS en `resources/css/app.css` y `resources/js/app.js`. |

## Rutas y relaciones

- `/` muestra el dashboard y los conteos de catálogo.
- Recursos REST bajo `admin`: `appliances`, `electrical-equipment`, `installation-materials` y `solar-projects`; nombres con prefijo `admin.`.
- Rutas anidadas por proyecto: `admin.solar-projects.loads.index/store/destroy` y `admin.solar-projects.system-design.show/update`. Cada página opera sobre el proyecto de su URL.
- `ElectricalEquipmentCategory` tiene muchos equipos; `ElectricalEquipment` pertenece a una categoría.
- `ElectricalEquipment` tiene una relación `hasOne` con cada ficha técnica; cada ficha pertenece a un equipo. Las FK de las fichas son únicas y eliminan la ficha al eliminar el equipo.
- Los equipos no pueden cambiar de categoría después de crearse, para conservar la compatibilidad de la ficha técnica.
- `SolarProject` tiene muchos `SolarProjectAppliance` y una relación uno-a-uno con `SolarProjectSystemDesign`. Cada línea pertenece al proyecto y al electrodoméstico; el consumo horario queda copiado como snapshot.
- `SolarProjectSystemDesign` pertenece a un panel, inversor y batería activos de sus categorías correspondientes.

## Esquema y migraciones

Todas las migraciones listadas figuran aplicadas en la base MySQL local al verificar este documento con `php artisan migrate:status`:

1. `0001_01_01_000000_create_users_table.php`, `0001_01_01_000001_create_cache_table.php` y `0001_01_01_000002_create_jobs_table.php`: tablas base de Laravel.
2. `2026_10_14_174632_create_appliances_table.php`: cargas con voltaje 110/220, consumo energético durante una hora, descripción y estado. La migración creó originalmente `power_w`; el nombre histórico no se edita.
3. `2026_10_14_174633_create_electrical_equipment_categories_table.php`: categorías extensibles con nombre y slug únicos.
4. `2026_10_14_174634_create_electrical_equipment_table.php`: datos comerciales comunes, precio `DECIMAL(14,2)`, SKU único y FK a categoría.
5. `2026_10_14_174635_create_electrical_equipment_specs_tables.php`: seis tablas normalizadas de fichas técnicas.
6. `2026_10_14_174636_create_installation_materials_table.php`: materiales, precio `DECIMAL(14,2)`, unidad y SKU.
7. `2026_10_14_190843_add_max_power_voltage_to_solar_panel_specs_table.php`: Vmp como `DECIMAL(12,3)` obligatorio.
8. `2026_10_14_191633_add_max_power_current_to_solar_panel_specs_table.php`: Imp como `DECIMAL(12,3)` obligatorio.
9. `2026_10_14_200540_rename_power_w_to_energy_consumption_wh_on_appliances_table.php`: renombra en forma reversible la columna de electrodomésticos a `energy_consumption_wh`, preservando sus valores.
10. `2026_10_15_031705_create_solar_projects_table.php`: proyectos, horas solares, coordenadas opcionales, agregados `Wh/día`/pico `W` y strings inicialmente nulos.
11. `2026_10_15_031706_create_solar_project_appliances_table.php`: relación proyecto-carga con cantidades, horas, snapshot de consumo y cálculos diarios.
12. `2026_10_15_031707_create_solar_project_system_designs_table.php`: equipos elegidos, porcentajes y resultados de dimensionamiento.

`ElectricalEquipmentCategorySeeder` es idempotente y crea Inversores, Paneles solares, Baterías, Breakers DC, Breakers AC y Cable solar. No sembrar productos. Nunca usar `migrate:fresh` ni `db:wipe` contra la base del usuario.

## Reglas de datos importantes

- Crear/eliminar un equipo se traduce en activar/desactivar (`is_active`); la acción de eliminar no borra físicamente el registro.
- Los datos generales y la ficha técnica se guardan en una transacción. Las reglas de ficha se centralizan por slug de categoría en `ElectricalEquipmentCatalog`.
- Electrodomésticos: `energy_consumption_wh` representa la energía consumida durante una hora de funcionamiento (`Wh` en 1 h); no es potencia nominal en W ni una tasa `Wh/h`. La columna se valida como numérica y positiva.
- Proyecto: consumo total y nocturno se almacenan en `Wh/día`; la potencia pico simultánea se almacena en `W`. Pico y cantidad de strings son `null` hasta que existan cargas/dimensionamiento; los agregados diarios parten en cero.
- Cada carga calcula `Wh/día = Wh por hora × cantidad × horas`; la potencia pico suma `Wh por hora × cantidad` (promedio W durante una hora). Cambiar las cargas recalcula agregados e invalida los resultados/string count anteriores para evitar datos obsoletos.
- Dimensionamiento: cantidades redondeadas hacia arriba; el sobredimensionamiento y descarga máxima se capturan como porcentajes. Energía nominal de batería calculada con `capacity_ah × voltage_v`, nunca con `nominal_power_w`.
- Restricciones del diseño: voltaje batería igual a voltaje de carga del inversor; un string por inversor; potencia de los paneles por string no mayor al límite `mppt_max_power_w` del inversor. Los paneles se redondean hasta repartirlos por igual entre strings.
- El apoyo de energía de red quedó fuera del módulo, por decisión del usuario.
- Panel solar: `max_power_w`, `max_power_voltage_v` (Vmp), `max_power_current_a` (Imp), Voc, Isc, voltaje máximo del sistema y dimensiones en mm. Vmp e Imp son magnitudes separadas, obligatorias y positivas.
- Batería: `capacity_ah` es Ah, no Wh; `nominal_power_w` es opcional.
- Breakers AC y voltajes AC de inversor aceptan solo 110 o 220 V. El AWG se almacena como texto para admitir calibres como `4/0`.
- La eliminación de una categoría con equipos está restringida; las fichas dependen del equipo mediante FK.

## Estado verificado

- CRUD, búsqueda/filtros/paginación y vistas de detalle implementados para las tres áreas.
- Formularios técnicos distintos por categoría; el cliente muestra el grupo elegido, pero la validación de servidor es obligatoria.
- Categorías iniciales sembradas en MySQL local; al verificar, el catálogo no contenía fichas solares existentes.
- Las últimas comprobaciones registradas: `php artisan test --compact` pasó con 30 pruebas; Pint y `artisan view:cache` pasaron. Las tres migraciones solares están aplicadas a MySQL en Laragon; el formulario del nuevo proyecto carga en `rionetsolar.test`.
- Al último chequeo MySQL aún tenía 0 proyectos y 0 electrodomésticos; no se insertaron registros de prueba en la base real. El catálogo eléctrico ya contiene panel, inversor y batería activos.
- Pendiente por diseño: siguientes submódulos del proyecto, cotizaciones y autenticación.

## Comandos habituales

```powershell
php artisan migrate:status
php artisan migrate
php artisan db:seed --class=ElectricalEquipmentCategorySeeder
php artisan test --compact
npm run build
```

Para verificar versiones instaladas: `composer show --direct` y revisar `package.json`. Confirmar que Artisan apunta a MySQL antes de ejecutar migraciones locales.

## Mantenimiento de este contexto

Al iniciar una tarea, leer este archivo y luego inspeccionar solo el código/tests que controlen el comportamiento solicitado; no asumir que este resumen está actualizado. Después de un cambio, actualizar únicamente los hechos durables que cambiaron (migraciones aplicadas, arquitectura, rutas, decisiones o pendientes) y registrar como verificado solo lo que efectivamente pasó las comprobaciones. No convertir este documento en una bitácora de cada edición ni duplicar aquí todo el requerimiento original.