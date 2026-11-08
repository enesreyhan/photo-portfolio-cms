<?php
/**
 * includes/legal_defaults.php
 * Türkiye Cumhuriyeti 6698 Sayılı KVKK Uyumlu Standart Metin Şablonları (Zengin HTML Formatı)
 * Şablondaki örnek ad/alan adı/e-posta, site sahibinin bilgileriyle değiştirilir (legal_personalize).
 */

function legal_personalize(string $html): string {
    $host  = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? '') ?: 'example.com';
    $email = get_setting('contact_email');
    return strtr($html, [
        '{url}' => e(BASE_URL),
        '{email}' => e($email),
        '{email_text}' => e($email !== '' ? $email : '—'),
        '{host}' => e($host),
        '{owner}' => e(site_owner_name()),
    ]);
}

function get_default_kvkk_aydinlatma_tr(): string {
    return legal_personalize(<<<'HTML'
<h1>Kişisel Verilerin Korunması ve İşlenmesi Aydınlatma Metni</h1>
<p>Bu Aydınlatma Metni, 6698 sayılı <strong>Kişisel Verilerin Korunması Kanunu (“KVKK”)</strong> ve Aydınlatma Yükümlülüğünün Yerine Getirilmesinde Uyulacak Usul ve Esaslar Hakkında Tebliğ uyarınca; veri sorumlusu sıfatıyla <strong>{owner}</strong> (“Veri Sorumlusu”) tarafından işletilen <strong>{host}</strong> web sitesini ziyaret eden kullanıcıların, iş birliği yapılan tarafların, editoryal/belgesel çalışmalarda yer alan kişilerin ve iletişim kanallarımız üzerinden bizimle irtibata geçen ilgili kişilerin kişisel verilerinin işlenmesine ilişkin usul ve esasları açıklamak amacıyla hazırlanmıştır.</p>
<hr />
<h3>1. Veri Sorumlusunun Kimliği</h3>
<ul>
<li><strong>Veri Sorumlusu:</strong> {owner}</li>
<li><strong>Web Sitesi:</strong> <a href="{url}">{host}</a></li>
<li><strong>İletişim E-Posta:</strong> <a href="mailto:{email}">{email_text}</a></li>
</ul>
<hr />
<h3>2. İşlenen Kişisel Verileriniz ve Toplama Kanalları</h3>
<p>Web sitemiz ve iletişim kanallarımız aracılığıyla aşağıdaki kişisel veri kategorileri toplanabilmektedir:</p>
<ul>
<li><strong>Kimlik ve İletişim Verileri:</strong> Web sitesi üzerindeki formlar, e-posta iletileri veya sosyal medya kanalları üzerinden paylaştığınız ad, soyad ve e-posta adresiniz.</li>
<li><strong>Görsel ve Editoryal Veriler:</strong> Belgesel fotoğrafçılık, saha çalışmaları, atölyeler ve sanat projeleri kapsamında yürütülen bağımsız editoryal çalışmalar sırasında kamusal alanda veya izne tabi sahalarda çekilen fotoğraflar ve video kayıtları.</li>
<li><strong>İşlem Güvenliği ve Dijital İz Verileri:</strong> Web sitemizi ziyaretiniz esnasında otomatik olarak kaydedilen IP adresi, erişim tarih ve saatleri, tarayıcı tipi, yönlendiren sayfa URL’leri ve çerez (cookie) verileri.</li>
<li><strong>Talepler ve İleti İçeriği:</strong> Lisanslama, baskı siparişi, sergi katılımı, basın kiti talebi veya iş birliği amacıyla ilettiğiniz mesaj metinleri ve proje detayları.</li>
</ul>
<hr />
<h3>3. Kişisel Verilerin İşlenme Amaçları</h3>
<p>Kişisel verileriniz aşağıdaki amaçlar doğrultusunda işlenmektedir:</p>
<ol>
<li><strong>İletişim ve Taleplerin Yanıtlanması:</strong> Tarafınızca iletilen editoryal lisanslama, yüksek çözünürlüklü baskı alımı, atölye veya proje iş birliği taleplerinin değerlendirilmesi ve sizinle iletişime geçilmesi.</li>
<li><strong>Belgesel ve Sanat Faaliyetlerinin Yürütülmesi:</strong> Fotoğrafik ve editoryal projelerin belgelenmesi, telif hakları çerçevesinde portfolyo ve sergilerde sanatsal/haber amaçlı sunumu.</li>
<li><strong>Web Sitesi Güvenliği ve Altyapı Yönetimi:</strong> Sitenin siber saldırılara, kötüye kullanımlara karşı korunması ve teknik stabilitesinin sağlanması.</li>
<li><strong>Ziyaretçi Analitiği ve Deneyim İyileştirme:</strong> Google Analytics altyapısı ile IP adresleri anonimleştirilerek genel ziyaretçi trafiğinin ve en çok ilgi gören çalışmaların istatistiki olarak ölçülmesi.</li>
<li><strong>Yasal Yükümlülükler:</strong> 5651 sayılı İnternet Ortamında Yapılan Yayınların Düzenlenmesi Hakkında Kanun ve ilgili mevzuat uyarınca trafik kayıtlarının (log) tutulması.</li>
</ol>
<hr />
<h3>4. Kişisel Veri Toplamanın Hukuki Sebepleri</h3>
<p>Kişisel verileriniz, KVKK’nın 5. maddesinde belirtilen aşağıdaki hukuki sebeplere dayanılarak işlenmektedir:</p>
<ul>
<li><strong>Sözleşmenin Kurulması veya İfası (KVKK m.5/2-c):</strong> Editoryal lisans, baskı alımı veya fotoğraf çekim anlaşmalarına ilişkin ön görüşmelerin ve sözleşme süreçlerinin yürütülmesi.</li>
<li><strong>Veri Sorumlusunun Hukuki Yükümlülüğü (KVKK m.5/2-ç):</strong> 5651 sayılı kanun kapsamındaki erişim loglarının tutulması gibi mevzuattan doğan yasal gereklilikler.</li>
<li><strong>Veri Sorumlusunun Meşru Menfaati (KVKK m.5/2-f):</strong> Temel hak ve özgürlüklerinize zarar vermemek kaydıyla, web sitesinin işlevselliğinin, güvenliğinin ve sanatsal portfolyo sunumunun sürdürülebilmesi.</li>
<li><strong>Açık Rıza (KVKK m.5/1):</strong> Kanuni istisnaların uygulanmadığı hallerde; iletişim formlarının gönderimi veya çerezler üzerinden analiz yapılmasına dair özgür iradenizle verdiğiniz onay.</li>
</ul>
<hr />
<h3>5. Kişisel Verilerin Aktarılması</h3>
<p>Kişisel verileriniz, kural olarak üçüncü taraflarla ticari veya pazarlama amaçlı <strong>paylaşılmaz ve satılmaz</strong>. Ancak aşağıdaki zorunlu hallerde aktarım söz konusu olabilir:</p>
<ul>
<li><strong>Teknik Altyapı ve Barındırma Sağlayıcıları:</strong> Web sitesi sunucu altyapısını sağlayan hosting firması ve e-posta sunucuları.</li>
<li><strong>Analitik Sağlayıcılar:</strong> Ziyaretçi eğilimlerini anonim olarak analiz etmek amacıyla Google LLC (Google Analytics).</li>
<li><strong>Yetkili Kamu Kurum ve Kuruluşları:</strong> Yasal bir zorunluluk veya adli süreç söz konusu olduğunda kanunen yetkili kılınmış adli/idari makamlar.</li>
</ul>
<hr />
<h3>6. İlgili Kişi Olarak Haklarınız (KVKK Madde 11)</h3>
<p>KVKK’nın 11. maddesi uyarınca veri sahibi olarak şu haklara sahipsiniz:</p>
<ol>
<li>Kişisel verilerinizin işlenip işlenmediğini öğrenme,</li>
<li>Kişisel verileriniz işlenmişse buna ilişkin bilgi talep etme,</li>
<li>Kişisel verilerinizin işlenme amacını ve bunların amacına uygun kullanılıp kullanılmadığını öğrenme,</li>
<li>Yurt içinde veya yurt dışında kişisel verilerinizin aktarıldığı üçüncü kişileri bilme,</li>
<li>Kişisel verilerinizin eksik veya yanlış işlenmiş olması hâlinde bunların düzeltilmesini isteme,</li>
<li>KVKK m. 7’de öngörülen şartlar çerçevesinde kişisel verilerinizin silinmesini veya yok edilmesini isteme,</li>
<li>Düzeltme, silme ve yok edilme işlemlerinin verilerin aktarıldığı üçüncü kişilere bildirilmesini isteme,</li>
<li>İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme,</li>
<li>Kişisel verilerinizin kanuna aykırı olarak işlenmesi sebebiyle zarara uğramanız hâlinde zararın giderilmesini talep etme.</li>
</ol>
<hr />
<h3>7. Başvuru ve İletişim</h3>
<p>Haklarınıza ilişkin taleplerinizi kimliğinizi tevsik eden belgelerle birlikte Veri Sorumlusu <strong>{owner}</strong>'a ait <strong><a href="mailto:{email}">{email_text}</a></strong> e-posta adresine yazılı olarak iletebilirsiniz. Talebiniz niteliğine göre en kısa sürede ve en geç <strong>30 (otuz) gün</strong> içinde ücretsiz olarak sonuçlandırılacaktır.</p>
HTML);
}

