// Datos iniciales de Mi Despensa: recetas rápidas, categorías y sugerencias.
// Las calorías son aproximadas por porción.

const CATEGORIAS = {
  carnes: { nombre: 'Carnes y embutidos', tipo: 'comida' },
  verduras: { nombre: 'Frutas y verduras', tipo: 'comida' },
  lacteos: { nombre: 'Lácteos', tipo: 'comida' },
  panaderia: { nombre: 'Tortillas y pan', tipo: 'comida' },
  alacena: { nombre: 'Alacena', tipo: 'comida' },
  congelados: { nombre: 'Congelados', tipo: 'comida' },
  salsas: { nombre: 'Salsas y condimentos', tipo: 'comida' },
  aseoPersonal: { nombre: 'Aseo personal', tipo: 'aseo' },
  limpieza: { nombre: 'Limpieza del hogar', tipo: 'aseo' },
  otros: { nombre: 'Otros', tipo: 'otro' },
};

const RECETAS_BASE = [
  {
    id: 'tacos-carne',
    nombre: 'Tacos de carne molida',
    minutos: 12,
    kcal: 550,
    ingredientes: [
      { nombre: 'Carne molida', cat: 'carnes' },
      { nombre: 'Tortillas de maíz', cat: 'panaderia' },
      { nombre: 'Cebolla', cat: 'verduras' },
      { nombre: 'Cilantro', cat: 'verduras' },
      { nombre: 'Limón', cat: 'verduras' },
      { nombre: 'Salsa verde', cat: 'salsas' },
    ],
    pasos: [
      'Dora la carne con cebolla picada y sal (8 min).',
      'Calienta las tortillas.',
      'Sirve con cilantro, limón y salsa.',
    ],
  },
  {
    id: 'bistec-papas',
    nombre: 'Bistec con papas',
    minutos: 12,
    kcal: 600,
    ingredientes: [
      { nombre: 'Bistec delgado', cat: 'carnes' },
      { nombre: 'Papas', cat: 'verduras' },
      { nombre: 'Cebolla', cat: 'verduras' },
      { nombre: 'Aceite', cat: 'alacena' },
    ],
    pasos: [
      'Corta la papa en cubitos y métela 5 min al microondas.',
      'Dórala en sartén con aceite y cebolla.',
      'Cocina el bistec 2–3 min por lado.',
    ],
  },
  {
    id: 'bowl-atun',
    nombre: 'Bowl de atún',
    minutos: 5,
    kcal: 500,
    ingredientes: [
      { nombre: 'Arroz de microondas', cat: 'alacena' },
      { nombre: 'Atún en lata', cat: 'alacena' },
      { nombre: 'Aguacate', cat: 'verduras' },
      { nombre: 'Pepino', cat: 'verduras' },
      { nombre: 'Mayonesa', cat: 'salsas' },
      { nombre: 'Chipotle', cat: 'salsas' },
    ],
    pasos: [
      'Calienta el arroz 90 s.',
      'Mezcla mayonesa con chipotle.',
      'Arma el bowl con atún, aguacate y pepino.',
    ],
  },
  {
    id: 'quesadillas-chorizo',
    nombre: 'Quesadillas de chorizo con papa',
    minutos: 12,
    kcal: 650,
    tags: ['cerdo'],
    ingredientes: [
      { nombre: 'Chorizo', cat: 'carnes' },
      { nombre: 'Papas', cat: 'verduras' },
      { nombre: 'Tortillas de maíz', cat: 'panaderia' },
      { nombre: 'Queso Oaxaca', cat: 'lacteos' },
    ],
    pasos: [
      'Papa en cubitos 4 min al microondas.',
      'Fríe el chorizo y mezcla con la papa.',
      'Rellena las tortillas con queso y el guiso, y dóralas.',
    ],
  },
  {
    id: 'arroz-carne',
    nombre: 'Arroz con carne molida y verduras',
    minutos: 10,
    kcal: 600,
    ingredientes: [
      { nombre: 'Arroz de microondas', cat: 'alacena' },
      { nombre: 'Carne molida', cat: 'carnes' },
      { nombre: 'Verduras mixtas congeladas', cat: 'congelados' },
      { nombre: 'Salsa de soya', cat: 'salsas' },
    ],
    pasos: [
      'Saltea las verduras congeladas 4 min.',
      'Agrega la carne (ya cocida o cruda) y el arroz.',
      'Sazona con soya y mezcla 3 min.',
    ],
  },
  {
    id: 'chuleta-ensalada',
    nombre: 'Chuleta de cerdo con ensalada',
    minutos: 12,
    kcal: 500,
    tags: ['cerdo'],
    ingredientes: [
      { nombre: 'Chuletas delgadas', cat: 'carnes' },
      { nombre: 'Lechuga', cat: 'verduras' },
      { nombre: 'Jitomate', cat: 'verduras' },
      { nombre: 'Pepino', cat: 'verduras' },
      { nombre: 'Limón', cat: 'verduras' },
      { nombre: 'Aceite', cat: 'alacena' },
    ],
    pasos: [
      'Sazona la chuleta y cocínala 4 min por lado.',
      'Mientras, corta la ensalada y alíñala con limón y aceite.',
    ],
  },
  {
    id: 'tostadas-atun',
    nombre: 'Tostadas de atún',
    minutos: 5,
    kcal: 400,
    ingredientes: [
      { nombre: 'Tostadas', cat: 'panaderia' },
      { nombre: 'Atún en lata', cat: 'alacena' },
      { nombre: 'Jitomate', cat: 'verduras' },
      { nombre: 'Cebolla', cat: 'verduras' },
      { nombre: 'Aguacate', cat: 'verduras' },
      { nombre: 'Limón', cat: 'verduras' },
    ],
    pasos: [
      'Mezcla el atún con jitomate, cebolla y limón.',
      'Sirve sobre tostadas con aguacate.',
    ],
  },
  {
    id: 'tacos-panela',
    nombre: 'Tacos de queso panela con champiñones',
    minutos: 10,
    kcal: 450,
    ingredientes: [
      { nombre: 'Queso panela', cat: 'lacteos' },
      { nombre: 'Champiñones', cat: 'verduras' },
      { nombre: 'Cebolla', cat: 'verduras' },
      { nombre: 'Tortillas de maíz', cat: 'panaderia' },
      { nombre: 'Salsa verde', cat: 'salsas' },
    ],
    pasos: [
      'Asa el panela en rebanadas 2 min por lado.',
      'Saltea champiñones con cebolla.',
      'Arma los tacos con salsa.',
    ],
  },
  {
    id: 'sandwich-jamon',
    nombre: 'Sándwich caliente de jamón y queso',
    minutos: 7,
    kcal: 450,
    tags: ['cerdo'],
    ingredientes: [
      { nombre: 'Pan de caja', cat: 'panaderia' },
      { nombre: 'Jamón', cat: 'carnes' },
      { nombre: 'Queso amarillo', cat: 'lacteos' },
      { nombre: 'Jitomate', cat: 'verduras' },
    ],
    pasos: ['Arma el sándwich y dóralo en sartén con tapa 3 min por lado.'],
  },
  {
    id: 'picadillo',
    nombre: 'Picadillo con arroz',
    minutos: 12,
    kcal: 600,
    ingredientes: [
      { nombre: 'Carne molida', cat: 'carnes' },
      { nombre: 'Papas', cat: 'verduras' },
      { nombre: 'Zanahorias', cat: 'verduras' },
      { nombre: 'Jitomate', cat: 'verduras' },
      { nombre: 'Arroz de microondas', cat: 'alacena' },
    ],
    pasos: [
      'Papa y zanahoria en cubitos, 5 min al microondas.',
      'Cocina la carne con jitomate picado y agrega las verduras.',
      'Sirve con arroz.',
    ],
  },
  {
    id: 'burrito-carne',
    nombre: 'Burrito de carne molida',
    minutos: 10,
    kcal: 700,
    ingredientes: [
      { nombre: 'Tortillas de harina', cat: 'panaderia' },
      { nombre: 'Carne molida', cat: 'carnes' },
      { nombre: 'Arroz de microondas', cat: 'alacena' },
      { nombre: 'Queso Oaxaca', cat: 'lacteos' },
      { nombre: 'Chipotle', cat: 'salsas' },
    ],
    pasos: [
      'Calienta carne y arroz.',
      'Rellena la tortilla con queso y chipotle, enrolla y dora 1 min.',
    ],
  },
  {
    id: 'pasta-tomate',
    nombre: 'Pasta con salsa de tomate',
    minutos: 15,
    kcal: 600,
    ingredientes: [
      { nombre: 'Pasta', cat: 'alacena' },
      { nombre: 'Salsa de tomate', cat: 'salsas' },
      { nombre: 'Queso parmesano', cat: 'lacteos' },
    ],
    pasos: ['Cuece la pasta 10 min.', 'Mezcla con la salsa caliente y queso.'],
  },
];

