<?php
session_start();
require_once __DIR__ . '/app/ClinicService.php';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function v($row, $key, $default = '')
{
    if (!$row || !array_key_exists($key, $row) || $row[$key] === null) {
        return $default;
    }

    return $row[$key];
}

function selectedValue($actual, $expected, $default = false)
{
    if ($actual === null || $actual === '') {
        return $default ? 'selected' : '';
    }

    return (string)$actual === (string)$expected ? 'selected' : '';
}

function money($value)
{
    return number_format((float)$value, 2, ',', '.') . ' TL';
}

function dateInput($value)
{
    if (!$value) {
        return '';
    }

    $time = strtotime((string)$value);
    return $time ? date('Y-m-d', $time) : '';
}

function dateTimeInput($value)
{
    if (!$value) {
        return '';
    }

    $time = strtotime((string)$value);
    return $time ? date('Y-m-d\TH:i', $time) : '';
}

function dateTimeText($value)
{
    if (!$value) {
        return '-';
    }

    $time = strtotime((string)$value);
    return $time ? date('d.m.Y H:i', $time) : h($value);
}

function editRow($dashboard, $type)
{
    if (!$dashboard['edit'] || $dashboard['edit']['type'] !== $type) {
        return null;
    }

    return $dashboard['editRow'];
}

function redirectAfter($anchor)
{
    header('Location: index.php#' . $anchor);
    exit;
}

function errorPage($message)
{
    http_response_code(500);
    ?>
    <!doctype html>
    <html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Hata</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <main class="page narrow">
            <h1>Islem yapilamadi</h1>
            <p class="error-text"><?php echo h($message); ?></p>
            <a class="ghost-link" href="index.php">Ana sayfaya don</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

try {
    $service = new ClinicService();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_danisan') {
            $service->saveDanisan($_POST);
            $_SESSION['flash'] = 'Danisan kaydi kaydedildi.';
            redirectAfter('danisan');
        }

        if ($action === 'delete_danisan') {
            $service->deleteDanisan($_POST['id'] ?? 0);
            $_SESSION['flash'] = 'Danisan kaydi silindi.';
            redirectAfter('danisan');
        }

        if ($action === 'save_terapist') {
            $service->saveTerapist($_POST);
            $_SESSION['flash'] = 'Terapist kaydi kaydedildi.';
            redirectAfter('terapist');
        }

        if ($action === 'delete_terapist') {
            $service->deleteTerapist($_POST['id'] ?? 0);
            $_SESSION['flash'] = 'Terapist kaydi silindi.';
            redirectAfter('terapist');
        }

        if ($action === 'save_tur') {
            $service->saveTur($_POST);
            $_SESSION['flash'] = 'Seans turu kaydedildi.';
            redirectAfter('tur');
        }

        if ($action === 'delete_tur') {
            $service->deleteTur($_POST['id'] ?? 0);
            $_SESSION['flash'] = 'Seans turu silindi.';
            redirectAfter('tur');
        }

        if ($action === 'save_seans') {
            $service->saveSeans($_POST);
            $_SESSION['flash'] = 'Seans kaydi kaydedildi.';
            redirectAfter('seans');
        }

        if ($action === 'delete_seans') {
            $service->deleteSeans($_POST['id'] ?? 0);
            $_SESSION['flash'] = 'Seans kaydi silindi.';
            redirectAfter('seans');
        }

        if ($action === 'save_odeme') {
            $service->saveOdeme($_POST);
            $_SESSION['flash'] = 'Odeme kaydi kaydedildi.';
            redirectAfter('odeme');
        }

        if ($action === 'delete_odeme') {
            $service->deleteOdeme($_POST['id'] ?? 0);
            $_SESSION['flash'] = 'Odeme kaydi silindi.';
            redirectAfter('odeme');
        }

        if ($action === 'save_not') {
            $service->saveNot($_POST);
            $_SESSION['flash'] = 'Seans notu kaydedildi.';
            redirectAfter('not');
        }

        if ($action === 'delete_not') {
            $service->deleteNot($_POST['id'] ?? 0);
            $_SESSION['flash'] = 'Seans notu silindi.';
            redirectAfter('not');
        }

        throw new Exception('Bilinmeyen islem.');
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    $dashboard = $service->dashboard($_GET['edit'] ?? null, $_GET['id'] ?? null, $flash);
} catch (Exception $e) {
    errorPage($e->getMessage());
}

