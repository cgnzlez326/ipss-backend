<?php

declare(strict_types=1);

use App\Controllers\OfertaController;
use App\Controllers\PostulacionController;
use App\Controllers\EstadoPostulacionController;
use App\Controllers\UsuarioController;

/**
 * Definicion de rutas de la API.
 * Formato: [METODO HTTP, PATRON, CONTROLADOR@METODO, ROL]
 *
 * ROL permite el control de acceso por perfil:
 *   'public'    -> sin autenticacion (registro)
 *   'any'       -> cualquier usuario autenticado
 *   'candidato' -> solo perfil candidato
 *   'reclutador'-> solo perfil reclutador
 *
 * Requisito: cada controlador expone los 4 endpoints basicos:
 * create (POST), read (GET), update (PUT/PATCH), delete (DELETE).
 */

return [
    // ------------------- Usuarios (candidatos y reclutadores) -------------------
    // [POST]   /usuarios                     Crear usuario (registro publico de candidato)
    // [GET]    /usuarios                     Listar usuarios (reclutador)
    // [GET]    /usuarios/{id}                Obtener un usuario (candidato solo el propio)
    // [PUT]    /usuarios/{id}                Actualizar usuario (candidato solo el propio)
    // [DELETE] /usuarios/{id}                Eliminar usuario (reclutador)
    ['POST',   '/usuarios',         UsuarioController::class . '@create',  'public'],
    ['GET',    '/usuarios',         UsuarioController::class . '@read',    'reclutador'],
    ['GET',    '/usuarios/{id}',    UsuarioController::class . '@readOne', 'any'],
    ['PUT',    '/usuarios/{id}',    UsuarioController::class . '@update',  'any'],
    ['DELETE', '/usuarios/{id}',    UsuarioController::class . '@delete',  'reclutador'],

    // ------------------- Ofertas laborales -------------------
    // [POST]   /ofertas                     Crear oferta (reclutador)
    // [GET]    /ofertas                     Listar ofertas (candidato ve solo activas)
    // [GET]    /ofertas/{id}                Obtener una oferta
    // [PUT]    /ofertas/{id}                Actualizar oferta (reclutador)
    // [DELETE] /ofertas/{id}                Desactivar oferta - baja logica (reclutador)
    ['POST',   '/ofertas',          OfertaController::class . '@create',   'reclutador'],
    ['GET',    '/ofertas',          OfertaController::class . '@read',     'any'],
    ['GET',    '/ofertas/{id}',     OfertaController::class . '@readOne',  'any'],
    ['PUT',    '/ofertas/{id}',     OfertaController::class . '@update',   'reclutador'],
    ['DELETE', '/ofertas/{id}',     OfertaController::class . '@delete',   'reclutador'],

    // ------------------- Postulaciones -------------------
    // [POST]   /postulaciones               Crear postulacion (candidato se postula)
    // [GET]    /postulaciones               Listar postulaciones (candidato solo las suyas)
    // [GET]    /postulaciones/{id}          Obtener postulacion con estado y comentarios
    // [PUT]    /postulaciones/{id}          Cambiar estado + comentario (reclutador)
    // [DELETE] /postulaciones/{id}          Eliminar postulacion (reclutador)
    ['POST',   '/postulaciones',    PostulacionController::class . '@create',   'candidato'],
    ['GET',    '/postulaciones',    PostulacionController::class . '@read',     'any'],
    ['GET',    '/postulaciones/{id}', PostulacionController::class . '@readOne', 'any'],
    ['PUT',    '/postulaciones/{id}', PostulacionController::class . '@update',  'reclutador'],
    ['DELETE', '/postulaciones/{id}', PostulacionController::class . '@delete',  'reclutador'],

    // ------------------- Estados de postulacion (catalogo) -------------------
    // [GET]    /estados                      Listar estados del catalogo
    ['GET',    '/estados',          EstadoPostulacionController::class . '@read', 'any'],
];
