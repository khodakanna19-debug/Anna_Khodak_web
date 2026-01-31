<?php
// form-handler.php
// Зберігає завантаження в /uploads і метадані у submissions.csv
// Пам'ятай: цей файл повинен запускатися на сервері з підтримкою PHP.

// === Налаштування ===
$uploadDir = __DIR__ . '/uploads';      // папка для файлів
$csvFile   = __DIR__ . '/submissions.csv'; // файл для зберігання записів
$maxSize   = 3 * 1024 * 1024; // 3 MB

// Створити папку uploads, якщо немає
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Простий helper для безпечного виводу
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Перевіряємо метод
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed.";
    exit;
}

// Отримуємо і валідуємо поля
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$cat_name = trim($_POST['cat_name'] ?? '');
$caption = trim($_POST['caption'] ?? '');
$genres = $_POST['genre'] ?? []; // масив

$errors = [];

// Валідація
if ($name === '') $errors[] = 'Вкажіть своє імʼя.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Вкажіть коректний email адрес.';
if (!empty($caption) && mb_strlen($caption) > 200) $errors[] = 'Підпис не має перевищувати 200 символів.';
if (!is_array($genres)) $genres = [];

// Файл
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'Оберіть фото котика (jpg/png).';
} else {
    $photo = $_FILES['photo'];
    if ($photo['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Помилка завантаження файлу.';
    } else {
        if ($photo['size'] > $maxSize) {
            $errors[] = 'Файл надто великий (макс 3 MB).';
        }
        // Перевірка MIME (тільки базова)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($photo['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!array_key_exists($mime, $allowed)) {
            $errors[] = 'Недопустимий формат файлу. Дозволені jpg, png.';
        }
    }
}

// Якщо є помилки — повертаємо повідомлення
if (!empty($errors)) {
    http_response_code(400);
    echo "<h2>Помилка</h2><ul>";
    foreach ($errors as $e) {
        echo "<li>" . esc($e) . "</li>";
    }
    echo "</ul><p><a href=\"javascript:history.back()\">Повернутись назад</a></p>";
    exit;
}

// Зберігаємо файл з унікальним ім'ям
$ext = $allowed[$mime];
$basename = bin2hex(random_bytes(8)); // унікальна частина імені
$filename = sprintf('%s.%s', $basename, $ext);
$destination = $uploadDir . '/' . $filename;

if (!move_uploaded_file($photo['tmp_name'], $destination)) {
    http_response_code(500);
    echo "<h2>Помилка</h2><p>Не вдалося зберегти файл на сервері.</p><p><a href=\"javascript:history.back()\">Повернутись назад</a></p>";
    exit;
}

// Підтвердження/редукція полів перед збереженням
$now = date('Y-m-d H:i:s');
$genres_str = implode('|', array_map('trim', $genres)); // роздільник |

// Зберігаємо запис у CSV (id, date, name, email, cat_name, caption, genres, filename)
$record = [
    bin2hex(random_bytes(6)),
    $now,
    $name,
    $email,
    $cat_name,
    $caption,
    $genres_str,
    $filename
];

// Переконатися, що файл csv існує або створити з заголовком
if (!file_exists($csvFile)) {
    $hdr = ['id','date','name','email','cat_name','caption','genres','photo'];
    $f = fopen($csvFile, 'a');
    fputcsv($f, $hdr);
    fclose($f);
}

// Запис
$f = fopen($csvFile, 'a');
if ($f === false) {
    http_response_code(500);
    echo "<h2>Помилка</h2><p>Не вдалося відкрити файл для збереження запису.</p>";
    exit;
}
fputcsv($f, $record);
fclose($f);

// (Опційно) Надіслати підтвердження email (залежить від налаштувань сервера)
// $subject = "Підписка Books & Cats — нове надходження";
// $message = "Отримано нову підписку/фото.\nІм'я: $name\nEmail: $email\nКіт: $cat_name\nФайл: $filename";
// mail($to_admin_email, $subject, $message);

// Відповідь користувачу
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Дякуємо — Books & Cats</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Press Start 2P',cursive;background:linear-gradient(180deg,#fff7fb,#ffe3f2);color:#2b1f2a;padding:2rem}
    .card{max-width:700px;margin:2rem auto;padding:1.2rem;border-radius:12px;background:#fff;border:4px solid #ffd0e8}
    a{color:#ff66b2;text-decoration:none}
    .thumb{display:block;margin:1rem 0}
    img{max-width:200px;border-radius:8px}
  </style>
</head>
<body>
  <div class="card">
    <h2>Дякуємо, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>!</h2>
    <p>Ми зберегли ваш запис (<?= htmlspecialchars($now) ?>).</p>

    <p>Інформація, яку збережено:</p>
    <ul>
      <li>Ім'я: <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></li>
      <li>Email: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></li>
      <li>Ім'я котика: <?= htmlspecialchars($cat_name, ENT_QUOTES, 'UTF-8') ?></li>
      <li>Жанри: <?= htmlspecialchars($genres_str, ENT_QUOTES, 'UTF-8') ?></li>
      <li>Підпис: <?= nl2br(htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')) ?></li>
    </ul>

    <p>Переглянути завантажене фото:</p>
    <a class="thumb" href="<?php echo 'uploads/' . rawurlencode($filename); ?>" target="_blank">
      <img src="<?php echo 'uploads/' . rawurlencode($filename); ?>" alt="Завантажене фото">
    </a>

    <p><a href="writers-and-cats.html">Повернутись на сторінку</a></p>
  </div>
</body>
</html>
