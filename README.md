# Klinik Psikoloji Seans Takip Sistemi

Bu proje BSM218/BSM303 Veritabani Yonetim Sistemleri odev-3 icin hazirlanmis basit bir PHP web uygulamasidir.

## Proje Konusu

Bir klinik psikoloji merkezinde danisan, terapist, seans, odeme ve seans notu kayitlarini takip etmek icin hazirlanmistir.

## Kullanilan Teknolojiler

- Veritabani: Microsoft SQL Server
- Uygulama: PHP
- Arayuz: HTML ve CSS
- Mimari: N katmanli mimari

## Katmanlar

- Presentation Layer: `index.php`
- Business Layer: `app/ClinicService.php`
- Data Access Layer: `app/ClinicRepository.php`
- Veritabani Layer: `database/klinik_psikoloji_mssql.sql`

Uygulama icinde veritabani islemleri icin dogrudan SELECT, INSERT, UPDATE veya DELETE yazilmamistir. PHP tarafinda sadece stored procedure cagirilir.

## Veritabani

Veritabani dosyasi:

`database/klinik_psikoloji_mssql.sql`

Bu dosyada sunlar bulunur:

- Tablolar
- Primary Key, Foreign Key, Unique, Default, Check, Identity kullanimi
- Her tablo icin stored procedure islemleri
- 2 adet function
- 2 adet trigger
- Ornek veriler

## Calistirma

Once veritabanini kur:

```powershell
sqlcmd -S localhost -E -b -i database\klinik_psikoloji_mssql.sql
```

Sonra uygulamayi baslat:

```powershell
php -S 127.0.0.1:8020
```

Tarayici adresi:

```text
http://127.0.0.1:8020
```

## Proje Icerigi

- `index.php`: Ekrandaki formlar ve tablolar
- `style.css`: Basit arayuz tasarimi
- `app/ClinicService.php`: Is kurallari
- `app/ClinicRepository.php`: Stored procedure cagrilari
- `database/klinik_psikoloji_mssql.sql`: MSSQL veritabani script dosyasi
