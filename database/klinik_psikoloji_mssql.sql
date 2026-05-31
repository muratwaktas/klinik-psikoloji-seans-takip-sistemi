IF DB_ID(N'KlinikPsikolojiDB') IS NOT NULL
BEGIN
    ALTER DATABASE KlinikPsikolojiDB SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE KlinikPsikolojiDB;
END
GO

CREATE DATABASE KlinikPsikolojiDB;
GO

USE KlinikPsikolojiDB;
GO

CREATE TABLE Danisanlar
(
    DanisanId       INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    Ad              NVARCHAR(50)      NOT NULL,
    Soyad           NVARCHAR(50)      NOT NULL,
    Telefon         NVARCHAR(20)      NOT NULL UNIQUE,
    Eposta          NVARCHAR(100)     NOT NULL UNIQUE,
    DogumTarihi     DATE              NULL,
    KayitTarihi     DATE              NOT NULL CONSTRAINT DF_Danisanlar_KayitTarihi DEFAULT (CAST(GETDATE() AS DATE)),
    Durum           NVARCHAR(20)      NOT NULL CONSTRAINT DF_Danisanlar_Durum DEFAULT (N'Aktif'),
    CONSTRAINT CK_Danisanlar_Durum CHECK (Durum IN (N'Aktif', N'Pasif'))
);
GO

CREATE TABLE Terapistler
(
    TerapistId      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    Ad              NVARCHAR(50)      NOT NULL,
    Soyad           NVARCHAR(50)      NOT NULL,
    Uzmanlik        NVARCHAR(100)     NOT NULL,
    Telefon         NVARCHAR(20)      NOT NULL UNIQUE,
    Eposta          NVARCHAR(100)     NOT NULL UNIQUE,
    AktifMi         BIT               NOT NULL CONSTRAINT DF_Terapistler_AktifMi DEFAULT (1)
);
GO

CREATE TABLE SeansTurleri
(
    TurId           INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    TurAdi          NVARCHAR(80)      NOT NULL UNIQUE,
    SureDakika      INT               NOT NULL CONSTRAINT DF_SeansTurleri_Sure DEFAULT (50),
    StandartUcret   DECIMAL(10,2)     NOT NULL,
    Aciklama        NVARCHAR(250)     NULL,
    CONSTRAINT CK_SeansTurleri_Sure CHECK (SureDakika BETWEEN 20 AND 180),
    CONSTRAINT CK_SeansTurleri_Ucret CHECK (StandartUcret >= 0)
);
GO

CREATE TABLE Seanslar
(
    SeansId         INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    DanisanId       INT               NOT NULL,
    TerapistId      INT               NOT NULL,
    TurId           INT               NOT NULL,
    SeansTarihi     DATETIME2(0)      NOT NULL,
    Durum           NVARCHAR(20)      NOT NULL CONSTRAINT DF_Seanslar_Durum DEFAULT (N'Planlandi'),
    Ucret           DECIMAL(10,2)     NOT NULL,
    Aciklama        NVARCHAR(250)     NULL,
    CONSTRAINT FK_Seanslar_Danisanlar FOREIGN KEY (DanisanId) REFERENCES Danisanlar(DanisanId),
    CONSTRAINT FK_Seanslar_Terapistler FOREIGN KEY (TerapistId) REFERENCES Terapistler(TerapistId),
    CONSTRAINT FK_Seanslar_SeansTurleri FOREIGN KEY (TurId) REFERENCES SeansTurleri(TurId),
    CONSTRAINT CK_Seanslar_Durum CHECK (Durum IN (N'Planlandi', N'Tamamlandi', N'Iptal')),
    CONSTRAINT CK_Seanslar_Ucret CHECK (Ucret >= 0)
);
GO

