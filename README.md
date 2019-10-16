# Link Checker
<img src="https://img.shields.io/badge/dle-13.3-007dad.svg"> <img src="https://img.shields.io/badge/lang-tr,en,ru-ce600f.svg"> <img src="https://img.shields.io/badge/license-MIT-60ce0f.svg">

Link Checker ile ilave alanlara girdiğiniz linkleri veya kodları, cron ile belirlediğiniz gün veya zaman aralığında otomatik olarak yaptırabilirsiniz. Kontrol sonrasında çalışmayan linkler kaydedilecek ve admin panelinden takip edilebilecektir. Ayrıca hata alınan her linkin son kontrol tarihini ve kaç kez kontrol edildiği bilgisi de mevcuttur. Aynı zamanda belirlediğiniz şablon ile kırık link bulunan makalenin yazarına tek tıklama ile bildirim gönderebilirsiniz.

## Kurulum
**1)** Eklentiyi zip olarak admin panelden  yükleyebilirsiniz. Fakat DLE eklenti sistemindeki yükleme alanı  Github'dan indirilen ziplerin direkt olarak yüklenmesi için uygun değil. Bu nedenle zip olarak modülü indirip, modülü bir dizine çıkartıp. XML  dosyasının bulunduğu dizindeki tüm dosyaları seçerek zip oluşturun.

**2)** Ardından eklentiyi zip dosyası sitenize yükleyin.



## Konfigürasyon
Eğer ilave alanınıza direkt link giriyorsanız, URL seçeneğini seçin.
Eğer dosya ID, video kodu vb. kısa kodlar giriyorsanız, yazacağınız url şablonu ile sistemin bunu direkt link olarak algılamasını sağlayabilirsiniz.

Ayrıca istediğiniz her site ile kullanabilirsiniz. Esnek yapısı sayesinde herhangi bir düzenleme yapmanıza gerek yoktur.
Tek yapmanız gereken, o site için hatalı linkte nasıl bir sonuç verdiğine bakmaktır. Örneğin: youtube da silinmiş bir video adresine tıkladığınızda sayfada yazan "Not found", "Video Silinmiş" vb. yazıları ayarlara kaydetmeniz gereklidir. Yani kontrol esnasında sayfaya bakıldığında, eğer yazdığınız yazıların herhangi birini içeriyorsa otomatik olarak "Kırık Link" olarak raporlara eklenecektir.

Cron scripti 4 fonksiyona sahiptir, 2 fonksiyonu için kontrol panelinizden cron tanımlaması yapabilirsiniz. Aynı zamanda bu özellikleri URL ile de çalıştırabilirsiniz.

**1.** Ayarlardan seçilmiş alanlara ait tüm URL leri bir dosyada toplar. Daha önceden eklemiş olanları es geçerek, sadece yeni URL leri listeye ekler.
Dosya, engine/data/linkchecker.db dizininde oluşturulur. Ayracı | karakteri olan bir CSV dosyasıdır. Metin editörü ile açılabilir.

Komut:
```bash
php -f cron.linkchecker.php generate
```

URL:

`http://siteniz.com/cron.linkchecker.php?mode=generate`


**2.** Periyodik olarak çalıştırılarak link kontrolü yapma. Her URL için max. bekleme süresini ve tek çalıştırmada hak adet URL nin kontrol edileceğini admin panelden belirleyebilirsiniz.
404 ile dönen sayfalar hatalı olarak algılanır ve panelde görebileceğiniz şekilde eklenir, sonraki çalışmalarda es geçilir. Eğer hedef siteden olumlu yanıt dönerse 200 gibi, sayfa kaynağında belirlenen yazının bulunması şart koşulur.
Bu şartları, admin panelden belirleyebilirsiniz. Aynı zamanda hatalı görünmeyen ve hata alınan URL leri "test" fonksiyonu ile test edip, şartı belirleyebilirsiniz.

Komut:
```
php -f cron.linkchecker.php check
```

URL:

`http://siteniz.com/cron.linkchecker.php?mode=check`

**3.** URL ye ait kural yazmak için test yapma
Burada url parametresi için yazacağını URL ler için, durum bilgileri ve sayfa içeriği döner.
Sayfa kaynağına göre kuralınızı/şartınızı admin panele yazabilirsiniz.

URL:

`http://siteniz.com/cron.linkchecker.php?mode=test&url=http://site.net/dosya.html`


**4.** URL listesini ve cron çalışma geçmişini silme
Generate fonksiyonu ile kaydedilen tüm linkleri ve cron çalışma geçmişini silmek için kullanabilirsiniz. Bu özellik sadece adres satırından çalışmaktadır ve DEBUG modu açık olması gerekir.
Başkaları tarafından çalıştırılmasını önlemek için dosya içinden bir şifre belirleyebilirsiniz.
Yeni kurallar ekledikten bu işlemi yapmanız gerekir. Fakat sık olmamasına dikkat ediniz. Her site için bir sağlam bir de kırık URL leri test kısmında deneyerek kuralları en başta belirlemeniz tavsite edilir.

URL:

`http://siteniz.com/cron.linkchecker.php?mode=refresh&pass=123456`


**5.** cron.linkchecker.php Dosyasından değiştirebileceğiniz değerler.

```php
define('DEBUG', 1); // Debug modu açıkken adres satırından erişebilir
```

ve tüm çıktıları görebilirsiniz.

```php
define('PASSWORD', "123456"); // Modül veri dosyalarını silmek için gerekli şifredir
```

```php
define('ACTIVE', 1); // Modülün cron işlevini aktifleştir-pasifleştir
```

işlemini buradan yapabilirsiniz.

**NOT:** Güvenliğiniz için cron.linkchecker.php dosyasının ismini ve PASSWORD bilgisini değiştiriniz.


## Ekran Görüntüleri
![Ekran 1](./docs/screen1.png?raw=true)
![Ekran 2](./docs/screen2.png?raw=true)
![Ekran 3](./docs/screen3.png?raw=true)

## Tarihçe
| Version | Tarih | Uyumluluk | Yenilikler |
| ------- | ----- | --------- | ---------- |
| **1.4** | 17.10.2019 | 13.0+ | Yeni DLE sürümleri ile uyumlu hale getirildi. |
| **1.3** | 09.03.2018 | 12.0, 12.1 | DataList modülü ile uyumlu hale getirildi. |
| **1.2** | 24.01.2018 | 12.0, 12.1 | URL kontrolü tamamen CURL ile yapılacak şekilde ayarlandı.<br>Cron scripti yeniden yazıldı ve test yapılabilecek sistem eklendi.<br>Her sorgu için max kaç sn bekleneceği belirlenebilir yapıldı.<br>Her çalışmada kaç URL işleme alınacağı belirlenebilir yapıldı.<br>10.2 ve öncesine ait destek yeni sürümle birlikte kaldırıldı.<br>Video part için part silme / yazı ile değiştirme kaldırıldı. Yerine admine panele kayıt eklenecek. |
| **1.1** | 26.12.2017 | 12.0, 12.1 |  |
| **1.0** | 12.02.2015 | 9.x, 10.x | İlave alanlar ile kullanabilme<br>Geniş sürüm uyumluluğu.<br>CronJob ile çalışabilme.<br>Admin panelden ayar yapabileme ve AJAX ile hızlı işlemler. |