$data = $dashboard['data'];
$stats = $dashboard['stats'];
$lookups = $dashboard['lookups'];

$danisanEdit = editRow($dashboard, 'danisan');
$terapistEdit = editRow($dashboard, 'terapist');
$turEdit = editRow($dashboard, 'tur');
$seansEdit = editRow($dashboard, 'seans');
$odemeEdit = editRow($dashboard, 'odeme');
$notEdit = editRow($dashboard, 'not');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Klinik Psikoloji Seans Takip Sistemi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <h1>Klinik Psikoloji Seans Takip Sistemi</h1>
        <nav>
            <a href="#danisan">Danisan</a>
            <a href="#terapist">Terapist</a>
            <a href="#tur">Seans Turu</a>
            <a href="#seans">Seans</a>
            <a href="#odeme">Odeme</a>
            <a href="#not">Not</a>
        </nav>
        <p class="user-line">Veritabani Dersi Projesi - PHP / MSSQL | Stored procedure ile calisir</p>
    </header>

    <main class="page">
        <?php if ($dashboard['flash']) : ?>
            <div class="notice"><?php echo h($dashboard['flash']); ?></div>
        <?php endif; ?>

        <h2 class="page-title">Ana Sayfa</h2>
        <section class="purpose">
            <h3>Projenin Amaci</h3>
            <p>Bu projede bir klinikte danisan, terapist, seans, odeme ve seans notu kayitlari takip edilir. Butun islemler veritabanindaki stored procedure'ler uzerinden yapilir.</p>
        </section>

        <section class="stats">
            <article><strong><?php echo h($stats['danisanSayisi']); ?></strong><span>Danisan</span></article>
            <article><strong><?php echo h($stats['terapistSayisi']); ?></strong><span>Terapist</span></article>
            <article><strong><?php echo h($stats['seansSayisi']); ?></strong><span>Seans</span></article>
            <article><strong><?php echo money($stats['toplamBakiye']); ?></strong><span>Toplam bakiye</span></article>
        </section>

        <section id="danisan" class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Danisanlar</p>
                    <h2>Danisan kaydi</h2>
                </div>
                <?php if ($danisanEdit) : ?><a class="ghost-link" href="index.php#danisan">Yeni kayit</a><?php endif; ?>
            </div>

            <form class="form-grid" method="post">
                <input type="hidden" name="action" value="save_danisan">
                <input type="hidden" name="DanisanId" value="<?php echo h(v($danisanEdit, 'DanisanId')); ?>">
                <label>Ad<input name="Ad" required value="<?php echo h(v($danisanEdit, 'Ad')); ?>"></label>
                <label>Soyad<input name="Soyad" required value="<?php echo h(v($danisanEdit, 'Soyad')); ?>"></label>
                <label>Telefon<input name="Telefon" required value="<?php echo h(v($danisanEdit, 'Telefon')); ?>"></label>
                <label>E-posta<input name="Eposta" type="email" required value="<?php echo h(v($danisanEdit, 'Eposta')); ?>"></label>
                <label>Dogum tarihi<input name="DogumTarihi" type="date" value="<?php echo h(dateInput(v($danisanEdit, 'DogumTarihi'))); ?>"></label>
                <label>Durum
                    <select name="Durum">
                        <option value="Aktif" <?php echo selectedValue(v($danisanEdit, 'Durum'), 'Aktif', true); ?>>Aktif</option>
                        <option value="Pasif" <?php echo selectedValue(v($danisanEdit, 'Durum'), 'Pasif'); ?>>Pasif</option>
                    </select>
                </label>
                <button type="submit"><?php echo $danisanEdit ? 'Danisani guncelle' : 'Danisan ekle'; ?></button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Ad Soyad</th><th>Telefon</th><th>E-posta</th><th>Durum</th><th>Bakiye</th><th>Islem</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['danisanlar'] as $row) : ?>
                        <tr>
                            <td><?php echo h($row['DanisanId']); ?></td>
                            <td><?php echo h($row['Ad'] . ' ' . $row['Soyad']); ?></td>
                            <td><?php echo h($row['Telefon']); ?></td>
                            <td><?php echo h($row['Eposta']); ?></td>
                            <td><span class="status"><?php echo h($row['Durum']); ?></span></td>
                            <td><?php echo money($row['Bakiye']); ?></td>
                            <td class="actions">
                                <a href="index.php?edit=danisan&id=<?php echo h($row['DanisanId']); ?>#danisan">Duzenle</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_danisan">
                                    <input type="hidden" name="id" value="<?php echo h($row['DanisanId']); ?>">
                                    <button type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="terapist" class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Terapistler</p>
                    <h2>Terapist kaydi</h2>
                </div>
                <?php if ($terapistEdit) : ?><a class="ghost-link" href="index.php#terapist">Yeni kayit</a><?php endif; ?>
            </div>

            <form class="form-grid" method="post">
                <input type="hidden" name="action" value="save_terapist">
                <input type="hidden" name="TerapistId" value="<?php echo h(v($terapistEdit, 'TerapistId')); ?>">
                <label>Ad<input name="Ad" required value="<?php echo h(v($terapistEdit, 'Ad')); ?>"></label>
                <label>Soyad<input name="Soyad" required value="<?php echo h(v($terapistEdit, 'Soyad')); ?>"></label>
                <label>Uzmanlik<input name="Uzmanlik" required value="<?php echo h(v($terapistEdit, 'Uzmanlik')); ?>"></label>
                <label>Telefon<input name="Telefon" required value="<?php echo h(v($terapistEdit, 'Telefon')); ?>"></label>
                <label>E-posta<input name="Eposta" type="email" required value="<?php echo h(v($terapistEdit, 'Eposta')); ?>"></label>
                <label>Aktif mi?
                    <select name="AktifMi">
                        <option value="1" <?php echo selectedValue(v($terapistEdit, 'AktifMi'), 1, true); ?>>Evet</option>
                        <option value="0" <?php echo selectedValue(v($terapistEdit, 'AktifMi'), 0); ?>>Hayir</option>
                    </select>
                </label>
                <button type="submit"><?php echo $terapistEdit ? 'Terapisti guncelle' : 'Terapist ekle'; ?></button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Ad Soyad</th><th>Uzmanlik</th><th>Telefon</th><th>Bu ay</th><th>Islem</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['terapistler'] as $row) : ?>
                        <tr>
                            <td><?php echo h($row['TerapistId']); ?></td>
                            <td><?php echo h($row['Ad'] . ' ' . $row['Soyad']); ?></td>
                            <td><?php echo h($row['Uzmanlik']); ?></td>
                            <td><?php echo h($row['Telefon']); ?></td>
                            <td><?php echo h($row['BuAySeansSayisi']); ?> seans</td>
                            <td class="actions">
                                <a href="index.php?edit=terapist&id=<?php echo h($row['TerapistId']); ?>#terapist">Duzenle</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_terapist">
                                    <input type="hidden" name="id" value="<?php echo h($row['TerapistId']); ?>">
                                    <button type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="tur" class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Seans turleri</p>
                    <h2>Seans tipi ve ucret bilgisi</h2>
                </div>
                <?php if ($turEdit) : ?><a class="ghost-link" href="index.php#tur">Yeni kayit</a><?php endif; ?>
            </div>

            <form class="form-grid" method="post">
                <input type="hidden" name="action" value="save_tur">
                <input type="hidden" name="TurId" value="<?php echo h(v($turEdit, 'TurId')); ?>">
                <label>Tur adi<input name="TurAdi" required value="<?php echo h(v($turEdit, 'TurAdi')); ?>"></label>
                <label>Sure<input name="SureDakika" type="number" min="20" max="180" required value="<?php echo h(v($turEdit, 'SureDakika', 50)); ?>"></label>
                <label>Standart ucret<input name="StandartUcret" type="number" min="0" step="0.01" required value="<?php echo h(v($turEdit, 'StandartUcret', 600)); ?>"></label>
                <label class="wide">Aciklama<input name="Aciklama" value="<?php echo h(v($turEdit, 'Aciklama')); ?>"></label>
                <button type="submit"><?php echo $turEdit ? 'Seans turunu guncelle' : 'Seans turu ekle'; ?></button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Tur</th><th>Sure</th><th>Ucret</th><th>Aciklama</th><th>Islem</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['turler'] as $row) : ?>
                        <tr>
                            <td><?php echo h($row['TurId']); ?></td>
                            <td><?php echo h($row['TurAdi']); ?></td>
                            <td><?php echo h($row['SureDakika']); ?> dk</td>
                            <td><?php echo money($row['StandartUcret']); ?></td>
                            <td><?php echo h(v($row, 'Aciklama', '-')); ?></td>
                            <td class="actions">
                                <a href="index.php?edit=tur&id=<?php echo h($row['TurId']); ?>#tur">Duzenle</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_tur">
                                    <input type="hidden" name="id" value="<?php echo h($row['TurId']); ?>">
                                    <button type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="seans" class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Seanslar</p>
                    <h2>Randevu ve seans takibi</h2>
                </div>
                <?php if ($seansEdit) : ?><a class="ghost-link" href="index.php#seans">Yeni kayit</a><?php endif; ?>
            </div>

            <form class="form-grid" method="post">
                <input type="hidden" name="action" value="save_seans">
                <input type="hidden" name="SeansId" value="<?php echo h(v($seansEdit, 'SeansId')); ?>">
                <label>Danisan
                    <select name="DanisanId" required>
                        <?php foreach ($lookups['danisanlar'] as $item) : ?>
                            <option value="<?php echo h($item['id']); ?>" <?php echo selectedValue(v($seansEdit, 'DanisanId'), $item['id']); ?>><?php echo h($item['text']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Terapist
                    <select name="TerapistId" required>
                        <?php foreach ($lookups['terapistler'] as $item) : ?>
                            <option value="<?php echo h($item['id']); ?>" <?php echo selectedValue(v($seansEdit, 'TerapistId'), $item['id']); ?>><?php echo h($item['text']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Seans turu
                    <select name="TurId" required>
                        <?php foreach ($lookups['turler'] as $item) : ?>
                            <option value="<?php echo h($item['id']); ?>" <?php echo selectedValue(v($seansEdit, 'TurId'), $item['id']); ?>><?php echo h($item['text']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Seans tarihi<input name="SeansTarihi" type="datetime-local" required value="<?php echo h($seansEdit ? dateTimeInput($seansEdit['SeansTarihi']) : '2026-06-02T10:00'); ?>"></label>
                <label>Durum
                    <select name="Durum">
                        <option value="Planlandi" <?php echo selectedValue(v($seansEdit, 'Durum'), 'Planlandi', true); ?>>Planlandi</option>
                        <option value="Tamamlandi" <?php echo selectedValue(v($seansEdit, 'Durum'), 'Tamamlandi'); ?>>Tamamlandi</option>
                        <option value="Iptal" <?php echo selectedValue(v($seansEdit, 'Durum'), 'Iptal'); ?>>Iptal</option>
                    </select>
                </label>
                <label>Ucret<input name="Ucret" type="number" min="0" step="0.01" value="<?php echo h(v($seansEdit, 'Ucret')); ?>" placeholder="Bos ise standart ucret"></label>
                <label class="wide">Aciklama<input name="Aciklama" value="<?php echo h(v($seansEdit, 'Aciklama')); ?>"></label>
                <button type="submit"><?php echo $seansEdit ? 'Seansi guncelle' : 'Seans ekle'; ?></button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Danisan</th><th>Terapist</th><th>Tur</th><th>Tarih</th><th>Durum</th><th>Ucret</th><th>Islem</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['seanslar'] as $row) : ?>
                        <tr>
                            <td><?php echo h($row['SeansId']); ?></td>
                            <td><?php echo h($row['Danisan']); ?></td>
                            <td><?php echo h($row['Terapist']); ?></td>
                            <td><?php echo h($row['TurAdi']); ?></td>
                            <td><?php echo dateTimeText($row['SeansTarihi']); ?></td>
                            <td><span class="status"><?php echo h($row['Durum']); ?></span></td>
                            <td><?php echo money($row['Ucret']); ?></td>
                            <td class="actions">
                                <a href="index.php?edit=seans&id=<?php echo h($row['SeansId']); ?>#seans">Duzenle</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_seans">
                                    <input type="hidden" name="id" value="<?php echo h($row['SeansId']); ?>">
                                    <button type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="odeme" class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Odemeler</p>
                    <h2>Seans odeme kaydi</h2>
                </div>
                <?php if ($odemeEdit) : ?><a class="ghost-link" href="index.php#odeme">Yeni kayit</a><?php endif; ?>
            </div>

            <form class="form-grid" method="post">
                <input type="hidden" name="action" value="save_odeme">
                <input type="hidden" name="OdemeId" value="<?php echo h(v($odemeEdit, 'OdemeId')); ?>">
                <label>Seans
                    <select name="SeansId" required>
                        <?php foreach ($lookups['seanslar'] as $item) : ?>
                            <option value="<?php echo h($item['id']); ?>" <?php echo selectedValue(v($odemeEdit, 'SeansId'), $item['id']); ?>><?php echo h($item['text']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Tutar<input name="Tutar" type="number" min="0" step="0.01" required value="<?php echo h(v($odemeEdit, 'Tutar', 600)); ?>"></label>
                <label>Odeme turu
                    <select name="OdemeTuru">
                        <option value="Nakit" <?php echo selectedValue(v($odemeEdit, 'OdemeTuru'), 'Nakit', true); ?>>Nakit</option>
                        <option value="Kredi Karti" <?php echo selectedValue(v($odemeEdit, 'OdemeTuru'), 'Kredi Karti'); ?>>Kredi Karti</option>
                        <option value="Havale" <?php echo selectedValue(v($odemeEdit, 'OdemeTuru'), 'Havale'); ?>>Havale</option>
                    </select>
                </label>
                <label class="wide">Aciklama<input name="Aciklama" value="<?php echo h(v($odemeEdit, 'Aciklama')); ?>"></label>
                <button type="submit"><?php echo $odemeEdit ? 'Odemeyi guncelle' : 'Odeme ekle'; ?></button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Seans</th><th>Danisan</th><th>Tarih</th><th>Tutar</th><th>Tur</th><th>Islem</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['odemeler'] as $row) : ?>
                        <tr>
                            <td><?php echo h($row['OdemeId']); ?></td>
                            <td>#<?php echo h($row['SeansId']); ?></td>
                            <td><?php echo h($row['Danisan']); ?></td>
                            <td><?php echo dateTimeText($row['OdemeTarihi']); ?></td>
                            <td><?php echo money($row['Tutar']); ?></td>
                            <td><?php echo h($row['OdemeTuru']); ?></td>
                            <td class="actions">
                                <a href="index.php?edit=odeme&id=<?php echo h($row['OdemeId']); ?>#odeme">Duzenle</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_odeme">
                                    <input type="hidden" name="id" value="<?php echo h($row['OdemeId']); ?>">
                                    <button type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="not" class="panel">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Seans notlari</p>
                    <h2>Gorusme notu</h2>
                </div>
                <?php if ($notEdit) : ?><a class="ghost-link" href="index.php#not">Yeni kayit</a><?php endif; ?>
            </div>

            <form class="form-grid" method="post">
                <input type="hidden" name="action" value="save_not">
                <input type="hidden" name="NotId" value="<?php echo h(v($notEdit, 'NotId')); ?>">
                <label>Seans
                    <select name="SeansId" required>
                        <?php foreach ($lookups['seanslar'] as $item) : ?>
                            <option value="<?php echo h($item['id']); ?>" <?php echo selectedValue(v($notEdit, 'SeansId'), $item['id']); ?>><?php echo h($item['text']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Baslik<input name="Baslik" required value="<?php echo h(v($notEdit, 'Baslik')); ?>"></label>
                <label class="wide">Not metni<textarea name="NotMetni" required><?php echo h(v($notEdit, 'NotMetni')); ?></textarea></label>
                <button type="submit"><?php echo $notEdit ? 'Notu guncelle' : 'Not ekle'; ?></button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Seans</th><th>Danisan</th><th>Baslik</th><th>Not</th><th>Islem</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['notlar'] as $row) : ?>
                        <tr>
                            <td><?php echo h($row['NotId']); ?></td>
                            <td>#<?php echo h($row['SeansId']); ?></td>
                            <td><?php echo h($row['Danisan']); ?></td>
                            <td><?php echo h($row['Baslik']); ?></td>
                            <td><?php echo h($row['NotMetni']); ?></td>
                            <td class="actions">
                                <a href="index.php?edit=not&id=<?php echo h($row['NotId']); ?>#not">Duzenle</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_not">
                                    <input type="hidden" name="id" value="<?php echo h($row['NotId']); ?>">
                                    <button type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