function get_default_kvkk_acik_riza_tr(): string {
    return legal_personalize(<<<'HTML'
<h1>Kişisel Verilerin İşlenmesine İlişkin Açık Rıza Metni</h1>
<p>Bu Açık Rıza Metni, 6698 sayılı <strong>Kişisel Verilerin Korunması Kanunu (“KVKK”)</strong> uyarınca; <strong>{host}</strong> web sitesinde yer alan <a href="aydinlatma-metni">Aydınlatma Metni</a> ile bilgilendirilmiş olan ziyaretçilerin ve ilgililerin, kanunun 5. maddesinin 1. fıkrası kapsamında açık rıza gerektiren kişisel veri işleme ve aktarım faaliyetlerine ilişkin serbest iradeleriyle onay vermelerini sağlamak amacıyla hazırlanmıştır.</p>
<hr />
<h3>1. Açık Rıza Kapsamında İşlenen Veriler ve Amaçlar</h3>
<p>Aydınlatma Metni’nde ayrıntılı olarak açıklanan ilkeler doğrultusunda;</p>
<ul>
<li><strong>İletişim ve Proje Süreçleri:</strong> Web sitesindeki iletişim kanalları veya doğrudan e-posta vasıtasıyla şahsımla paylaştığım kimlik ve iletişim bilgilerimin (ad, soyad, e-posta, telefon, mesaj içeriği); fotoğraf çekim projeleri, editoryal lisanslama, sergi, baskı alımı ve iş birliği süreçlerinin yürütülmesi, tarafıma geri dönüş sağlanması ve gerekli bilgilendirmelerin yapılması amacıyla işlenmesine,</li>
<li><strong>Editoryal ve Belgesel Görsel Kayıtlar:</strong> {owner} tarafından icra edilen belgesel ve sanatsal fotoğrafçılık projeleri kapsamında elde edilen görsel ve editoryal materyallerin; sanatçının portfolyosunda, basılı/dijital sergilerde, editoryal yayınlarda ve <strong>{host}</strong> platformunda telif ve basın etiği ilkeleri gözetilerek kamuya sunulmasına ve arşivlenmesine,</li>
<li><strong>Analitik ve Web Deneyimi:</strong> Web sitesinin ziyaretçi deneyimini analiz etmek ve performansını optimize etmek üzere kullanılan anonim çerezler ve analitik araçları (Google Analytics vb.) vasıtasıyla elde edilen dijital kullanım verilerinin işlenmesine,</li>
</ul>
<p>özgür irademle, açıkça ve tereddüde mahal bırakmayacak şekilde <strong>onay veriyorum</strong>.</p>
<hr />
<h3>2. Yurt Dışına Veri Aktarımı</h3>
<p>Web sitesinin teknik altyapısı gereği; kullanılan e-posta sunucuları, hosting sağlayıcıları ve ziyaretçi trafik analitiğini sağlayan uluslararası servis sağlayıcıların (Google vb.) veri merkezlerinin yurt dışında bulunabilmesi sebebiyle, yukarıda belirtilen kişisel verilerimin bu kapsamda yurt dışındaki güvenli sunuculara aktarılmasına açık rıza gösteriyorum.</p>
<hr />
<h3>3. Açık Rızanın Geri Alınması</h3>
<p>KVKK uyarınca vermiş olduğum bu açık rızayı; hiçbir gerekçe göstermeksizin, dilediğim zaman Veri Sorumlusu {owner}’a <strong><a href="mailto:{email}">{email_text}</a></strong> adresi üzerinden yazılı bildirimde bulunarak kısmen veya tamamen <strong>geri alma hakkına</strong> sahip olduğumu biliyorum. Açık rızanın geri alınması, geri alma beyanının veri sorumlusuna ulaştığı andan itibaren hüküm doğuracak olup, bu tarihe kadar rıza kapsamında gerçekleştirilmiş işleme faaliyetlerinin hukuka uygunluğunu etkilemez.</p>
HTML);
}

