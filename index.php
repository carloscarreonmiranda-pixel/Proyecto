<?php
/* conexion sql*/
$con = new mysqli("localhost", "root", "", "sushi");
if ($con->connect_error) {
    die("Error de conexión: " . $con->connect_error);
}
/* Variables */
$seccion_activa = "crear";
$buscado  = null;
$editar   = null;
$eliminar = null;
$mensaje  = "";
$datos_lista = null;
/* Función para buscar por ID */
function buscar($con, $id) {
    $stmt = $con->prepare("SELECT * FROM `órdenes` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_assoc() : null;
}
/* Insertar */
if (isset($_POST['crear'])) {
    $cliente            = $_POST['cliente'];
    $descripcion_pedido = $_POST['descripcion_pedido'];
    $total              = $_POST['total'];
    $stmt = $con->prepare("INSERT INTO `órdenes` (cliente, descripcion_pedido, total, fecha_hora) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssd", $cliente, $descripcion_pedido, $total);

    if ($stmt->execute()) {
        $mensaje = "Orden registrada correctamente.";
    } else {
        $mensaje = "Error al registrar: " . $stmt->error;
    }
    $stmt->close();
    $seccion_activa = "lista";
}
/* BUSCAR */
if (isset($_POST['buscar'])) {
    $buscado = buscar($con, $_POST['id']);
    if (!$buscado) $mensaje = "Orden con ID " . $_POST['id'] . " no encontrada.";
    $seccion_activa = "buscar";
}
/* ACTUALIZAR */
if (isset($_POST['cargar_actualizar'])) {
    $editar = buscar($con, $_POST['id']);
    if (!$editar) $mensaje = "Orden con ID " . $_POST['id'] . " no encontrada para actualizar.";
    $seccion_activa = "actualizar";
}
if (isset($_POST['actualizar'])) {
    $id                 = $_POST['id'];
    $cliente            = $_POST['cliente'];
    $descripcion_pedido = $_POST['descripcion_pedido'];
    $total              = $_POST['total'];
    $stmt = $con->prepare("UPDATE `órdenes` SET cliente=?, descripcion_pedido=?, total=? WHERE id=?");
    $stmt->bind_param("ssdi", $cliente, $descripcion_pedido, $total, $id);
    if ($stmt->execute()) {
        $mensaje = "Orden $id actualizada correctamente.";
    } else {
        $mensaje = "Error al actualizar: " . $stmt->error;
    }
    $stmt->close();
    $seccion_activa = "lista";
}
/* ELIMINAR */
if (isset($_POST['cargar_eliminar'])) {
    $eliminar = buscar($con, $_POST['id']);
    if (!$eliminar) $mensaje = "Orden con ID " . $_POST['id'] . " no encontrada para eliminar.";
    $seccion_activa = "eliminar";
}
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    $stmt = $con->prepare("DELETE FROM `órdenes` WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = "Orden $id eliminada correctamente.";
    } else {
        $mensaje = "Error al eliminar: " . $stmt->error;
    }
    $stmt->close();
    $seccion_activa = "lista";
}
/* lista */
$datos_lista = $con->query("SELECT * FROM `órdenes` ORDER BY id DESC");
?>
 <!-- Pagina Principal -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Sushi Komaba</title>
  <style>
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="bg-slate-900 font-sans leading-relaxed text-slate-100">
  <!-- HEADER -->
  <header class="bg-slate-950/90 backdrop-blur shadow-sm sticky top-0 z-50 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center space-x-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#38bdf8" class="w-8 h-8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C7 3 3 7 3 12s4 9 9 9 9-4 9-9S17 3 12 3zm0 0c0 4 4 9 9 9M3 12c4 0 9-4 9-9" />
          </svg>
          <h1 class="text-3xl font-bold text-sky-300 tracking-tight">Sushi Komaba</h1>
        </div>
        <!-- Menu computadora -->
        <nav class="hidden md:flex space-x-8">
          <a href="#inicio" class="text-slate-100 font-medium hover:text-yellow-300 transition-colors duration-200">Inicio</a>
          <a href="#descripcion" class="text-slate-100 font-medium hover:text-yellow-300 transition-colors duration-200">Descripción</a>
          <a href="#menu" class="text-slate-100 font-medium hover:text-yellow-300 transition-colors duration-200">Menú</a>
          <a href="#ordenes" class="text-slate-100 font-medium hover:text-yellow-300 transition-colors duration-200">Órdenes</a>
          <a href="#contacto" class="text-slate-100 font-medium hover:text-yellow-300 transition-colors duration-200">Contacto</a>
        </nav>
        <!-- Buscador -->
        <div class="hidden sm:flex items-center space-x-2">
          <input id="buscador" type="text" placeholder="Ir a (menu, ordenes...)"
                 class="px-4 py-2 w-48 lg:w-64 border border-sky-500/60 bg-slate-900/60 text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:border-transparent placeholder:text-slate-400 text-sm">
          <button id="btn-buscar"
            class="bg-yellow-400 text-slate-900 p-2 rounded-lg font-semibold hover:bg-yellow-300 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-yellow-300 shadow-lg shadow-yellow-400/20">
            🔍
          </button>
        </div>
        <!-- Botón Móvil -->
        <button id="menu-btn" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-sky-200 hover:text-yellow-300 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-yellow-300">
          <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
      <!-- Menu hamburguesa -->
      <nav id="nav-mobile" class="hidden md:hidden flex-col space-y-2 mt-4 bg-slate-950 rounded-lg px-4 py-3 border border-slate-800">
        <a href="#inicio" class="block py-2 text-slate-100 hover:text-yellow-300 border-b border-slate-800">Inicio</a>
        <a href="#descripcion" class="block py-2 text-slate-100 hover:text-yellow-300 border-b border-slate-800">Descripción</a>
        <a href="#menu" class="block py-2 text-slate-100 hover:text-yellow-300 border-b border-slate-800">Menú</a>
        <a href="#ordenes" class="block py-2 text-slate-100 hover:text-yellow-300 border-b border-slate-800">Órdenes</a>
        <a href="#contacto" class="block py-2 text-slate-100 hover:text-yellow-300">Contacto</a>
      </nav>
    </div>
  </header>
  <!-- HERO -->
  <section id="inicio" class="relative bg-gradient-to-br from-sky-600 via-sky-700 to-cyan-600 text-white py-28 text-center overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/sushi.png')]"></div>
    <div class="relative z-10 max-w-4xl mx-auto px-4">
      <h2 class="text-5xl md:text-6xl font-extrabold mb-6 drop-shadow-xl tracking-tight">
        Bienvenido a <span class="text-yellow-300">Sushi Komaba</span> 🍣
      </h2>
      <p class="text-xl md:text-2xl mb-10 font-light opacity-95 max-w-2xl mx-auto">
        La fusión perfecta entre tradición japonesa y sabor moderno. ¡Todo lo que tu estómago desea!
      </p>
      <a href="#ordenes"
         class="inline-block bg-yellow-400 text-slate-900 py-4 px-10 rounded-full font-bold shadow-2xl hover:shadow-yellow-400/50 transition-all duration-300 hover:-translate-y-1 hover:bg-yellow-300">
        Hacer Pedido Ahora
      </a>
    </div>
  </section>
  <!-- DESCRIPCIÓN -->
  <section id="descripcion" class="py-20 bg-slate-950 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 text-center">
      <span class="text-sky-400 font-semibold tracking-wider uppercase text-sm">Nuestra Historia</span>
      <h2 class="text-4xl font-extrabold text-white mt-2 mb-8">Sobre Nosotros</h2>
      <p class="text-slate-400 text-lg leading-relaxed">
        En Sushi Komaba, nos dedicamos a traer los sabores más auténticos de Japón directamente a tu mesa. 
        Utilizamos ingredientes frescos seleccionados diariamente para garantizar que cada rollo, nigiri y sashimi sea una obra de arte culinaria.
      </p>
    </div>
  </section>
  <!-- MENÚ -->
  <section id="menu" class="py-20 bg-slate-900">
    <div class="max-w-6xl mx-auto px-4">
      <!-- diseño -->
      <div class="relative w-full h-64 md:h-80 rounded-2xl overflow-hidden shadow-2xl mb-10 group">
        <img 
          src="https://images.unsplash.com/photo-1579871494447-9811cf80d66c?q=80&w=1470&auto=format&fit=crop" 
          alt="Banner Menú Sushi" 
          class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent opacity-90"></div>
        <div class="absolute bottom-0 left-0 p-8">
          <h2 class="text-4xl md:text-5xl font-extrabold text-white drop-shadow-lg">Nuestro Menú</h2>
          <p class="text-yellow-300 font-medium mt-2 text-lg">Explora nuestros sabores exclusivos</p>
        </div>
      </div>
      <!-- PLATILLOS -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Platillo 1 -->
        <div class="bg-slate-950 rounded-xl p-6 border border-slate-800 hover:border-sky-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-sky-500/10 group">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-bold text-white group-hover:text-sky-300 transition-colors">Explosion Dulce</h3>
            <span class="bg-sky-900/30 text-sky-300 px-3 py-1 rounded-full text-sm font-bold">$120</span>
          </div>
          <p class="text-slate-400 text-sm">Chocolate,algodon y arroz con leche.</p>
        </div>
        <!-- Platillo 2-->
        <div class="bg-slate-950 rounded-xl p-6 border border-slate-800 hover:border-sky-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-sky-500/10 group">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-bold text-white group-hover:text-sky-300 transition-colors">Nigiri Salmón</h3>
            <span class="bg-sky-900/30 text-sky-300 px-3 py-1 rounded-full text-sm font-bold">$150</span>
          </div>
          <p class="text-slate-400 text-sm">Corte fino de salmón noruego fresco sobre una cama de arroz avinagrado.</p>
        </div>
        <!-- Platillo 3 -->
        <div class="bg-slate-950 rounded-xl p-6 border border-slate-800 hover:border-sky-500/50 transition-all duration-300 hover:shadow-lg hover:shadow-sky-500/10 group">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-bold text-white group-hover:text-sky-300 transition-colors">Sashimi Mix</h3>
            <span class="bg-sky-900/30 text-sky-300 px-3 py-1 rounded-full text-sm font-bold">$280</span>
          </div>
          <p class="text-slate-400 text-sm">Selección premium de atún, salmón y pescado blanco del día (12 piezas).</p>
        </div>
      </div>
    </div>
  </section>
  <!-- ÓRDENES -->
  <section id="ordenes" class="py-20 bg-slate-950 border-t border-slate-800">
    <div class="max-w-6xl mx-auto px-4">
      <div class="text-center mb-10">
        <span class="text-sky-400 font-semibold tracking-wider uppercase text-sm">Sistema de Pedidos</span>
        <h2 class="text-4xl font-extrabold text-white mt-2">Gestión de Órdenes</h2>
      </div>
      <?php if($mensaje): ?>
        <div class="bg-yellow-400/10 border border-yellow-400/50 text-yellow-300 p-4 rounded-lg mb-8 text-center font-semibold shadow-lg animate-pulse">
          <?= htmlspecialchars($mensaje) ?>
        </div>
      <?php endif; ?>
      <!-- Ordenar -->
      <div class="flex flex-wrap justify-center gap-4 mb-10">
        <button onclick="mostrar('crear')" class="px-6 py-3 rounded-lg font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-lg shadow-sky-600/20 ring-1 ring-sky-500">
         Nueva Orden
        </button>
        <button onclick="mostrar('lista')" class="px-6 py-3 rounded-lg font-bold text-slate-900 bg-emerald-400 hover:bg-emerald-300 transition-all shadow-lg shadow-emerald-400/20">
          Ver Lista
        </button>
        <button onclick="mostrar('buscar-form')" class="px-6 py-3 rounded-lg font-bold text-white bg-purple-600 hover:bg-purple-500 transition-all shadow-lg shadow-purple-600/20 ring-1 ring-purple-500">
          Buscar
        </button>
        <button onclick="mostrar('actualizar-form')" class="px-6 py-3 rounded-lg font-bold text-white bg-orange-600 hover:bg-orange-500 transition-all shadow-lg shadow-orange-600/20 ring-1 ring-orange-500">
          Editar
        </button>
        <button onclick="mostrar('eliminar-form')" class="px-6 py-3 rounded-lg font-bold text-white bg-rose-600 hover:bg-rose-500 transition-all shadow-lg shadow-rose-600/20 ring-1 ring-rose-500">
          Eliminar
        </button>
      </div>
      <!-- FORMULARIO -->
      <div id="crear" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl max-w-2xl mx-auto">
        <h3 class="text-2xl font-bold text-sky-300 mb-6 border-b border-slate-800 pb-4">Registrar Pedido</h3>
        <form method="POST" class="space-y-5">
          <div>
            <label class="block text-slate-400 text-sm font-semibold mb-2">Nombre del Cliente</label>
            <input type="text" name="cliente" class="w-full px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
          </div>
          <div>
            <label class="block text-slate-400 text-sm font-semibold mb-2">Descripción del Pedido</label>
            <textarea name="descripcion_pedido" required rows="3" class="w-full px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">   
            </textarea>
          </div>
          <div>
            <label class="block text-slate-400 text-sm font-semibold mb-2">Total ($)</label>
            <input type="number" name="total" step="0.01" required placeholder="0.00"
                   class="w-full px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
          </div>
          <button type="submit" name="crear" class="w-full bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-300 hover:to-yellow-400 text-slate-900 py-3 rounded-lg font-bold shadow-lg transform transition hover:scale-[1.01]">
            Confirmar Orden
          </button>
        </form>
      </div>
      <!-- TABLA  -->
      <div id="lista" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl overflow-hidden">
        <h3 class="text-2xl font-bold text-emerald-400 mb-6 border-b border-slate-800 pb-4">Historial de Órdenes</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-slate-400 text-sm uppercase tracking-wider border-b border-slate-700">
                <th class="py-3 px-4">ID</th>
                <th class="py-3 px-4">Cliente</th>
                <th class="py-3 px-4">Descripción</th>
                <th class="py-3 px-4">Total</th>
                <th class="py-3 px-4">Fecha</th>
              </tr>
            </thead>
            <tbody class="text-slate-300 text-sm divide-y divide-slate-800">
              <?php if($datos_lista && $datos_lista->num_rows > 0): ?>
                <?php while($fila = $datos_lista->fetch_assoc()): ?>
                  <tr class="hover:bg-slate-800/50 transition-colors">
                    <td class="py-3 px-4 font-mono text-sky-400">#<?= htmlspecialchars($fila['id']) ?></td>
                    <td class="py-3 px-4 font-medium text-white"><?= htmlspecialchars($fila['cliente']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($fila['descripcion_pedido']) ?></td>
                    <td class="py-3 px-4 text-emerald-400 font-bold">$<?= number_format($fila['total'], 2) ?></td>
                    <td class="py-3 px-4 text-slate-500"><?= htmlspecialchars($fila['fecha_hora']) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-500">No hay órdenes registradas aún.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- RESULTADO -->
      <div id="buscar-form" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-purple-400 mb-6">Buscar Orden</h3>
        <form method="POST" class="flex gap-2">
          <input type="number" name="id" required placeholder="ID de Orden"
                 class="flex-1 px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:border-purple-500 focus:outline-none">
          <button type="submit" name="buscar" class="bg-purple-600 hover:bg-purple-500 text-white px-6 py-3 rounded-lg font-bold">
            Buscar
          </button>
        </form>
      </div>
      <div id="buscar" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-purple-400 mb-6">Resultado</h3>
        <?php if($buscado): ?>
          <div class="space-y-4 text-slate-300">
            <div class="p-4 bg-slate-950 rounded-lg border border-slate-800">
              <p class="text-sm text-slate-500">Orden #<?= htmlspecialchars($buscado['id']) ?></p>
              <p class="text-xl font-bold text-white mt-1"><?= htmlspecialchars($buscado['cliente']) ?></p>
              <p class="mt-2"><?= htmlspecialchars($buscado['descripcion_pedido']) ?></p>
              <div class="mt-3 flex justify-between items-center pt-3 border-t border-slate-800">
                <span class="text-emerald-400 font-bold text-lg">$<?= number_format($buscado['total'], 2) ?></span>
                <span class="text-xs text-slate-600"><?= htmlspecialchars($buscado['fecha_hora']) ?></span>
              </div>
            </div>
          </div>
        <?php else: ?>
          <p class="text-slate-500 text-center">No se encontró la orden.</p>
        <?php endif; ?>
        <button onclick="mostrar('buscar-form')" class="mt-6 w-full bg-slate-800 hover:bg-slate-700 text-white py-2 rounded-lg transition-colors">Volver a Buscar</button>
      </div>
      <!-- ACTUALIZAR -->
      <div id="actualizar-form" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-orange-400 mb-6">Modificar Orden</h3>
        <form method="POST" class="flex gap-2">
          <input type="number" name="id" required placeholder="ID de Orden"
                 class="flex-1 px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:border-orange-500 focus:outline-none">
          <button type="submit" name="cargar_actualizar" class="bg-orange-600 hover:bg-orange-500 text-white px-6 py-3 rounded-lg font-bold">
            Cargar
          </button>
        </form>
      </div>
      <div id="actualizar" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl max-w-2xl mx-auto">
        <h3 class="text-2xl font-bold text-orange-400 mb-6">Editar Datos</h3>
        <?php if($editar): ?>
          <form method="POST" class="space-y-5">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id']) ?>">
            <div>
              <label class="block text-slate-400 text-sm font-semibold mb-2">Cliente</label>
              <input type="text" name="cliente" value="<?= htmlspecialchars($editar['cliente']) ?>" required
                     class="w-full px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:border-orange-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-slate-400 text-sm font-semibold mb-2">Descripción</label>
              <textarea name="descripcion_pedido" required rows="3"
                        class="w-full px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:border-orange-500 focus:outline-none"><?= htmlspecialchars($editar['descripcion_pedido']) ?></textarea>
            </div>
            <div>
              <label class="block text-slate-400 text-sm font-semibold mb-2">Total</label>
              <input type="number" name="total" step="0.01" value="<?= htmlspecialchars($editar['total']) ?>" required
                     class="w-full px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:border-orange-500 focus:outline-none">
            </div>
            <button type="submit" name="actualizar" class="w-full bg-orange-600 hover:bg-orange-500 text-white py-3 rounded-lg font-bold">Guardar Cambios</button>
          </form>
        <?php endif; ?>
      </div>
      <!-- ELIMINAR -->
      <div id="eliminar-form" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-rose-500 mb-6">Borrar Orden</h3>
        <form method="POST" class="flex gap-2">
          <input type="number" name="id" required placeholder="ID de Orden"
                 class="flex-1 px-4 py-3 bg-slate-950 text-slate-100 border border-slate-700 rounded-lg focus:border-rose-500 focus:outline-none">
          <button type="submit" name="cargar_eliminar" class="bg-rose-600 hover:bg-rose-500 text-white px-6 py-3 rounded-lg font-bold">
            Cargar
          </button>
        </form>
      </div>
      <div id="eliminar" class="tarjeta hidden bg-slate-900 rounded-2xl p-8 border border-slate-800 shadow-2xl max-w-md mx-auto text-center">
        <h3 class="text-2xl font-bold text-rose-500 mb-4">¿Eliminar Definitivamente?</h3>
        <?php if($eliminar): ?>
          <div class="bg-rose-500/10 p-4 rounded-lg mb-6 text-left">
            <p class="text-slate-300"><strong>Cliente:</strong> <?= htmlspecialchars($eliminar['cliente']) ?></p>
            <p class="text-slate-300"><strong>Total:</strong> $<?= number_format($eliminar['total'], 2) ?></p>
          </div>
          <form method="POST" class="flex gap-3">
            <input type="hidden" name="id" value="<?= htmlspecialchars($eliminar['id']) ?>">
            <button type="submit" name="eliminar" class="flex-1 bg-rose-600 hover:bg-rose-500 text-white py-3 rounded-lg font-bold">Sí, Eliminar</button>
            <button type="button" onclick="mostrar('eliminar-form')" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white py-3 rounded-lg font-bold">Cancelar</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <!-- CONTACTO -->
  <section id="contacto" class="py-20 bg-gradient-to-b from-slate-900 to-slate-950">
    <div class="max-w-4xl mx-auto px-4 text-center">
      <h2 class="text-4xl font-extrabold text-white mb-8">Contáctanos</h2>
      <div class="grid md:grid-cols-3 gap-8 text-slate-300">
        <div class="p-6 rounded-lg bg-slate-900/50 border border-slate-800">
          <div class="text-yellow-400 text-2xl mb-2">📍</div>
          <p>Gral. Carlos Plank 103, Magdalena de Kino.</p>
        </div>
        <div class="p-6 rounded-lg bg-slate-900/50 border border-slate-800">
          <div class="text-yellow-400 text-2xl mb-2">📞</div>
          <p>+52 (55) 1234-5678</p>
        </div>
        <div class="p-6 rounded-lg bg-slate-900/50 border border-slate-800">
          <div class="text-yellow-400 text-2xl mb-2">⏰</div>
          <p>Lun-Dom: 12:00 - 22:00</p>
        </div>
      </div>
    </div>
  </section>
  <footer class="bg-slate-950 py-8 border-t border-slate-800 text-slate-400 text-sm">
    <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center">
      <p>© 2025 Sushi Komaba. <br>
       Autor: Carlos Roberto Carreon Miranda.</p>
    </div>
  </footer>
  <script>
    const menuBtn = document.getElementById('menu-btn');
    const navMobile = document.getElementById('nav-mobile');
    if (menuBtn) {
      menuBtn.addEventListener('click', () => {
        navMobile.classList.toggle('hidden');
        navMobile.classList.toggle('flex');
      });
    }
    function mostrar(idSeccion) {
      document.querySelectorAll('.tarjeta').forEach(el => el.classList.add('hidden'));
      const seleccionada = document.getElementById(idSeccion);
      if (seleccionada) {
        seleccionada.classList.remove('hidden');
        seleccionada.classList.add('animate-fade-in-up');
      }
    }
    const inputBuscar = document.getElementById('buscador');
    const btnBuscar   = document.getElementById('btn-buscar');
    function irASeccion() {
      const texto = inputBuscar.value.toLowerCase().trim();
      const mapa = {
        'inicio': 'inicio', 'home': 'inicio',
        'descripcion': 'descripcion', 'nosotros': 'descripcion',
        'menu': 'menu', 'menú': 'menu', 'comida': 'menu', 'sushi': 'menu',
        'ordenes': 'ordenes', 'órdenes': 'ordenes', 'pedido': 'ordenes', 'pedidos': 'ordenes',
        'contacto': 'contacto', 'ubicacion': 'contacto'
      };
      const destino = mapa[texto];
      if (destino) {
        const seccion = document.getElementById(destino);
        seccion.scrollIntoView({ behavior: 'smooth' });
        if (destino === 'ordenes') {
          setTimeout(() => mostrar('crear'), 600); 
        }
      } else {
        inputBuscar.classList.add('ring-2', 'ring-red-500');
        setTimeout(() => inputBuscar.classList.remove('ring-red-500'), 500);
      }
    }
    if(btnBuscar) btnBuscar.addEventListener('click', irASeccion);
    if(inputBuscar) {
      inputBuscar.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') irASeccion();
      });
    }
    window.onload = () => {
    mostrar("<?= $seccion_activa ?>");
    };
  </script>
</body>
</html>
<?php $con->close(); ?>