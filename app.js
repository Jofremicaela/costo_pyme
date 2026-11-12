
const $ = id => document.getElementById(id);

const pesos = n =>
  '$ ' + Math.round(n).toLocaleString('es-AR');

// Los costos fijos de la base se toman como mensuales
// y se convierten al período seleccionado.
const FACTOR = {
  day: 1 / 30,
  week: 7 / 30,
  month: 1,
  year: 12
};

let DATOS = null;

// ========================================
// MENSAJES
// ========================================

function mostrarMensaje(mensaje, clase = 'mal') {
  const lista = $('alertas');
  lista.replaceChildren();

  const li = document.createElement('li');
  li.className = clase;
  li.textContent = mensaje;

  lista.appendChild(li);
}

// ========================================
// CONEXIÓN CON API.PHP
// ========================================

async function api(consulta) {
  const r = await fetch('api.php?' + consulta, {
    method: 'GET',
    headers: {
      'Accept': 'application/json'
    },
    credentials: 'same-origin'
  });

  const datos = await r.json();

  if (!r.ok) {
    throw new Error(datos.error || 'Error al consultar la base de datos.');
  }

  return datos;
}

// ========================================
// CARGAR PRODUCTOS
// ========================================

async function cargarProductos() {
  try {
    DATOS = null;

    const cliente = $('cliente').value;

    const lista = await api(
      'accion=productos&cliente=' + encodeURIComponent(cliente)
    );

    const select = $('producto');
    select.replaceChildren();

    if (!Array.isArray(lista) || lista.length === 0) {
      const opcion = document.createElement('option');
      opcion.value = '';
      opcion.textContent = 'No hay productos disponibles';

      select.appendChild(opcion);
      select.disabled = true;

      $('kpis').replaceChildren();
      $('sens').replaceChildren();

      mostrarMensaje(
        'Este cliente todavía no tiene productos registrados.',
        'aviso'
      );

      return;
    }

    select.disabled = false;

    lista.forEach(p => {
      const opcion = document.createElement('option');

      opcion.value = p.id;
      opcion.textContent = p.nombre;

      select.appendChild(opcion);
    });

    // Si se acaba de crear un producto, seleccionarlo automáticamente.
    const nuevoId = new URLSearchParams(window.location.search).get('producto_nuevo');
    if (nuevoId && lista.some(p => String(p.id) === String(nuevoId))) {
      select.value = String(nuevoId);
    }

    await cargarProducto();

  } catch (error) {
    console.error(error);
    mostrarMensaje(error.message);
  }
}

// ========================================
// CARGAR DATOS DE UN PRODUCTO
// ========================================

async function cargarProducto() {
  try {
    const id = $('producto').value;

    if (!id) {
      return;
    }

    const cliente = $('cliente').value;
    DATOS = await api(
      'accion=producto&id=' + encodeURIComponent(id) +
      '&cliente=' + encodeURIComponent(cliente)
    );

    $('margen').value = DATOS.producto.margen_porcentaje;
    $('precioManual').value = DATOS.producto.precio_manual || '';

    $('varVar').value = 0;
    $('varFijos').value = 0;

    ponerVolumen();
    calcular();

  } catch (error) {
    DATOS = null;
    console.error(error);
    mostrarMensaje(error.message);
  }
}

// ========================================
// CONFIGURAR VOLUMEN
// ========================================

function ponerVolumen() {
  if (!DATOS) return;

  const periodo = $('periodo').value;
  const objetivo = DATOS.objetivos[periodo];

  $('volumen').value =
    objetivo !== undefined
      ? objetivo
      : Math.round(
          DATOS.producto.volumen_esperado * FACTOR[periodo]
        );
}

// ========================================
// SUMAR COSTOS
// ========================================

const suma = tipo =>
  DATOS.costos
    .filter(c => c.tipo_costo === tipo)
    .reduce((total, c) => total + Number(c.monto), 0);

// ========================================
// MODELO DE CÁLCULO
// ========================================

function modelo(vol, precioFijo) {
  const factor = FACTOR[$('periodo').value];

  // La mano de obra se carga como un costo desde el apartado Costos.
  const fijosTotal =
    (
      suma('fixed') +
      suma('indirect') +
      suma('labor')
    ) *
    factor *
    (1 + Number($('varFijos').value) / 100);

  const variable =
    suma('variable') *
    (1 + Number($('varVar').value) / 100);

  const margen = Number($('margen').value) / 100;

  const costoUnit =
    vol > 0
      ? fijosTotal / vol + variable
      : 0;

  const sugerido =
    margen < 1
      ? costoUnit / (1 - margen)
      : 0;

  const manual = parseFloat($('precioManual').value);

  const precio =
    precioFijo !== undefined
      ? precioFijo
      : (manual > 0 ? manual : sugerido);

  const costoTotal = fijosTotal + variable * vol;
  const ingreso = vol * precio;

  return {
    fijosTotal,
    variable,
    costoUnit,
    sugerido,
    precio,
    costoTotal,
    ingreso,
    ganancia: ingreso - costoTotal,

    equilibrio:
      precio > variable
        ? Math.ceil(fijosTotal / (precio - variable))
        : null
  };
}

