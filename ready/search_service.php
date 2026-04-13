<?php
session_start();
require 'db.php';

header('Content-Type: text/html; charset=utf-8');

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
$bestServices = [];
$debug_info = [];
function splitWordIntoSyllables($word) {
    $cleanWord = preg_replace('/[[:punct:]]/', '', $word);
    if (empty($cleanWord)) {
        return [$word];
    }
    
    $vowels = ['а', 'е', 'ё', 'и', 'о', 'у', 'ы', 'э', 'ю', 'я'];
    
    $syllables = [];
    $currentSyllable = '';
    $length = mb_strlen($cleanWord);
    
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($cleanWord, $i, 1);
        $currentSyllable .= $char;
        
        if (in_array($char, $vowels)) {
            $syllables[] = $currentSyllable;
            $currentSyllable = '';
        }
    }
    
    if ($currentSyllable !== '') {
        if (!empty($syllables)) {
            $syllables[count($syllables) - 1] .= $currentSyllable;
        } else {
            $syllables[] = $currentSyllable;
        }
    }
    
    return $syllables;
}

function splitSentenceIntoSyllables($sentence) {
    $words = preg_split('/\s+/u', mb_strtolower(trim($sentence)));
    $allSyllables = [];
    
    foreach ($words as $word) {
        if (!empty($word)) {
            // Каждое слово разбиваем на слоги
            $wordSyllables = splitWordIntoSyllables($word);
            $allSyllables = array_merge($allSyllables, $wordSyllables);
        }
    }
    
    return $allSyllables;
}

function removeDuplicateSyllables($syllables) {
    return array_values(array_unique($syllables));
}

function generateUniqueCombinations($items) {
    $combinations = [];
    $count = count($items);
    
    for ($i = 1; $i <= $count; $i++) {
        generateCombinationsRecursive($combinations, [], 0, $count, $i);
    }
    
    sort($combinations);
    $combinations = array_unique($combinations, SORT_REGULAR);
    
    $stringCombinations = [];
    foreach ($combinations as $combo) {
        $stringCombinations[] = implode(',', $combo);
    }
    sort($stringCombinations);
    
    $sortedCombinations = [];
    foreach ($stringCombinations as $stringCombo) {
        $sortedCombinations[] = explode(',', $stringCombo);
    }
    
    return $sortedCombinations;
}

function generateCombinationsRecursive(&$combinations, $current, $start, $n, $k) {
    if (count($current) == $k) {
        $combinations[] = $current;
        return;
    }
    
    for ($i = $start; $i < $n; $i++) {
        $current[] = $i;
        generateCombinationsRecursive($combinations, $current, $i + 1, $n, $k);
        array_pop($current);
    }
}

function getServicesCountBySyllables($pdo, $syllables) {
    if (empty($syllables)) {
        return 0;
    }
    
    $conditions = [];
    $params = [];
    
    foreach ($syllables as $index => $syllable) {
        $paramName = ":syllable_$index";
        $conditions[] = "LOWER(title) LIKE $paramName";
        $params[$paramName] = '%' . mb_strtolower($syllable) . '%';
    }
    
    $sql = "SELECT COUNT(*) as count FROM services WHERE " . implode(' AND ', $conditions);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? (int)$result['count'] : 0;
}

