<!-- Cabecera profesional – Maquinaria Pesada B2B -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom" style="border-color:#004080;">
  <div class="container">
    <!-- Marca / Inicio -->
    <a class="navbar-brand text-primary font-weight-bold" href="index.php">
      <i class="fas fa-industry mr-2"></i>Maquinaria B2B
    </a>
    <!-- Toggle móvil -->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNav"
      aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <!-- Menú -->
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ml-auto">
        <!-- Cotizaciones -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-dark" href="#" id="cotizacionesDropdown" role="button"
             data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-file-alt mr-1"></i>Cotizaciones
          </a>
          <div class="dropdown-menu" aria-labelledby="cotizacionesDropdown">
            <a class="dropdown-item" href="index.php?step=1">
              <i class="fas fa-plus-circle mr-1"></i>Crear Cotización
            </a>
            <a class="dropdown-item" href="ver_cotizaciones.php">
              <i class="fas fa-eye mr-1"></i>Ver Cotizaciones
            </a>
          </div>
        </li>
        <!-- Pedidos -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-dark" href="#" id="pedidosDropdown" role="button"
             data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-truck mr-1"></i>Pedidos
          </a>
          <div class="dropdown-menu" aria-labelledby="pedidosDropdown">
            <a class="dropdown-item" href="ver_cotizaciones.php">
              <i class="fas fa-plus-circle mr-1"></i>Crear Pedido
            </a>
            <a class="dropdown-item" href="ver_pedidos.php">
              <i class="fas fa-eye mr-1"></i>Ver Pedidos
            </a>
          </div>
        </li>

        <!-- Cerrar sesión -->
        <li class="nav-item">
          <a class="nav-link text-dark" href="logout.php">
            <i class="fas fa-sign-out-alt mr-1"></i>Cerrar sesión
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Estilos adicionales para la cabecera -->
<style>
  .navbar-brand { font-size: 1.4rem; }
  .nav-link { font-weight: 500; }
  .nav-link:hover, .nav-link:focus { color: #004080 !important; }
  .dropdown-menu { border-radius: 4px; }
  .dropdown-item i { width: 1rem; text-align: center; }
</style>