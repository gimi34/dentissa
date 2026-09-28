---
id: 009
slug: servicios
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 009 · Catálogo de servicios

## Problema
La clínica necesita un catálogo de tratamientos con duración, precio y color de agenda, para
agendar citas con la duración correcta y distinguirlas visualmente.

## Historias de usuario
- Como admin, quiero dar de alta y editar los servicios que ofrece la clínica.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [x] CA1 · Dado un admin, cuando da de alta un servicio, entonces se guardan el precio y el color escritos.
- [x] CA2 · Dada una edición parcial, cuando se guarda, entonces el precio y el resto de campos no tocados se conservan.
- [x] CA3 · Dado un precio negativo, cuando se envía, entonces se rechaza y el servicio no cambia.
- [x] CA4 · Dado un color de agenda que no es `#RRGGBB`, cuando se envía, entonces se rechaza.
- [x] CA5 · Dado un cambio de precio, cuando se guarda, entonces se refleja en las vistas SQL que lo usan.
- [ ] CA6 · Dado un admin, cuando borra un servicio, entonces se elimina físicamente.
- [ ] CA7 (abuso) · Como usuario que no es admin, intento gestionar servicios → 403 por `role:admin`.

## Fuera de alcance
- Precios por paciente, promociones o facturación.

## Seguridad y privacidad
- Datos sensibles involucrados: No aplica.
- Quién puede hacer qué: solo admin.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:49`, `app/Http/Controllers/ServiciosController.php`, `app/Models/Servicio.php`, `resources/views/servicios/*.blade.php`
- Tests: `tests/Feature/ServiciosPrecioTest.php` (CA1–CA4), `tests/Feature/AdminVistasSmokeTest.php::test_las_vistas_de_servicios_conservan_los_campos_no_tocados` (CA2), `::test_el_precio_correcto_se_propaga_a_las_vistas_sql` (CA5)

## Observaciones (solo si status=inferred)
- `Route::resource` registra `GET /admin/servicios/{id}`, pero el controlador no tiene `show`.
- El borrado es físico, mientras el resto del sistema usa baja lógica.
- `index` ignora el filtro `buscar` aunque la vista tiene buscador.
- `store` asigna el UUID a mano aunque el modelo usa `HasUuids`; no hay flash de éxito.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