function getServicesBySyllables($pdo, $syllables) {
    if (empty($syllables)) {
        return [];
    }
    
    $conditions = [];
    $params = [];
    
    foreach ($syllables as $index => $syllable) {
        $paramName = ":syllable_$index";
        $conditions[] = "LOWER(title) LIKE $paramName";
        $params[$paramName] = '%' . mb_strtolower($syllable) . '%';
    }
    
    $sql = "SELECT * FROM services WHERE " . implode(' AND ', $conditions) . " ORDER BY price ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$allServices = $pdo->query("SELECT * FROM services ORDER BY id")->fetchAll();

if (!empty($search_query)) {
    $syllables = splitSentenceIntoSyllables($search_query);
    $uniqueSyllables = removeDuplicateSyllables($syllables);
    
    $debug_info['original_query'] = $search_query;
    $debug_info['syllables'] = $syllables;
    $debug_info['unique_syllables'] = $uniqueSyllables;
    
    $combinations = generateUniqueCombinations($uniqueSyllables);
    $debug_info['total_combinations'] = count($combinations);
    
    $results = [];
    foreach ($combinations as $combo) {
        $comboSyllables = [];
        foreach ($combo as $index) {
            $comboSyllables[] = $uniqueSyllables[$index];
        }
        
        $count = getServicesCountBySyllables($pdo, $comboSyllables);
        
        $results[] = [
            'syllables' => $comboSyllables,
            'syllables_text' => implode(' + ', $comboSyllables),
            'count' => $count,
            'combo_index' => $combo
        ];
    }
    
    $debug_info['all_results'] = $results;
    
    $bestServices = [];
    $bestCombination = null;
    $maxSyllables = 0;
    $minCount = PHP_INT_MAX;
    
    foreach ($results as $r) {
        if ($r['count'] > 0) {
            if ($r['count'] < $minCount || ($r['count'] == $minCount && count($r['syllables']) > $maxSyllables)) {
                $minCount = $r['count'];
                $maxSyllables = count($r['syllables']);
                $bestCombination = $r;
            }
        }
    }
    
    if ($bestCombination) {
        $bestSyllables = $bestCombination['syllables'];
        $bestServices = getServicesBySyllables($pdo, $bestSyllables);
        $debug_info['best_combination'] = $bestCombination;
        $debug_info['best_services'] = array_column($bestServices, 'title');
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск услуг | BarberShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .container {
            flex: 1;
        }
        
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body class="bg-success bg-opacity-10">

<nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php">✂️ BARBERSHOP</a>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/admin_panel.php" class="btn btn-outline-danger btn-sm">Админка</a>
                <?php endif; ?>
                <a href="profile.php" class="btn btn-outline-primary btn-sm">Профиль</a>
                <a href="logout.php" class="btn btn-dark btn-sm">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-success btn-sm">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h3 class="text-success text-center mb-4">
                        <i class="fas fa-search"></i> Поиск услуг
                    </h3>
                    <form method="GET" action="search_service.php" class="d-flex gap-2">
                        <input type="text" name="q" class="form-control form-control-lg rounded-pill" 
                               placeholder="Например: стрижка, окрашивание, борода..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                        <button type="submit" class="btn btn-success rounded-pill px-4">Искать</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($search_query)): ?>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-success text-white rounded-top-4">
                        <h5 class="mb-0"><i class="fas fa-cut"></i> Результаты поиска: "<?= htmlspecialchars($search_query) ?>"</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bestServices)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                По запросу "<strong><?= htmlspecialchars($search_query) ?></strong>" ничего не найдено.
                            </div>
                        <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($bestServices as $s): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 border-0 shadow-sm rounded-4">
                                        <img src="<?= h($s['image_url']) ?>" class="card-img-top rounded-top-4" style="height: 180px; object-fit: cover;" alt="<?= h($s['title']) ?>">
                                        <div class="card-body">
                                            <h5 class="card-title fw-bold"><?= h($s['title']) ?></h5>
                                            <p class="card-text text-secondary"><?= h($s['descriptions']) ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-success fw-bold fs-5"><?= number_format($s['price'], 0, '', ' ') ?> ₽</span>
                                                <span class="badge bg-secondary"><?= $s['duration'] ?> мин</span>
                                            </div>
                                            <a href="booking.php?service_id=<?= $s['id'] ?>" class="btn btn-outline-success rounded-pill w-100 mt-3">
                                                Записаться
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-success text-white rounded-top-4">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Все услуги</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php foreach ($allServices as $s): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm rounded-4">
                                    <img src="<?= h($s['image_url']) ?>" class="card-img-top rounded-top-4" style="height: 180px; object-fit: cover;" alt="<?= h($s['title']) ?>">
                                    <div class="card-body">
                                        <h5><?= h($s['title']) ?></h5>
                                        <p class="text-secondary small"><?= h($s['descriptions']) ?></p>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-success fw-bold"><?= number_format($s['price'], 0, '', ' ') ?> ₽</span>
                                            <span class="badge bg-secondary"><?= $s['duration'] ?> мин</span>
                                        </div>
                                        <a href="booking.php?service_id=<?= $s['id'] ?>" class="btn btn-outline-success rounded-pill w-100 mt-3">Записаться</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<footer class="bg-dark text-white-50 text-center py-3 mt-4">
    <div class="container">
        <p class="mb-0">© 2025 BARBERSHOP. Поиск услуг</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>