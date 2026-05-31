<?php

class ClinicRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = sqlsrv_connect('localhost', [
            'Database' => 'KlinikPsikolojiDB',
            'CharacterSet' => 'UTF-8',
            'ReturnDatesAsStrings' => true,
            'TrustServerCertificate' => true,
            'Encrypt' => false
        ]);

        if ($this->connection === false) {
            throw new Exception($this->errorText());
        }
    }

    public function listAll()
    {
        return [
            'danisanlar' => $this->rows('usp_Danisan_Listele'),
            'terapistler' => $this->rows('usp_Terapist_Listele'),
            'turler' => $this->rows('usp_SeansTuru_Listele'),
            'seanslar' => $this->rows('usp_Seans_Listele'),
            'odemeler' => $this->rows('usp_Odeme_Listele'),
            'notlar' => $this->rows('usp_SeansNotu_Listele')
        ];
    }

    public function addDanisan($form)
    {
        $this->run('usp_Danisan_Ekle', [
            $form['Ad'], $form['Soyad'], $form['Telefon'], $form['Eposta'],
            $this->emptyToNull($form['DogumTarihi']), $form['Durum']
        ]);
    }

    public function updateDanisan($form)
    {
        $this->run('usp_Danisan_Guncelle', [
            (int)$form['DanisanId'], $form['Ad'], $form['Soyad'], $form['Telefon'],
            $form['Eposta'], $this->emptyToNull($form['DogumTarihi']), $form['Durum']
        ]);
    }

    public function deleteDanisan($id)
    {
        $this->run('usp_Danisan_Sil', [(int)$id]);
    }

    public function addTerapist($form)
    {
        $this->run('usp_Terapist_Ekle', [
            $form['Ad'], $form['Soyad'], $form['Uzmanlik'], $form['Telefon'],
            $form['Eposta'], $form['AktifMi'] === '1' ? 1 : 0
        ]);
    }

    public function updateTerapist($form)
    {
        $this->run('usp_Terapist_Guncelle', [
            (int)$form['TerapistId'], $form['Ad'], $form['Soyad'], $form['Uzmanlik'],
            $form['Telefon'], $form['Eposta'], $form['AktifMi'] === '1' ? 1 : 0
        ]);
    }

    public function deleteTerapist($id)
    {
        $this->run('usp_Terapist_Sil', [(int)$id]);
    }

    public function addTur($form)
    {
        $this->run('usp_SeansTuru_Ekle', [
            $form['TurAdi'], (int)$form['SureDakika'], (float)$form['StandartUcret'],
            $this->emptyToNull($form['Aciklama'])
        ]);
    }

    public function updateTur($form)
    {
        $this->run('usp_SeansTuru_Guncelle', [
            (int)$form['TurId'], $form['TurAdi'], (int)$form['SureDakika'],
            (float)$form['StandartUcret'], $this->emptyToNull($form['Aciklama'])
        ]);
    }

    public function deleteTur($id)
    {
        $this->run('usp_SeansTuru_Sil', [(int)$id]);
    }

    public function addSeans($form)
    {
        $this->run('usp_Seans_Ekle', [
            (int)$form['DanisanId'], (int)$form['TerapistId'], (int)$form['TurId'],
            $this->dateTimeForSql($form['SeansTarihi']), $form['Durum'],
            $this->emptyToNull($form['Ucret']), $this->emptyToNull($form['Aciklama'])
        ]);
    }

    public function updateSeans($form)
    {
        $this->run('usp_Seans_Guncelle', [
            (int)$form['SeansId'], (int)$form['DanisanId'], (int)$form['TerapistId'],
            (int)$form['TurId'], $this->dateTimeForSql($form['SeansTarihi']),
            $form['Durum'], (float)$form['Ucret'], $this->emptyToNull($form['Aciklama'])
        ]);
    }

    public function deleteSeans($id)
    {
        $this->run('usp_Seans_Sil', [(int)$id]);
    }

    public function addOdeme($form)
    {
        $this->run('usp_Odeme_Ekle', [
            (int)$form['SeansId'], (float)$form['Tutar'], $form['OdemeTuru'],
            $this->emptyToNull($form['Aciklama'])
        ]);
    }

    public function updateOdeme($form)
    {
        $this->run('usp_Odeme_Guncelle', [
            (int)$form['OdemeId'], (int)$form['SeansId'], (float)$form['Tutar'],
            $form['OdemeTuru'], $this->emptyToNull($form['Aciklama'])
        ]);
    }

    public function deleteOdeme($id)
    {
        $this->run('usp_Odeme_Sil', [(int)$id]);
    }

    public function addNot($form)
    {
        $this->run('usp_SeansNotu_Ekle', [
            (int)$form['SeansId'], $form['Baslik'], $form['NotMetni']
        ]);
    }

    public function updateNot($form)
    {
        $this->run('usp_SeansNotu_Guncelle', [
            (int)$form['NotId'], (int)$form['SeansId'], $form['Baslik'], $form['NotMetni']
        ]);
    }

    public function deleteNot($id)
    {
        $this->run('usp_SeansNotu_Sil', [(int)$id]);
    }

    private function rows($procedureName, $params = [])
    {
        $stmt = sqlsrv_query($this->connection, $this->procedureSql($procedureName, count($params)), $params);
        if ($stmt === false) {
            throw new Exception($this->errorText());
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    private function run($procedureName, $params)
    {
        $stmt = sqlsrv_query($this->connection, $this->procedureSql($procedureName, count($params)), $params);
        if ($stmt === false) {
            throw new Exception($this->errorText());
        }

        while (sqlsrv_next_result($stmt)) {
        }

        sqlsrv_free_stmt($stmt);
    }

    private function procedureSql($procedureName, $count)
    {
        if ($count === 0) {
            return "{CALL $procedureName}";
        }

        return "{CALL $procedureName(" . implode(',', array_fill(0, $count, '?')) . ")}";
    }

    private function emptyToNull($value)
    {
        if (!isset($value) || trim((string)$value) === '') {
            return null;
        }

        return trim((string)$value);
    }

    private function dateTimeForSql($value)
    {
        return str_replace('T', ' ', (string)$value);
    }

    private function errorText()
    {
        $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        if (!$errors) {
            return 'Bilinmeyen veritabani hatasi olustu.';
        }

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error['message'];
        }

        return implode(' ', $messages);
    }
}