function get_default_kvkk_aydinlatma_en(): string {
    return legal_personalize(<<<'HTML'
<h1>Personal Data Protection and Privacy Notice (KVKK)</h1>
<p>This Clarification Notice has been prepared in accordance with the Law No. 6698 on the <strong>Protection of Personal Data (“KVKK”)</strong> and the Communiqué on the Principles and Procedures to be Followed in Fulfillment of the Obligation to Inform, to outline the procedures and principles regarding the processing of personal data by <strong>{owner}</strong> (“Data Controller”) through the website <strong>{host}</strong>.</p>
<hr />
<h3>1. Identity of the Data Controller</h3>
<ul>
<li><strong>Data Controller:</strong> {owner}</li>
<li><strong>Website:</strong> <a href="{url}">{host}</a></li>
<li><strong>Contact Email:</strong> <a href="mailto:{email}">{email_text}</a></li>
</ul>
<hr />
<h3>2. Categories of Processed Personal Data</h3>
<ul>
<li><strong>Identity and Contact Data:</strong> Name, surname, and email address submitted via contact forms, direct email, or project inquiries.</li>
<li><strong>Visual and Editorial Data:</strong> Photographic and video recordings produced within independent editorial, documentary, or artistic projects in public spaces or authorized areas.</li>
<li><strong>Technical and Browsing Data:</strong> IP addresses, log files, visit timestamps, browser types, and anonymous analytics cookies (Google Analytics).</li>
<li><strong>Inquiry and Request Data:</strong> Editorial licensing inquiries, print purchase requests, press kit demands, or collaboration proposals.</li>
</ul>
<hr />
<h3>3. Purposes of Data Processing</h3>
<p>Your personal data is processed for the following purposes:</p>
<ol>
<li>Responding to print inquiries, licensing proposals, and editorial collaborations.</li>
<li>Documenting and publishing photographic and editorial monographs under copyright and press ethics principles.</li>
<li>Ensuring website cybersecurity, technical stability, and performance.</li>
<li>Analyzing anonymized traffic trends through Google Analytics to improve visitor experience.</li>
<li>Complying with statutory retention obligations pursuant to Law No. 5651.</li>
</ol>
<hr />
<h3>4. Legal Grounds for Processing</h3>
<p>Under Article 5 of the KVKK, your data is processed based on:</p>
<ul>
<li><strong>Establishment or Performance of a Contract (Art. 5/2-c):</strong> Execution of licensing, print acquisition, or project agreements.</li>
<li><strong>Legal Obligation (Art. 5/2-ç):</strong> Preservation of web traffic logs as mandated by Turkish law.</li>
<li><strong>Legitimate Interests (Art. 5/2-f):</strong> Technical operation and security of the website without infringing upon your fundamental rights.</li>
<li><strong>Explicit Consent (Art. 5/1):</strong> Freely given affirmative consent when submitting contact forms or accepting analytics cookies.</li>
</ul>
<hr />
<h3>5. Transfer of Personal Data</h3>
<p>Personal data is <strong>never sold or rented</strong> to third parties for commercial or advertising purposes. Data may only be shared with:</p>
<ul>
<li>Secure cloud infrastructure, hosting, and email providers.</li>
<li>Anonymized web analytics services (Google LLC).</li>
<li>Authorized judicial or public authorities upon lawful request.</li>
</ul>
<hr />
<h3>6. Rights of the Data Subject (KVKK Article 11)</h3>
<p>Pursuant to Article 11 of the KVKK, you are entitled to:</p>
<ol>
<li>Inquire whether your personal data is processed,</li>
<li>Request information regarding the processing if it has taken place,</li>
<li>Learn the purpose of processing and verify adherence to that purpose,</li>
<li>Know the third parties to whom data is transferred domestically or internationally,</li>
<li>Request correction of incomplete or inaccurate data,</li>
<li>Request erasure or destruction of data under statutory conditions,</li>
<li>Object to adverse outcomes resulting exclusively from automated processing,</li>
<li>Claim compensation for damages incurred due to unlawful processing.</li>
</ol>
<hr />
<h3>7. Applications and Inquiries</h3>
<p>You may exercise your rights by submitting a written request accompanied by verification of your identity to <strong><a href="mailto:{email}">{email_text}</a></strong>. Inquiries will be resolved free of charge within <strong>30 days</strong> at the latest.</p>
HTML);
}

