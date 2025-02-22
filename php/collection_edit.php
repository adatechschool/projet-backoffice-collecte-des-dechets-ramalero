<?php
require 'config.php';

// Vérifier si un ID de collecte est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: collection_list.php");
    exit;
}

$id = $_GET['id'];
// Récupérer les informations de la collecte
$stmt = $pdo->prepare("SELECT * FROM collectes WHERE id = ?");
$stmt->execute([$id]);
$collecte = $stmt->fetch();

// Récupérer les informations sur les dechets collectés
$stmt_dechets = $pdo->prepare("SELECT * FROM dechets_collectes WHERE id_collecte = ?");
$stmt_dechets->execute([$id]);
$types_dechets = $stmt_dechets->fetchAll();

if (!$collecte) {
    header("Location: collection_list.php");
    exit;
}

// Récupérer la liste des bénévoles
$stmt_benevoles = $pdo->prepare("SELECT id, nom FROM benevoles ORDER BY nom");
$stmt_benevoles->execute();
$benevoles = $stmt_benevoles->fetchAll();

// Mettre à jour la collecte
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo "<script>console.log('lalalal');</script>";
    $date = $_POST["date"];
    $lieu = $_POST["lieu"];
    $benevole_id = $_POST["benevole"]; // Récupérer l'ID du bénévole sélectionn

    $stmt = $pdo->prepare("UPDATE collectes SET date_collecte = ?, lieu = ?, id_benevole = ? WHERE id = ?");
    $stmt->execute([$date, $lieu, $benevole_id, $id]);

    header("Location: collection_list.php");
    exit;
}

// Mettre à jour les dechets collectés
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_collecte = $_POST["id_collecte"];
    $type_dechet = $_POST["type_dechet"];
    $quantite = $_POST["quantite"];

    try {
        // $stmt = $pdo->prepare("UPDATE dechets_collectes SET type_dechet = ?, quantite_kg = ? WHERE id_collecte = ?");
        // $stmt->execute([$type_dechet, $quantite, $id_collecte]);

        $stmt = $pdo->prepare("INSERT INTO dechets_collectes (type_dechet, quantite_kg, id_collecte) VALUES (?, ?, ?)");
        $stmt->execute([$type_dechet, $quantite, $id_collecte]);

    if ($stmt->rowCount() > 0) {
        echo "Mise à jour réussie!";
    } else {
        echo "Aucune ligne mise à jour. Vérifiez que l'ID est correct.";
    }

    header("Location: collection_list.php");
    exit;
    } catch (PDOException $e){
        echo "Erreur lors de la mise à jour: ". $e->getMessage();
    };
    
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une collecte</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">

<div class="flex h-screen">
    <!-- Dashboard -->
    <div class="bg-cyan-200 text-white w-64 p-6">
        <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

            <li><a href="collection_list.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fas fa-tachometer-alt mr-3"></i> Tableau de bord</a></li>
            <li><a href="volunteer_list.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fa-solid fa-list mr-3"></i> Liste des bénévoles</a></li>
            <li>
                <a href="user_add.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg">
                    <i class="fas fa-user-plus mr-3"></i> Ajouter un bénévole
                </a>
            </li>
            <li><a href="my_account.php" class="flex items-center py-2 px-3 hover:bg-blue-800 rounded-lg"><i class="fas fa-cogs mr-3"></i> Mon compte</a></li>

        <div class="mt-6">
            <button onclick="logout()" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg shadow-md">
                Déconnexion
            </button>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-4xl font-bold text-blue-900 mb-6">Modifier une collecte</h1>

        <!-- Formulaire -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date :</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($collecte['date_collecte']) ?>" required
                           class="w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Lieu :</label>
                    <input type="text" name="lieu" value="<?= htmlspecialchars($collecte['lieu']) ?>" required
                           class="w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bénévole :</label>
                    <select name="benevole" required
                            class="w-full p-2 border border-gray-300 rounded-lg">
                        <option value="" disabled selected>Sélectionnez un·e bénévole</option>
                        <?php foreach ($benevoles as $benevole): ?>
                            <option value="<?= $benevole['id'] ?>" <?= $benevole['id'] == $collecte['id_benevole'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($benevole['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <?php foreach ($types_dechets as $type_dechet): ?>
                        <label class="block text-sm font-medium text-gray-700">Types de dechet :</label>
                        <input type="text" name="existing_dechet" class="p-1 border border-gray-300 rounded-lg" value="<?= $type_dechet['type_dechet'] ?>" <?= $type_dechet['id'] == $type_dechet['id_collecte'] ? 'selected' : '' ?>>
                            <!-- <?= htmlspecialchars($type_dechet['type_dechet']) ?> -->
                        </input>
                        <label class="block text-sm font-medium text-gray-700">Quantité :</label>
                        <input type="number" name="existing_dechet" class="p-1 border border-gray-300 rounded-lg" value="<?= $type_dechet['quantite_kg'] ?>" <?= $type_dechet['id'] == $type_dechet['id_collecte'] ? 'selected' : '' ?>>
                            <!-- <?= htmlspecialchars($type_dechet['quantite_kg']) ?> -->
                        </input>
                    <?php endforeach; ?>
                 
                </div>
                <div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantité de déchets collectés :</label>
                    <input type="number" name="quantite" value="" required
                        class="w-full p-2 border border-gray-300 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700">type de dechet :</label>
                    <input type="text" name="type_dechet" value="" required
                        class="w-full p-2 border border-gray-300 rounded-lg">
                </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <a href="collection_list.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg">Annuler</a>
                    <button type="submit" class="bg-cyan-200 text-white px-4 py-2 rounded-lg">Modifier</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
