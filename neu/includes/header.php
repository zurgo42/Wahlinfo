<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mensa Wahlinfo - Ergänzende Wahlinformation">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) . ' - ' : ''; ?>Mensa Wahlinfo</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <img src="../logo_mensa.png" alt="Mensa Logo" class="header-logo">
            <h1><?php echo isset($pageTitle) ? escape($pageTitle) : 'Ergänzende Wahlinformation'; ?></h1>
        </div>
    </header>
    <main class="container">