function get_default_kvkk_acik_riza_en(): string {
    return legal_personalize(<<<'HTML'
<h1>Explicit Consent Statement on Personal Data Processing</h1>
<p>This Explicit Consent Statement is drafted pursuant to Law No. 6698 on the <strong>Protection of Personal Data (“KVKK”)</strong> for visitors and clients of <strong>{host}</strong> who have been informed via the <a href="aydinlatma-metni">Privacy &amp; Clarification Notice</a>, regarding personal data processing and cross-border data transfer activities requiring explicit consent under Article 5(1) of the KVKK.</p>
<hr />
<h3>1. Scope of Explicit Consent</h3>
<p>In accordance with the principles set forth in the Clarification Notice, I freely, explicitly, and unequivocally grant my consent to:</p>
<ul>
<li><strong>Inquiry and Project Communications:</strong> Processing of my identity and contact information (name, email address, message details) submitted via online inquiry forms or email, for evaluating print purchases, editorial licensing, exhibition inquiries, and coordinating collaborations.</li>
<li><strong>Editorial &amp; Documentary Archives:</strong> Archiving, presentation, and publication of photographic materials captured during documentary projects across {owner}'s portfolio, digital/print exhibitions, and website (<strong>{host}</strong>) in accordance with press ethics and artistic copyrights.</li>
<li><strong>Digital Analytics &amp; Web Experience:</strong> Processing of anonymized browsing data and session cookies via Google Analytics for technical optimization.</li>
</ul>
<hr />
<h3>2. Cross-Border Data Transfer</h3>
<p>I explicitly acknowledge and consent that, due to the global infrastructure of email servers, web hosting providers, and international analytical services (e.g., Google), the aforementioned personal data may be processed and stored on secure data centers located outside Turkey.</p>
<hr />
<h3>3. Revocation of Consent</h3>
<p>I understand that I may withdraw this consent partially or entirely at any time without stating any reason, by submitting a written notice to Data Controller {owner} at <strong><a href="mailto:{email}">{email_text}</a></strong>. Revocation takes effect upon receipt and does not affect the lawfulness of processing carried out prior to that date.</p>
HTML);
}

/**
 * Verilen tip ve dile ait yasal metin içeriğini getirir (DB'den veya varsayılandan)
 */
function get_legal_content(string $type, string $lang = 'tr'): string {
    $saved = (string)get_setting("kvkk_{$type}_metni_{$lang}", '');
    if (trim($saved) !== '') return $saved;
    // Türkçe ve İngilizcenin hazır metni var; diğer dillerde yedek dildeki kayıtlı metin kullanılır
    if (!in_array($lang, ['tr', 'en'], true)) {
        foreach (array_slice(lang_chain($lang), 1) as $c) {
            $saved = (string)get_setting("kvkk_{$type}_metni_{$c}", '');
            if (trim($saved) !== '') return $saved;
        }
    }

    if ($type === 'acik_riza') {
        return ($lang === 'tr') ? get_default_kvkk_acik_riza_tr() : get_default_kvkk_acik_riza_en();
    }
    return ($lang === 'tr') ? get_default_kvkk_aydinlatma_tr() : get_default_kvkk_aydinlatma_en();
}