const ASEO_SUGERIDOS = [
  { nombre: 'Papel higiénico', cat: 'aseoPersonal' },
  { nombre: 'Jabón de baño', cat: 'aseoPersonal' },
  { nombre: 'Shampoo', cat: 'aseoPersonal' },
  { nombre: 'Pasta dental', cat: 'aseoPersonal' },
  { nombre: 'Desodorante', cat: 'aseoPersonal' },
  { nombre: 'Rastrillos', cat: 'aseoPersonal' },
  { nombre: 'Detergente para ropa', cat: 'limpieza' },
  { nombre: 'Lavatrastes', cat: 'limpieza' },
  { nombre: 'Cloro', cat: 'limpieza' },
  { nombre: 'Limpiador multiusos', cat: 'limpieza' },
  { nombre: 'Bolsas de basura', cat: 'limpieza' },
  { nombre: 'Esponjas', cat: 'limpieza' },
  { nombre: 'Suavizante', cat: 'limpieza' },
  { nombre: 'Servilletas', cat: 'limpieza' },
];

const SNACKS = [
  { nombre: 'Manzana', kcal: 95 },
  { nombre: 'Plátano', kcal: 105 },
  { nombre: 'Yogur', kcal: 150 },
  { nombre: 'Pan dulce', kcal: 350 },
  { nombre: 'Café con leche', kcal: 120 },
  { nombre: 'Refresco (lata)', kcal: 140 },
  { nombre: 'Galletas (4)', kcal: 200 },
  { nombre: 'Tortilla', kcal: 70 },
  { nombre: 'Cerveza', kcal: 150 },
  { nombre: 'Papas fritas (bolsa)', kcal: 230 },
];

const EXCLUSIONES_INICIALES = ['pollo', 'huevo', 'camarón', 'frijol'];

const DIAS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
