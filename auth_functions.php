<?php
/**
 * Funciones de autenticación y autorización
 * Sistema de control de acceso basado en roles
 */

/**
 * Verifica si el usuario está autenticado
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtiene el rol del usuario actual
 */
function obtenerRolUsuario() {
    return $_SESSION['rol'] ?? null;
}

/**
 * Verifica si el usuario tiene un rol específico
 */
function tieneRol($rol) {
    return obtenerRolUsuario() === $rol;
}

/**
 * Verifica si el usuario tiene acceso a un módulo específico
 */
function tieneAccesoModulo($modulo) {
    $rol = obtenerRolUsuario();
    
    // ROL GERENTE/ADMINISTRADOR: Acceso total
    if ($rol === 'GERENTE' || $rol === 'ADMINISTRADOR') {
        return true;
    }
    
    // ROL ASISTENTE: Solo acceso a pedidos
    if ($rol === 'ASISTENTE') {
        return $modulo === 'pedidos';
    }
    
    return false;
}

/**
 * Verifica si el usuario puede acceder a la página actual
 */
function puedeAccederPagina($moduloActual) {
    if (!estaAutenticado()) {
        return false;
    }
    
    return tieneAccesoModulo($moduloActual);
}

/**
 * Redirige al usuario si no tiene permisos
 */
function requerirAccesoModulo($modulo) {
    if (!estaAutenticado()) {
        header("Location: ../../login_registro.php");
        exit();
    }
    
    if (!tieneAccesoModulo($modulo)) {
        header("Location: ../../plataforma.php?error=acceso_denegado");
        exit();
    }
}

/**
 * Obtiene los módulos disponibles para el usuario actual
 */
function obtenerModulosDisponibles() {
    $rol = obtenerRolUsuario();
    
    if ($rol === 'GERENTE' || $rol === 'ADMINISTRADOR') {
        return [
            'categorias' => '📂 Gestión de Categorías',
            'productos' => '🍔 Gestión de Productos',
            'proveedores' => '🏢 Gestión de Proveedores',
            'clientes' => '👤 Gestión de Clientes',
            'pedidos' => '🛒 Gestión de Pedidos',
            'usuarios' => '👥 Gestión de Usuarios',
            'ciudades' => '🏙️ Gestión de Ciudades',
            'provincias' => '🏛️ Gestión de Provincias',
            'distritos' => '🏘️ Gestión de Distritos'
        ];
    }
    
    if ($rol === 'ASISTENTE') {
        return [
            'pedidos' => '🛒 Gestión de Pedidos'
        ];
    }
    
    return [];
}

/**
 * Obtiene el nombre del rol en formato legible
 */
function obtenerNombreRol($rol) {
    $roles = [
        'ADMINISTRADOR' => 'Administrador',
        'GERENTE' => 'Gerente',
        'ASISTENTE' => 'Asistente'
    ];
    
    return $roles[$rol] ?? $rol;
}

/**
 * Verifica si el usuario puede realizar una acción específica
 */
function puedeRealizarAccion($accion) {
    $rol = obtenerRolUsuario();
    
    // ROL GERENTE/ADMINISTRADOR: Puede realizar todas las acciones
    if ($rol === 'GERENTE' || $rol === 'ADMINISTRADOR') {
        return true;
    }
    
    // ROL ASISTENTE: Solo puede realizar acciones en pedidos
    if ($rol === 'ASISTENTE') {
        $accionesPermitidas = [
            'pedidos_view',
            'pedidos_create',
            'pedidos_edit',
            'pedidos_delete',
            'pedidos_view_details'
        ];
        
        return in_array($accion, $accionesPermitidas);
    }
    
    return false;
}
?>
