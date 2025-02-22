<?php
require 'config.php';
include 'session_check.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Récupérer tous les bénévoles et déchets
    $stmt = $pdo->query("
    SELECT b.id, b.nom, b.email, b.role, ROUND(COALESCE(SUM(d.quantite_kg), 0)) AS total_dechets
    FROM benevoles b
    LEFT JOIN collectes c ON b.id = c.id_benevole
    LEFT JOIN dechets_collectes d ON c.id = d.id_collecte
    GROUP BY b.id
    ORDER BY b.nom ASC
");
$benevoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}


?>
 
 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Bénévoles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-900">
<div class="flex h-screen">
    <!-- Barre de navigation -->
    <div class="bg-[#80DDB2] text-[#233B3D] w-64 p-6">
  <h2 class="text-2xl font-bold mb-6">Dashboard</h2>
  <li><a href="collection_list.php" class="flex items-center py-2 px-3 hover:bg-blue-91B9E4 rounded-lg"><i class="fas fa-tachometer-alt mr-3"></i> Tableau de bord</a></li>
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
        <h1 class="text-4xl font-bold text-blue-800 mb-6">Liste des Bénévoles</h1>
        <!-- Tableau des bénévoles -->
        <div class="overflow-hidden rounded-lg shadow-lg bg-white">
            <table class="w-full table-auto border-collapse">
                <thead class="bg-blue-800 text-white">
                <tr>
                    <th class="py-3 px-4 text-left">Nom</th>
                    <th class="py-3 px-4 text-left">Email</th>
                    <th class="py-3 px-4 text-left">Rôle</th>
                    <th class="py-3 px-4 text-left">Actions</th>
                    <th class="py-3 px-4 text-left">Quantité déchets</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-300">
                <tbody class="divide-y divide-gray-300">
    <?php foreach ($benevoles as $benevole) : ?>
        <tr class="hover:bg-gray-100 transition duration-200">
            <td class="py-3 px-4"><?= htmlspecialchars($benevole['nom']) ?></td>
            <td class="py-3 px-4"><?= htmlspecialchars($benevole['email']) ?></td>
            <td class="py-3 px-4"><?= htmlspecialchars($benevole['role']) ?></td>
            
            <td class="py-3 px-4 flex space-x-2">
            <a href="volunteer_edit.php?id=<?= $benevole['id'] ?>" 
                   class="bg-cyan-200 hover:bg-cyan-600 text-white px-4 py-2 rounded-lg shadow-lg">
                    ✏️ Modifier
                </a>
                <a href="volunteer_delete.php?id=<?= $benevole['id'] ?>" 
                   class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow-lg"
                   onclick="return confirm('Voulez-vous vraiment supprimer ce bénévole ?');">
                    🗑️ Supprimer
                </a>
                
            </td>
            <td class="py-3 px-4"><?= htmlspecialchars($benevole['total_dechets']) ?> kg</td>
        </tr>
    <?php endforeach; ?>
</tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>


