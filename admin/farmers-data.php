
<?php
// Sample farmer records for admin/manage-farmers.php.
// All names, emails and numbers are made up. Replace this file with your Firestore/database
// records when they are connected (the page only needs the same fields).
//
// status: active | inactive      crop status: ok | soon | warn | done
// markets: ids from user/markets-data.php

$__d = function ($days) { return date('Y-m-d', strtotime(($days >= 0 ? '+' : '') . $days . ' days')); };

$farmers = [
  [
    'id' => 'f001', 'name' => 'Maria Santos', 'email' => 'maria.santos@example.com', 'phone' => '0917 123 4501',
    'municipality' => 'Bay', 'barangay' => 'Santo Domingo', 'farm_size' => 4.5,
    'joined' => $__d(-210), 'last_active' => $__d(0), 'status' => 'active',
    'crops' => [
      ['name' => 'Tomato',   'field' => 'Field A', 'area' => 2.0, 'planted' => $__d(-70), 'harvest' => $__d(4),   'stage' => 'Harvest ready', 'status' => 'soon'],
      ['name' => 'Eggplant', 'field' => 'Field B', 'area' => 2.5, 'planted' => $__d(-14), 'harvest' => $__d(95),  'stage' => 'Seedling',      'status' => 'warn'],
    ],
    'activities' => [
      ['date' => $__d(0),   'text' => 'Logged soil moisture reading for Field B'],
      ['date' => $__d(-2),  'text' => 'Marked Field B watering as done'],
      ['date' => $__d(-5),  'text' => 'Compared market prices for tomato'],
      ['date' => $__d(-14), 'text' => 'Registered eggplant crop in Field B'],
    ],
    'harvests' => [
      ['date' => $__d(-75),  'crop' => 'Tomato',   'yield_t' => 0.9, 'price' => 38],
      ['date' => $__d(-130), 'crop' => 'Eggplant', 'yield_t' => 0.7, 'price' => 42],
    ],
    'markets' => ['bay-pm', 'calamba-hub'],
  ],
  [
    'id' => 'f002', 'name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@example.com', 'phone' => '0918 555 2210',
    'municipality' => 'Calauan', 'barangay' => 'Dayap', 'farm_size' => 8.0,
    'joined' => $__d(-320), 'last_active' => $__d(-1), 'status' => 'active',
    'crops' => [
      ['name' => 'Rice', 'field' => 'Field 1', 'area' => 5.0, 'planted' => $__d(-104), 'harvest' => $__d(16), 'stage' => 'Fruiting',   'status' => 'ok'],
      ['name' => 'Corn', 'field' => 'Field 2', 'area' => 3.0, 'planted' => $__d(-49),  'harvest' => $__d(61), 'stage' => 'Vegetative', 'status' => 'ok'],
    ],
    'activities' => [
      ['date' => $__d(-1),  'text' => 'Viewed harvest plan for rice'],
      ['date' => $__d(-3),  'text' => 'Applied fertilizer to Field 2'],
      ['date' => $__d(-9),  'text' => 'Saved Pila Rice Buying Station'],
      ['date' => $__d(-30), 'text' => 'Updated rice harvest date'],
    ],
    'harvests' => [
      ['date' => $__d(-140), 'crop' => 'Rice', 'yield_t' => 3.6, 'price' => 19],
      ['date' => $__d(-260), 'crop' => 'Corn', 'yield_t' => 2.1, 'price' => 13],
    ],
    'markets' => ['calauan-coop', 'pila-rice', 'calauan-pm'],
  ],
  [
    'id' => 'f003', 'name' => 'Ana Reyes', 'email' => 'ana.reyes@example.com', 'phone' => '0920 777 3382',
    'municipality' => 'Los Baños', 'barangay' => 'Bayog', 'farm_size' => 2.5,
    'joined' => $__d(-95), 'last_active' => $__d(-2), 'status' => 'active',
    'crops' => [
      ['name' => 'Eggplant', 'field' => 'Plot 1', 'area' => 1.5, 'planted' => $__d(-40), 'harvest' => $__d(55), 'stage' => 'Vegetative', 'status' => 'ok'],
      ['name' => 'Tomato',   'field' => 'Plot 2', 'area' => 1.0, 'planted' => $__d(-62), 'harvest' => $__d(9),  'stage' => 'Fruiting',   'status' => 'soon'],
    ],
    'activities' => [
      ['date' => $__d(-2),  'text' => 'Checked weather forecast'],
      ['date' => $__d(-6),  'text' => 'Marked tomato prep tasks as done'],
      ['date' => $__d(-40), 'text' => 'Registered eggplant crop in Plot 1'],
    ],
    'harvests' => [
      ['date' => $__d(-50), 'crop' => 'Tomato', 'yield_t' => 0.6, 'price' => 40],
    ],
    'markets' => ['lb-pm'],
  ],
  [
    'id' => 'f004', 'name' => 'Pedro Garcia', 'email' => 'pedro.garcia@example.com', 'phone' => '0919 404 6615',
    'municipality' => 'Pila', 'barangay' => 'Labuin', 'farm_size' => 6.0,
    'joined' => $__d(-180), 'last_active' => $__d(-6), 'status' => 'active',
    'crops' => [
      ['name' => 'Rice', 'field' => 'North field', 'area' => 6.0, 'planted' => $__d(-95), 'harvest' => $__d(27), 'stage' => 'Fruiting', 'status' => 'ok'],
    ],
    'activities' => [
      ['date' => $__d(-6),  'text' => 'Viewed Pila Rice Buying Station'],
      ['date' => $__d(-12), 'text' => 'Updated rice progress'],
    ],
    'harvests' => [
      ['date' => $__d(-120), 'crop' => 'Rice', 'yield_t' => 4.4, 'price' => 20],
      ['date' => $__d(-240), 'crop' => 'Rice', 'yield_t' => 4.1, 'price' => 19],
    ],
    'markets' => ['pila-rice', 'stacruz-hub'],
  ],
  [
    'id' => 'f005', 'name' => 'Luisa Mendoza', 'email' => 'luisa.mendoza@example.com', 'phone' => '0921 880 1927',
    'municipality' => 'San Pablo City', 'barangay' => 'San Gregorio', 'farm_size' => 3.0,
    'joined' => $__d(-60), 'last_active' => $__d(-1), 'status' => 'active',
    'crops' => [
      ['name' => 'Corn',     'field' => 'Lot A', 'area' => 2.0, 'planted' => $__d(-30), 'harvest' => $__d(75), 'stage' => 'Vegetative', 'status' => 'ok'],
      ['name' => 'Eggplant', 'field' => 'Lot B', 'area' => 1.0, 'planted' => $__d(-8),  'harvest' => $__d(100), 'stage' => 'Seedling',  'status' => 'warn'],
    ],
    'activities' => [
      ['date' => $__d(-1),  'text' => 'Saved San Pablo City Public Market'],
      ['date' => $__d(-4),  'text' => 'Watered Lot B'],
      ['date' => $__d(-8),  'text' => 'Registered eggplant crop in Lot B'],
    ],
    'harvests' => [],
    'markets' => ['sanpablo-pm'],
  ],
  [
    'id' => 'f006', 'name' => 'Roberto Aquino', 'email' => 'roberto.aquino@example.com', 'phone' => '0915 220 7743',
    'municipality' => 'Bay', 'barangay' => 'Maitim', 'farm_size' => 5.5,
    'joined' => $__d(-270), 'last_active' => $__d(0), 'status' => 'active',
    'crops' => [
      ['name' => 'Corn',   'field' => 'Field C', 'area' => 3.5, 'planted' => $__d(-80), 'harvest' => $__d(28), 'stage' => 'Flowering', 'status' => 'ok'],
      ['name' => 'Tomato', 'field' => 'Field D', 'area' => 2.0, 'planted' => $__d(-66), 'harvest' => $__d(-1), 'stage' => 'Harvest ready', 'status' => 'soon'],
    ],
    'activities' => [
      ['date' => $__d(0),   'text' => 'Opened recommendations'],
      ['date' => $__d(-1),  'text' => 'Compared market prices for tomato'],
      ['date' => $__d(-7),  'text' => 'Applied fertilizer to Field C'],
    ],
    'harvests' => [
      ['date' => $__d(-90),  'crop' => 'Tomato', 'yield_t' => 1.4, 'price' => 41],
      ['date' => $__d(-200), 'crop' => 'Corn',   'yield_t' => 2.8, 'price' => 14],
      ['date' => $__d(-290), 'crop' => 'Tomato', 'yield_t' => 1.1, 'price' => 37],
    ],
    'markets' => ['bay-pm', 'lb-pm', 'calamba-hub'],
  ],
  [
    'id' => 'f007', 'name' => 'Carmen Villanueva', 'email' => 'carmen.villanueva@example.com', 'phone' => '0922 331 5098',
    'municipality' => 'Calauan', 'barangay' => 'Lamot 1', 'farm_size' => 1.5,
    'joined' => $__d(-400), 'last_active' => $__d(-75), 'status' => 'inactive',
    'crops' => [
      ['name' => 'Eggplant', 'field' => 'Back lot', 'area' => 1.5, 'planted' => $__d(-150), 'harvest' => $__d(-60), 'stage' => 'Harvest ready', 'status' => 'done'],
    ],
    'activities' => [
      ['date' => $__d(-75),  'text' => 'Marked eggplant as harvested'],
      ['date' => $__d(-110), 'text' => 'Viewed weather forecast'],
    ],
    'harvests' => [
      ['date' => $__d(-62),  'crop' => 'Eggplant', 'yield_t' => 0.8, 'price' => 43],
      ['date' => $__d(-230), 'crop' => 'Eggplant', 'yield_t' => 0.6, 'price' => 41],
    ],
    'markets' => ['calauan-pm'],
  ],
  [
    'id' => 'f008', 'name' => 'Eduardo Ramos', 'email' => 'eduardo.ramos@example.com', 'phone' => '0916 909 4412',
    'municipality' => 'Santa Cruz', 'barangay' => 'Patimbao', 'farm_size' => 7.0,
    'joined' => $__d(-330), 'last_active' => $__d(-120), 'status' => 'inactive',
    'crops' => [
      ['name' => 'Rice', 'field' => 'Main field', 'area' => 7.0, 'planted' => $__d(-220), 'harvest' => $__d(-110), 'stage' => 'Harvest ready', 'status' => 'done'],
    ],
    'activities' => [
      ['date' => $__d(-120), 'text' => 'Marked rice as harvested'],
    ],
    'harvests' => [
      ['date' => $__d(-112), 'crop' => 'Rice', 'yield_t' => 5.2, 'price' => 20],
    ],
    'markets' => ['stacruz-hub'],
  ],
];