// ========================================
// CALCULAR Y MOSTRAR RESULTADOS
// ========================================

function calcular() {
  if (!DATOS) return;

  $('margenTxt').textContent = $('margen').value;
  $('varVarTxt').textContent = $('varVar').value;
  $('varFijosTxt').textContent = $('varFijos').value;

  const vol = Math.max(
    0,
    parseFloat($('volumen').value) || 0
  );

  const r = modelo(vol);

  const usandoManual =
    parseFloat($('precioManual').value) > 0;

  // ========================================
  // INDICADORES PRINCIPALES
  // ========================================

  const kpi = (titulo, valor, clase = '') =>
    `<div class="kpi ${clase}">
      ${titulo}
      <b>${valor}</b>
    </div>`;

  $('kpis').innerHTML =
    kpi('Costo unitario', pesos(r.costoUnit)) +
    kpi('Precio sugerido', pesos(r.sugerido)) +
    kpi(
      'Precio usado',
      pesos(r.precio) + (usandoManual ? ' (manual)' : '')
    ) +
    kpi('Ingreso', pesos(r.ingreso)) +
    kpi('Costo total', pesos(r.costoTotal)) +
    kpi(
      'Ganancia',
      pesos(r.ganancia),
      r.ganancia < 0 ? 'mal' : 'bien'
    ) +
    kpi(
      'Punto de equilibrio',
      r.equilibrio === null
        ? 'No se alcanza'
        : r.equilibrio + ' unidades'
    );

  // ========================================
  // ALERTAS Y SUGERENCIAS
  // ========================================

  const alertas = [];

  if (r.precio < r.costoUnit) {
    alertas.push([
      'mal',
      `El precio (${pesos(r.precio)}) es menor que el costo unitario (${pesos(r.costoUnit)}): se pierde ${pesos(r.costoUnit - r.precio)} por unidad.`
    ]);
  }

  if (r.equilibrio !== null && vol < r.equilibrio) {
    alertas.push([
      'mal',
      `Con ${vol} unidades no se llega al equilibrio (${r.equilibrio}). Faltan ${r.equilibrio - vol}.`
    ]);
  }

  if (
    r.equilibrio !== null &&
    vol >= r.equilibrio &&
    vol > 0
  ) {
    alertas.push([
      'bien',
      `Se cubre el equilibrio con el ${Math.round(r.equilibrio / vol * 100)}% del volumen.`
    ]);
  }

  if (
    usandoManual &&
    r.sugerido > 0 &&
    r.precio < r.sugerido
  ) {
    const margenReal =
      r.ingreso > 0
        ? Math.round(r.ganancia / r.ingreso * 100)
        : 0;

    alertas.push([
      'aviso',
      `Tu precio manual está ${Math.round((1 - r.precio / r.sugerido) * 100)}% por debajo del sugerido: el margen real baja de ${$('margen').value}% a ${margenReal}%.`
    ]);
  }

  if (
    r.costoTotal > 0 &&
    (r.variable * vol) / r.costoTotal > 0.7
  ) {
    alertas.push([
      'aviso',
      'Más del 70% del costo es variable (materiales): conviene negociar con proveedores.'
    ]);
  }

  if (!alertas.length) {
    alertas.push([
      'bien',
      'Sin alertas con estos valores.'
    ]);
  }

  $('alertas').innerHTML = alertas
    .map(([clase, texto]) =>
      `<li class="${clase}">${texto}</li>`
    )
    .join('');

  // ========================================
  // ANÁLISIS DE SENSIBILIDAD
  // ========================================

  let tabla =
    '<tr>' +
    '<th>Volumen</th>' +
    '<th>Ingreso</th>' +
    '<th>Costo total</th>' +
    '<th>Ganancia</th>' +
    '</tr>';

  [0.8, 1, 1.2].forEach(multiplicador => {
    const volumen = Math.round(vol * multiplicador);
    const resultado = modelo(volumen, r.precio);

    const etiqueta =
      multiplicador === 1
        ? 'actual'
        : (multiplicador < 1 ? '-20%' : '+20%');

    tabla += `
      <tr>
        <td>${volumen} (${etiqueta})</td>
        <td>${pesos(resultado.ingreso)}</td>
        <td>${pesos(resultado.costoTotal)}</td>
        <td class="${resultado.ganancia < 0 ? 'mal' : 'bien'}">
          ${pesos(resultado.ganancia)}
        </td>
      </tr>
    `;
  });

  $('sens').innerHTML = tabla;
}

// ========================================
// EVENTOS DE LA INTERFAZ
// ========================================

$('cliente').addEventListener('change', cargarProductos);

$('producto').addEventListener('change', cargarProducto);

$('periodo').addEventListener('change', () => {
  ponerVolumen();
  calcular();
});

[
  'volumen',
  'margen',
  'precioManual',
  'varVar',
  'varFijos'
].forEach(id => {
  $(id).addEventListener('input', calcular);
});

// ========================================
// INICIO DE LA APLICACIÓN
// ========================================

cargarProductos();

