<?php
require 'config.php';

try {
    $stmt = $pdo->query("
        SELECT c.id, c.date_collecte, c.lieu, b.nom
        FROM collectes c
        LEFT JOIN benevoles b ON c.id_benevole = b.id
        ORDER BY c.date_collecte DESC
    ");

    $query = $pdo->prepare("SELECT nom FROM benevoles WHERE role = 'admin' LIMIT 1");
    $query->execute();

    $collectes = $stmt->fetchAll();
    $admin = $query->fetch(PDO::FETCH_ASSOC);
    $adminNom = $admin ? htmlspecialchars($admin['nom']) : 'Aucun administrateur trouvé';

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage();
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


try {
    $pdo = new PDO("mysql:host=localhost;dbname=collections", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Récupérer le total des déchets collectés
    $stmt = $pdo->query("SELECT SUM(quantite_kg) AS total_dechets FROM dechets_collectes");
    $total_dechets = $stmt->fetch(PDO::FETCH_ASSOC)['total_dechets'];

    // Récupérer les types de déchets avec leur quantité
    $stmt = $pdo->query("SELECT type_dechet, SUM(quantite_kg) AS total FROM dechets_collectes GROUP BY type_dechet");
    $types_dechets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les quantités collectées par mois
    $stmt = $pdo->query("SELECT DATE_FORMAT(date_collecte, '%Y-%m') AS mois, SUM(quantite_kg) AS total FROM collectes 
                         JOIN dechets_collectes ON collectes.id = dechets_collectes.id_collecte
                         GROUP BY mois ORDER BY mois");
    $dechets_par_mois = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Initialisation des filtres
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$type_dechet = $_GET['type_dechet'] ?? '';

// Construction de la requête SQL avec filtres dynamiques
$whereClause = " WHERE 1=1";
$params = [];

if (!empty($date_debut) && !empty($date_fin)) {
    $whereClause .= " AND date_collecte BETWEEN ? AND ?";
    $params[] = $date_debut;
    $params[] = $date_fin;
}

if (!empty($type_dechet)) {
    $whereClause .= " AND type_dechet = ?";
    $params[] = $type_dechet;
}

// Récupérer les données filtrées pour le graphique en camembert
$stmt = $pdo->prepare("SELECT type_dechet, SUM(quantite_kg) AS total 
                       FROM dechets_collectes 
                       JOIN collectes ON collectes.id = dechets_collectes.id_collecte
                       $whereClause GROUP BY type_dechet");
$stmt->execute($params);
$types_dechets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les données filtrées pour l'évolution des déchets par mois
$stmt = $pdo->prepare("SELECT DATE_FORMAT(date_collecte, '%Y-%m') AS mois, SUM(quantite_kg) AS total 
                       FROM collectes 
                       JOIN dechets_collectes ON collectes.id = dechets_collectes.id_collecte
                       $whereClause GROUP BY mois ORDER BY mois");
$stmt->execute($params);
$dechets_par_mois = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Collectes</title>
    <head>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&family=Lora:wght@400;700&family=Montserrat:wght@300;400;700&family=Open+Sans:wght@300;400;700&family=Poppins:wght@300;400;700&family=Playfair+Display:wght@400;700&family=Raleway:wght@300;400;700&family=Nunito:wght@300;400;700&family=Merriweather:wght@300;400;700&family=Oswald:wght@300;400;700&display=swap" rel="stylesheet">
    </head>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-900">
<div class="flex h-screen">
    <!-- Barre de navigation -->
    <div class="bg-cyan-200 text-white w-64 p-6">
        <h2 class="text-2xl font-bold mb-6">Dashboard</h2>
            <li><a href="collection_list.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fas fa-tachometer-alt mr-3"></i> Tableau de bord</a></li>
            <li><a href="collection_add.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fas fa-plus-circle mr-3"></i> Ajouter une collecte</a></li>
            <li><a href="volunteer_list.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fa-solid fa-list mr-3"></i> Liste des bénévoles</a></li>
            <li><a href="user_add.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fas fa-user-plus mr-3"></i> Ajouter un bénévole</a></li>
            <li><a href="my_account.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fas fa-cogs mr-3"></i> Mon compte</a></li>
        <div class="mt-6">
            <button onclick="logout()" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg shadow-md">
                Déconnexion
            </button>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="flex-1 p-8 overflow-y-auto">
        <!-- Titre -->
        <h1 class="text-4xl font-bold text-blue-800 mb-6">Liste des Collectes de Déchets</h1>

        <!-- Message de notification (ex: succès de suppression ou ajout) -->
        <?php if (isset($_GET['message'])): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded-md mb-6">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Cartes d'informations -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Nombre total de collectes -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Total des Collectes</h3>
                <p class="text-3xl font-bold text-blue-600"><?= count($collectes) ?></p>
            </div>
            <!-- Dernière collecte -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Dernière Collecte</h3>
                <p class="text-lg text-gray-600"><?= htmlspecialchars($collectes[0]['lieu']) ?></p>
                <p class="text-lg text-gray-600"><?= date('d/m/Y', strtotime($collectes[0]['date_collecte'])) ?></p>
            </div>
            <!-- Bénévole Responsable -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Bénévole Admin</h3>
                <p class="text-lg text-gray-600"><?= $adminNom ?></p>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-center text-blue-900 mb-6">Tableau de Bord des Collectes</h1>

<!-- Formulaire de filtre -->
<form method="GET" class="mb-6 flex justify-center space-x-4">
    <input type="date" name="date_debut" class="border p-2 rounded" value="<?= htmlspecialchars($date_debut) ?>">
    <input type="date" name="date_fin" class="border p-2 rounded" value="<?= htmlspecialchars($date_fin) ?>">
    
    <select name="type_dechet" class="border p-2 rounded">
        <option value="">Tous les types</option>
        <?php
        $stmt = $pdo->query("SELECT DISTINCT type_dechet FROM dechets_collectes");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $selected = ($type_dechet == $row['type_dechet']) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($row['type_dechet']) . "' $selected>" . htmlspecialchars($row['type_dechet']) . "</option>";
        }
        ?>
    </select>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filtrer</button>
</form>

<!-- Conteneur des graphiques -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold text-center">Répartition des types de déchets</h2>
        <canvas id="dechetsChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold text-center">Évolution des déchets collectés</h2>
        <canvas id="evolutionChart"></canvas>
    </div>
</div>


        <!-- Tableau des collectes -->
        <div class="overflow-hidden rounded-lg shadow-lg bg-white">
            <table class="w-full table-auto border-collapse">
                <thead class="bg-blue-800 text-white">
                <tr>
                    <th class="py-3 px-4 text-left">Date</th>
                    <th class="py-3 px-4 text-left">Lieu</th>
                    <th class="py-3 px-4 text-left">Bénévole Responsable</th>
                    <th class="py-3 px-4 text-left">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-300">
                <?php foreach ($collectes as $collecte) : ?>
                    <tr class="hover:bg-gray-100 transition duration-200">
                        <td class="py-3 px-4"><?= date('d/m/Y', strtotime($collecte['date_collecte'])) ?></td>
                        <td class="py-3 px-4"><?= htmlspecialchars($collecte['lieu']) ?></td>
                        <td class="py-3 px-4">
                            <?= $collecte['nom'] ? htmlspecialchars($collecte['nom']) : 'Aucun bénévole' ?>
                        </td>
                        <td class="py-3 px-4 flex space-x-2">
                            <a href="collection_edit.php?id=<?= $collecte['id'] ?>" class="bg-cyan-200 hover:bg-cyan-600 text-white px-4 py-2 rounded-lg shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                                ✏️ Modifier
                            </a>
                            <a href="collection_delete.php?id=<?= $collecte['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow-lg focus:outline-none focus:ring-2 focus:ring-red-500 transition duration-200" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette collecte ?');">
                                🗑️ Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
// Graphique des types de déchets
        const labelsTypes = <?= json_encode(array_column($types_dechets, 'type_dechet')) ?>;
        const dataTypes = <?= json_encode(array_column($types_dechets, 'total')) ?>;

        new Chart(document.getElementById('dechetsChart'), {
            type: 'pie',
            data: {
                labels: labelsTypes,
                datasets: [{
                    label: 'Répartition des déchets',
                    data: dataTypes,
                    backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff'],
                }]
            }
        });

        // Graphique de l'évolution des déchets
        const labelsMois = <?= json_encode(array_column($dechets_par_mois, 'mois')) ?>;
        const dataMois = <?= json_encode(array_column($dechets_par_mois, 'total')) ?>;

        new Chart(document.getElementById('evolutionChart'), {
            type: 'line',
            data: {
                labels: labelsMois,
                datasets: [{
                    label: 'Évolution des déchets collectés',
                    data: dataMois,
                    borderColor: '#36a2eb',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true
                }]
            }
        });
</script>
</body>
</html>
