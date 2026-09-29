---
id: 0002
status: accepted
date: 2026-09-28
---

# ADR 0002 · Laravel 12 + MariaDB + Blade renderizado en servidor (registrado a posteriori)

## Contexto
Decisión tomada antes de adoptar ADRs; se registra a posteriori a partir del código. El proyecto
necesita un sistema de gestión para una clínica dental con varios roles, formularios CRUD y
agenda, desarrollado por un equipo académico.

## Decisión
- Framework: Laravel 12 (PHP ≥ 8.2) como monolito MVC.
- Presentación: vistas Blade renderizadas en el servidor con CSS propio en `public/css/`.
- Base de datos: MariaDB (10.11 en Docker), PKs UUID.
- Autenticación: sesión de Laravel + Google OAuth con `laravel/socialite`.
- Entorno: Docker Compose (php-fpm, nginx, MariaDB, phpMyAdmin).

## Alternativas consideradas
- No documentadas en el repositorio (decisión previa a los ADR).

## Consecuencias
- Positivas: un solo despliegue, CSRF y sesión integrados, curva de aprendizaje baja.
- Negativas: sin capa de dominio el monolito acumula lógica en controladores (ver spec 013);
  Vite y Tailwind quedaron declarados sin uso real.