CREATE TABLE Odemeler
(
    OdemeId         INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    SeansId         INT               NOT NULL UNIQUE,
    OdemeTarihi     DATETIME2(0)      NOT NULL CONSTRAINT DF_Odemeler_Tarih DEFAULT (GETDATE()),
    Tutar           DECIMAL(10,2)     NOT NULL,
    OdemeTuru       NVARCHAR(30)      NOT NULL,
    Aciklama        NVARCHAR(250)     NULL,
    CONSTRAINT FK_Odemeler_Seanslar FOREIGN KEY (SeansId) REFERENCES Seanslar(SeansId) ON DELETE CASCADE,
    CONSTRAINT CK_Odemeler_Tutar CHECK (Tutar > 0),
    CONSTRAINT CK_Odemeler_Tur CHECK (OdemeTuru IN (N'Nakit', N'Kredi Karti', N'Havale'))
);
GO

CREATE TABLE SeansNotlari
(
    NotId           INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    SeansId         INT               NOT NULL,
    NotTarihi       DATETIME2(0)      NOT NULL CONSTRAINT DF_SeansNotlari_Tarih DEFAULT (GETDATE()),
    Baslik          NVARCHAR(100)     NOT NULL,
    NotMetni        NVARCHAR(1000)    NOT NULL,
    CONSTRAINT FK_SeansNotlari_Seanslar FOREIGN KEY (SeansId) REFERENCES Seanslar(SeansId) ON DELETE CASCADE
);
GO

CREATE FUNCTION dbo.fn_DanisanBakiye(@DanisanId INT)
RETURNS DECIMAL(10,2)
AS
BEGIN
    DECLARE @Borc DECIMAL(10,2);
    DECLARE @Odeme DECIMAL(10,2);

    SELECT @Borc = ISNULL(SUM(Ucret), 0)
    FROM Seanslar
    WHERE DanisanId = @DanisanId AND Durum <> N'Iptal';

    SELECT @Odeme = ISNULL(SUM(o.Tutar), 0)
    FROM Odemeler o
    INNER JOIN Seanslar s ON s.SeansId = o.SeansId
    WHERE s.DanisanId = @DanisanId;

    RETURN @Borc - @Odeme;
END;
GO

CREATE FUNCTION dbo.fn_TerapistAylikSeansSayisi(@TerapistId INT, @Yil INT, @Ay INT)
RETURNS INT
AS
BEGIN
    DECLARE @Sayi INT;

    SELECT @Sayi = COUNT(*)
    FROM Seanslar
    WHERE TerapistId = @TerapistId
      AND YEAR(SeansTarihi) = @Yil
      AND MONTH(SeansTarihi) = @Ay
      AND Durum <> N'Iptal';

    RETURN ISNULL(@Sayi, 0);
END;
GO

