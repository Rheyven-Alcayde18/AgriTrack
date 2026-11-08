<?php
// Shared crop records, used by crop-monitoring.php, harvest-management.php AND dashboard.php.
// Edit a crop here and every page updates. Replace with Firestore/database records later.
//
// You only enter the facts: planting date, harvest date, moisture, area, yield and price.
// Growth progress, growth stage and status are CALCULATED from the dates below,
// so the pages can never disagree with each other.
//
// yield_t = expected yield in tons, price = expected price per kg (PHP)

$stages = ['Seedling', 'Vegetative', 'Flowering', 'Fruiting', 'Harvest ready'];

$crops = [
  [
    'id' => 'rice', 'name' => 'Rice', 'field' => 'Field A', 'area' => 5.0,
    'planted' => '2026-07-27', 'harvest' => '2026-11-24',
    'moisture' => 82,
    'action' => 'Inspect the rice field on Friday and keep the water level steady.',
    'yield_t' => 4.0, 'price' => 20,
    'prep' => ['Reserve harvester', 'Arrange drying space', 'Confirm buyer'],
  ],
  [
    'id' => 'corn', 'name' => 'Corn', 'field' => 'Field B', 'area' => 3.5,
    'planted' => '2026-09-20', 'harvest' => '2027-01-08',
    'moisture' => 61,
    'action' => 'Apply fertilizer tomorrow.',
    'yield_t' => 2.4, 'price' => 14,
    'prep' => ['Reserve sheller', 'Prepare sacks', 'Confirm buyer'],
  ],
  [
    'id' => 'tomato', 'name' => 'Tomato', 'field' => 'Field C', 'area' => 2.0,
    'planted' => '2026-08-30', 'harvest' => '2026-11-12',
    'moisture' => 54,
    'action' => 'Book transport and prepare crates for picking.',
    'yield_t' => 1.2, 'price' => 40,
    'prep' => ['Book transport', 'Hire pickers', 'Prepare crates'],
  ],
  [
    'id' => 'eggplant', 'name' => 'Eggplant', 'field' => 'Field D', 'area' => 2.0,
    'planted' => '2026-10-25', 'harvest' => '2027-02-10',
    'moisture' => 28,
    'action' => 'Water today. Soil moisture is below the healthy range.',
    'yield_t' => 0.8, 'price' => 45,
    'prep' => ['Plan picking crew', 'Prepare crates', 'Confirm buyer'],
  ],
];

// Work out progress, stage and status from the dates
$__today = new DateTime('today');
foreach ($crops as &$__c) {
  $__start = new DateTime($__c['planted']);
  $__end   = new DateTime($__c['harvest']);
  $__total = max(1, (int)$__start->diff($__end)->format('%a'));
  $__done  = (int)$__start->diff($__today)->format('%r%a');
  $__c['progress'] = (int)max(0, min(100, round($__done / $__total * 100)));

  $__p = $__done / $__total * 100;
  if     ($__p < 15) $__c['stage'] = 'Seedling';
  elseif ($__p < 45) $__c['stage'] = 'Vegetative';
  elseif ($__p < 70) $__c['stage'] = 'Flowering';
  elseif ($__p < 90) $__c['stage'] = 'Fruiting';
  else               $__c['stage'] = 'Harvest ready';

  $__left = (int)$__today->diff($__end)->format('%r%a');
  if ($__c['moisture'] < 35)  { $__c['status'] = 'warn'; $__c['label'] = 'Needs water'; }
  elseif ($__left <= 14)      { $__c['status'] = 'soon'; $__c['label'] = 'Harvest soon'; }
  else                        { $__c['status'] = 'ok';   $__c['label'] = 'On track'; }
}
unset($__c);

// Completed harvests from earlier cycles (sample), newest first
$pastHarvests = [
  ['date' => '2026-08-02', 'crop' => 'Eggplant', 'field' => 'Field D', 'yield_t' => 0.7, 'price' => 42],
  ['date' => '2026-06-22', 'crop' => 'Tomato',   'field' => 'Field C', 'yield_t' => 0.9, 'price' => 38],
  ['date' => '2026-06-18', 'crop' => 'Rice',     'field' => 'Field A', 'yield_t' => 3.6, 'price' => 19],
];