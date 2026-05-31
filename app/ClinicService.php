<?php
require_once __DIR__ . '/ClinicRepository.php';

class ClinicService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new ClinicRepository();
    }

    public function dashboard($editType = null, $editId = null, $flash = null)
    {
        $data = $this->repo->listAll();
        $edit = $this->editState($editType, $editId);

        return [
            'data' => $data,
            'stats' => $this->makeStats($data),
            'lookups' => $this->makeLookups($data),
            'edit' => $edit,
            'editRow' => $edit ? $this->findEditRow($data, $edit['type'], $edit['id']) : null,
            'flash' => $flash
        ];
    }

    public function saveDanisan($form)
    {
        $this->required($form, 'Ad', 'Ad');
        $this->required($form, 'Soyad', 'Soyad');
        $this->required($form, 'Telefon', 'Telefon');
        $this->required($form, 'Eposta', 'E-posta');

        if ($this->hasId($form, 'DanisanId')) {
            $this->repo->updateDanisan($form);
            return;
        }

        $this->repo->addDanisan($form);
    }

    public function deleteDanisan($id)
    {
        $this->repo->deleteDanisan($id);
    }

    public function saveTerapist($form)
    {
        $this->required($form, 'Ad', 'Ad');
        $this->required($form, 'Soyad', 'Soyad');
        $this->required($form, 'Uzmanlik', 'Uzmanlik');
        $this->required($form, 'Telefon', 'Telefon');
        $this->required($form, 'Eposta', 'E-posta');

        if ($this->hasId($form, 'TerapistId')) {
            $this->repo->updateTerapist($form);
            return;
        }

        $this->repo->addTerapist($form);
    }

    public function deleteTerapist($id)
    {
        $this->repo->deleteTerapist($id);
    }

    public function saveTur($form)
    {
        $this->required($form, 'TurAdi', 'Seans turu');
        $this->positiveNumber($form, 'SureDakika', 'Sure');
        $this->positiveNumber($form, 'StandartUcret', 'Standart ucret');

        if ($this->hasId($form, 'TurId')) {
            $this->repo->updateTur($form);
            return;
        }

        $this->repo->addTur($form);
    }

    public function deleteTur($id)
    {
        $this->repo->deleteTur($id);
    }

    public function saveSeans($form)
    {
        $this->positiveNumber($form, 'DanisanId', 'Danisan');
        $this->positiveNumber($form, 'TerapistId', 'Terapist');
        $this->positiveNumber($form, 'TurId', 'Seans turu');
        $this->required($form, 'SeansTarihi', 'Seans tarihi');

        if ($this->hasId($form, 'SeansId')) {
            $this->positiveNumber($form, 'Ucret', 'Ucret');
            $this->repo->updateSeans($form);
            return;
        }

        $this->repo->addSeans($form);
    }

    public function deleteSeans($id)
    {
        $this->repo->deleteSeans($id);
    }

    public function saveOdeme($form)
    {
        $this->positiveNumber($form, 'SeansId', 'Seans');
        $this->positiveNumber($form, 'Tutar', 'Tutar');
        $this->required($form, 'OdemeTuru', 'Odeme turu');

        if ($this->hasId($form, 'OdemeId')) {
            $this->repo->updateOdeme($form);
            return;
        }

        $this->repo->addOdeme($form);
    }

    public function deleteOdeme($id)
    {
        $this->repo->deleteOdeme($id);
    }

    public function saveNot($form)
    {
        $this->positiveNumber($form, 'SeansId', 'Seans');
        $this->required($form, 'Baslik', 'Baslik');
        $this->required($form, 'NotMetni', 'Not metni');

        if ($this->hasId($form, 'NotId')) {
            $this->repo->updateNot($form);
            return;
        }

        $this->repo->addNot($form);
    }

    public function deleteNot($id)
    {
        $this->repo->deleteNot($id);
    }

    private function makeStats($data)
    {
        $toplamBakiye = 0;
        foreach ($data['danisanlar'] as $row) {
            $toplamBakiye += (float)$row['Bakiye'];
        }

        return [
            'danisanSayisi' => count($data['danisanlar']),
            'terapistSayisi' => count($data['terapistler']),
            'seansSayisi' => count($data['seanslar']),
            'odemeSayisi' => count($data['odemeler']),
            'toplamBakiye' => $toplamBakiye
        ];
    }

    private function makeLookups($data)
    {
        $lookups = [
            'danisanlar' => [],
            'terapistler' => [],
            'turler' => [],
            'seanslar' => []
        ];

        foreach ($data['danisanlar'] as $row) {
            $lookups['danisanlar'][] = [
                'id' => $row['DanisanId'],
                'text' => $row['Ad'] . ' ' . $row['Soyad']
            ];
        }

        foreach ($data['terapistler'] as $row) {
            $lookups['terapistler'][] = [
                'id' => $row['TerapistId'],
                'text' => $row['Ad'] . ' ' . $row['Soyad']
            ];
        }

        foreach ($data['turler'] as $row) {
            $lookups['turler'][] = [
                'id' => $row['TurId'],
                'text' => $row['TurAdi']
            ];
        }

        foreach ($data['seanslar'] as $row) {
            $lookups['seanslar'][] = [
                'id' => $row['SeansId'],
                'text' => '#' . $row['SeansId'] . ' - ' . $row['Danisan'] . ' / ' . $this->shortDateTime($row['SeansTarihi'])
            ];
        }

        return $lookups;
    }

    private function editState($type, $id)
    {
        $allowed = ['danisan', 'terapist', 'tur', 'seans', 'odeme', 'not'];
        if (!$type || !$id || !in_array($type, $allowed, true)) {
            return null;
        }

        return ['type' => $type, 'id' => (int)$id];
    }

    private function findEditRow($data, $type, $id)
    {
        $map = [
            'danisan' => ['danisanlar', 'DanisanId'],
            'terapist' => ['terapistler', 'TerapistId'],
            'tur' => ['turler', 'TurId'],
            'seans' => ['seanslar', 'SeansId'],
            'odeme' => ['odemeler', 'OdemeId'],
            'not' => ['notlar', 'NotId']
        ];

        $listName = $map[$type][0];
        $idName = $map[$type][1];
        foreach ($data[$listName] as $row) {
            if ((int)$row[$idName] === (int)$id) {
                return $row;
            }
        }

        return null;
    }

    private function required($form, $key, $label)
    {
        if (!isset($form[$key]) || trim((string)$form[$key]) === '') {
            throw new Exception($label . ' alani bos birakilamaz.');
        }
    }

    private function positiveNumber($form, $key, $label)
    {
        if (!isset($form[$key]) || (float)$form[$key] <= 0) {
            throw new Exception($label . ' 0 dan buyuk olmalidir.');
        }
    }

    private function hasId($form, $key)
    {
        return isset($form[$key]) && (int)$form[$key] > 0;
    }

    private function shortDateTime($value)
    {
        $time = strtotime((string)$value);
        if ($time === false) {
            return (string)$value;
        }

        return date('d.m.Y H:i', $time);
    }
}