CREATE TRIGGER trg_Seans_CakismaKontrol
ON Seanslar
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    IF EXISTS
    (
        SELECT 1
        FROM inserted i
        INNER JOIN Seanslar s
            ON s.TerapistId = i.TerapistId
           AND s.SeansTarihi = i.SeansTarihi
           AND s.SeansId <> i.SeansId
           AND s.Durum <> N'Iptal'
           AND i.Durum <> N'Iptal'
    )
    BEGIN
        RAISERROR(N'Terapistin ayni tarih ve saatte baska seansi vardir.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END
END;
GO

CREATE TRIGGER trg_Odeme_TutarKontrol
ON Odemeler
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    IF EXISTS
    (
        SELECT 1
        FROM inserted i
        INNER JOIN Seanslar s ON s.SeansId = i.SeansId
        WHERE i.Tutar > s.Ucret
    )
    BEGIN
        RAISERROR(N'Odeme tutari seans ucretinden buyuk olamaz.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END
END;
GO

CREATE PROCEDURE usp_Danisan_Ekle
    @Ad NVARCHAR(50),
    @Soyad NVARCHAR(50),
    @Telefon NVARCHAR(20),
    @Eposta NVARCHAR(100),
    @DogumTarihi DATE = NULL,
    @Durum NVARCHAR(20) = N'Aktif'
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO Danisanlar (Ad, Soyad, Telefon, Eposta, DogumTarihi, Durum)
    VALUES (@Ad, @Soyad, @Telefon, @Eposta, @DogumTarihi, @Durum);
END;
GO

CREATE PROCEDURE usp_Danisan_Guncelle
    @DanisanId INT,
    @Ad NVARCHAR(50),
    @Soyad NVARCHAR(50),
    @Telefon NVARCHAR(20),
    @Eposta NVARCHAR(100),
    @DogumTarihi DATE = NULL,
    @Durum NVARCHAR(20)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Danisanlar
    SET Ad = @Ad,
        Soyad = @Soyad,
        Telefon = @Telefon,
        Eposta = @Eposta,
        DogumTarihi = @DogumTarihi,
        Durum = @Durum
    WHERE DanisanId = @DanisanId;
END;
GO

CREATE PROCEDURE usp_Danisan_Sil
    @DanisanId INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM Danisanlar WHERE DanisanId = @DanisanId;
END;
GO

CREATE PROCEDURE usp_Danisan_Listele
AS
BEGIN
    SET NOCOUNT ON;
    SELECT DanisanId, Ad, Soyad, Telefon, Eposta, DogumTarihi, KayitTarihi, Durum,
           dbo.fn_DanisanBakiye(DanisanId) AS Bakiye
    FROM Danisanlar
    ORDER BY DanisanId DESC;
END;
GO

CREATE PROCEDURE usp_Terapist_Ekle
    @Ad NVARCHAR(50),
    @Soyad NVARCHAR(50),
    @Uzmanlik NVARCHAR(100),
    @Telefon NVARCHAR(20),
    @Eposta NVARCHAR(100),
    @AktifMi BIT = 1
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO Terapistler (Ad, Soyad, Uzmanlik, Telefon, Eposta, AktifMi)
    VALUES (@Ad, @Soyad, @Uzmanlik, @Telefon, @Eposta, @AktifMi);
END;
GO

CREATE PROCEDURE usp_Terapist_Guncelle
    @TerapistId INT,
    @Ad NVARCHAR(50),
    @Soyad NVARCHAR(50),
    @Uzmanlik NVARCHAR(100),
    @Telefon NVARCHAR(20),
    @Eposta NVARCHAR(100),
    @AktifMi BIT
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Terapistler
    SET Ad = @Ad,
        Soyad = @Soyad,
        Uzmanlik = @Uzmanlik,
        Telefon = @Telefon,
        Eposta = @Eposta,
        AktifMi = @AktifMi
    WHERE TerapistId = @TerapistId;
END;
GO

CREATE PROCEDURE usp_Terapist_Sil
    @TerapistId INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM Terapistler WHERE TerapistId = @TerapistId;
END;
GO

CREATE PROCEDURE usp_Terapist_Listele
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TerapistId, Ad, Soyad, Uzmanlik, Telefon, Eposta, AktifMi,
           dbo.fn_TerapistAylikSeansSayisi(TerapistId, YEAR(GETDATE()), MONTH(GETDATE())) AS BuAySeansSayisi
    FROM Terapistler
    ORDER BY TerapistId DESC;
END;
GO

CREATE PROCEDURE usp_SeansTuru_Ekle
    @TurAdi NVARCHAR(80),
    @SureDakika INT,
    @StandartUcret DECIMAL(10,2),
    @Aciklama NVARCHAR(250) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO SeansTurleri (TurAdi, SureDakika, StandartUcret, Aciklama)
    VALUES (@TurAdi, @SureDakika, @StandartUcret, @Aciklama);
END;
GO

CREATE PROCEDURE usp_SeansTuru_Guncelle
    @TurId INT,
    @TurAdi NVARCHAR(80),
    @SureDakika INT,
    @StandartUcret DECIMAL(10,2),
    @Aciklama NVARCHAR(250) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE SeansTurleri
    SET TurAdi = @TurAdi,
        SureDakika = @SureDakika,
        StandartUcret = @StandartUcret,
        Aciklama = @Aciklama
    WHERE TurId = @TurId;
END;
GO

CREATE PROCEDURE usp_SeansTuru_Sil
    @TurId INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM SeansTurleri WHERE TurId = @TurId;
END;
GO

CREATE PROCEDURE usp_SeansTuru_Listele
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TurId, TurAdi, SureDakika, StandartUcret, Aciklama
    FROM SeansTurleri
    ORDER BY TurId DESC;
END;
GO

CREATE PROCEDURE usp_Seans_Ekle
    @DanisanId INT,
    @TerapistId INT,
    @TurId INT,
    @SeansTarihi DATETIME2(0),
    @Durum NVARCHAR(20) = N'Planlandi',
    @Ucret DECIMAL(10,2) = NULL,
    @Aciklama NVARCHAR(250) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO Seanslar (DanisanId, TerapistId, TurId, SeansTarihi, Durum, Ucret, Aciklama)
    VALUES
    (
        @DanisanId,
        @TerapistId,
        @TurId,
        @SeansTarihi,
        @Durum,
        ISNULL(@Ucret, (SELECT StandartUcret FROM SeansTurleri WHERE TurId = @TurId)),
        @Aciklama
    );
END;
GO

CREATE PROCEDURE usp_Seans_Guncelle
    @SeansId INT,
    @DanisanId INT,
    @TerapistId INT,
    @TurId INT,
    @SeansTarihi DATETIME2(0),
    @Durum NVARCHAR(20),
    @Ucret DECIMAL(10,2),
    @Aciklama NVARCHAR(250) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Seanslar
    SET DanisanId = @DanisanId,
        TerapistId = @TerapistId,
        TurId = @TurId,
        SeansTarihi = @SeansTarihi,
        Durum = @Durum,
        Ucret = @Ucret,
        Aciklama = @Aciklama
    WHERE SeansId = @SeansId;
END;
GO

CREATE PROCEDURE usp_Seans_Sil
    @SeansId INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM Seanslar WHERE SeansId = @SeansId;
END;
GO

CREATE PROCEDURE usp_Seans_Listele
AS
BEGIN
    SET NOCOUNT ON;
    SELECT s.SeansId,
           s.DanisanId,
           CONCAT(d.Ad, N' ', d.Soyad) AS Danisan,
           s.TerapistId,
           CONCAT(t.Ad, N' ', t.Soyad) AS Terapist,
           s.TurId,
           st.TurAdi,
           s.SeansTarihi,
           s.Durum,
           s.Ucret,
           s.Aciklama
    FROM Seanslar s
    INNER JOIN Danisanlar d ON d.DanisanId = s.DanisanId
    INNER JOIN Terapistler t ON t.TerapistId = s.TerapistId
    INNER JOIN SeansTurleri st ON st.TurId = s.TurId
    ORDER BY s.SeansTarihi DESC;
END;
GO

CREATE PROCEDURE usp_Odeme_Ekle
    @SeansId INT,
    @Tutar DECIMAL(10,2),
    @OdemeTuru NVARCHAR(30),
    @Aciklama NVARCHAR(250) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO Odemeler (SeansId, Tutar, OdemeTuru, Aciklama)
    VALUES (@SeansId, @Tutar, @OdemeTuru, @Aciklama);
END;
GO

CREATE PROCEDURE usp_Odeme_Guncelle
    @OdemeId INT,
    @SeansId INT,
    @Tutar DECIMAL(10,2),
    @OdemeTuru NVARCHAR(30),
    @Aciklama NVARCHAR(250) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Odemeler
    SET SeansId = @SeansId,
        Tutar = @Tutar,
        OdemeTuru = @OdemeTuru,
        Aciklama = @Aciklama
    WHERE OdemeId = @OdemeId;
END;
GO

CREATE PROCEDURE usp_Odeme_Sil
    @OdemeId INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM Odemeler WHERE OdemeId = @OdemeId;
END;
GO

CREATE PROCEDURE usp_Odeme_Listele
AS
BEGIN
    SET NOCOUNT ON;
    SELECT o.OdemeId,
           o.SeansId,
           CONCAT(d.Ad, N' ', d.Soyad) AS Danisan,
           o.OdemeTarihi,
           o.Tutar,
           o.OdemeTuru,
           o.Aciklama
    FROM Odemeler o
    INNER JOIN Seanslar s ON s.SeansId = o.SeansId
    INNER JOIN Danisanlar d ON d.DanisanId = s.DanisanId
    ORDER BY o.OdemeTarihi DESC;
END;
GO

CREATE PROCEDURE usp_SeansNotu_Ekle
    @SeansId INT,
    @Baslik NVARCHAR(100),
    @NotMetni NVARCHAR(1000)
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO SeansNotlari (SeansId, Baslik, NotMetni)
    VALUES (@SeansId, @Baslik, @NotMetni);
END;
GO

CREATE PROCEDURE usp_SeansNotu_Guncelle
    @NotId INT,
    @SeansId INT,
    @Baslik NVARCHAR(100),
    @NotMetni NVARCHAR(1000)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE SeansNotlari
    SET SeansId = @SeansId,
        Baslik = @Baslik,
        NotMetni = @NotMetni
    WHERE NotId = @NotId;
END;
GO

CREATE PROCEDURE usp_SeansNotu_Sil
    @NotId INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM SeansNotlari WHERE NotId = @NotId;
END;
GO

CREATE PROCEDURE usp_SeansNotu_Listele
AS
BEGIN
    SET NOCOUNT ON;
    SELECT n.NotId,
           n.SeansId,
           CONCAT(d.Ad, N' ', d.Soyad) AS Danisan,
           n.NotTarihi,
           n.Baslik,
           n.NotMetni
    FROM SeansNotlari n
    INNER JOIN Seanslar s ON s.SeansId = n.SeansId
    INNER JOIN Danisanlar d ON d.DanisanId = s.DanisanId
    ORDER BY n.NotTarihi DESC;
END;
GO

CREATE PROCEDURE usp_Rapor_DanisanBakiye
    @DanisanId INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT dbo.fn_DanisanBakiye(@DanisanId) AS Bakiye;
END;
GO

CREATE PROCEDURE usp_Rapor_TerapistAylikSeans
    @TerapistId INT,
    @Yil INT,
    @Ay INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT dbo.fn_TerapistAylikSeansSayisi(@TerapistId, @Yil, @Ay) AS SeansSayisi;
END;
GO

EXEC usp_Danisan_Ekle N'Elif', N'Yilmaz', N'05320000001', N'elif.yilmaz@mail.com', '2001-04-12', N'Aktif';
EXEC usp_Danisan_Ekle N'Mert', N'Demir', N'05320000002', N'mert.demir@mail.com', '1998-11-03', N'Aktif';
EXEC usp_Danisan_Ekle N'Zeynep', N'Kaya', N'05320000003', N'zeynep.kaya@mail.com', '2003-02-20', N'Aktif';

EXEC usp_Terapist_Ekle N'Deniz', N'Arslan', N'Klinik Psikoloji', N'05330000001', N'deniz.arslan@klinik.com', 1;
EXEC usp_Terapist_Ekle N'Asli', N'Korkmaz', N'Cocuk ve Ergen Terapisi', N'05330000002', N'asli.korkmaz@klinik.com', 1;

EXEC usp_SeansTuru_Ekle N'Bireysel Terapi', 50, 1200.00, N'Yetiskin bireysel gorusme';
EXEC usp_SeansTuru_Ekle N'Cift Terapisi', 75, 1800.00, N'Ciftlerle yapilan seans';
EXEC usp_SeansTuru_Ekle N'Ergen Danismanligi', 50, 1300.00, N'Ergen danisan gorusmesi';

EXEC usp_Seans_Ekle 1, 1, 1, '2026-05-20T10:00:00', N'Planlandi', NULL, N'Ilk gorusme';
EXEC usp_Seans_Ekle 2, 1, 2, '2026-05-20T11:30:00', N'Tamamlandi', NULL, N'Duzenli takip';
EXEC usp_Seans_Ekle 3, 2, 3, '2026-05-21T14:00:00', N'Planlandi', NULL, N'Ergen danismanligi';

EXEC usp_Odeme_Ekle 2, 1800.00, N'Kredi Karti', N'Seans ucreti odendi';
EXEC usp_Odeme_Ekle 3, 800.00, N'Nakit', N'Kismi odeme';

EXEC usp_SeansNotu_Ekle 2, N'Seans ozeti', N'Danisanin duzenli takip sureci degerlendirildi.';
EXEC usp_SeansNotu_Ekle 3, N'On gorusme', N'Aile ile birlikte ilk hedefler belirlendi.';
GO

EXEC usp_Danisan_Listele;
EXEC usp_Terapist_Listele;
EXEC usp_Seans_Listele;
EXEC usp_Odeme_Listele;
EXEC usp_SeansNotu_Listele;
GO
