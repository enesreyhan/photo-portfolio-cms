<?php
/**
 * includes/setup_i18n.php
 * İlk kurulum sihirbazının (admin/login.php) metinleri. Eksik anahtar İngilizceye düşer.
 */

function setup_strings(): array {
    return [
        'tr' => [
            'choose' => 'Kurulum dilini seçin', 'choose_sub' => 'Yönetim paneli bu dilde kurulacak',
            'welcome' => 'Hoş geldiniz', 'sub' => 'İlk yönetici hesabınızı oluşturun', 'title' => 'Kurulum',
            'username' => 'Kullanıcı adı', 'owner' => 'Ad soyad / Site sahibi', 'owner_ph' => 'Telif ve künye metinlerinde görünür',
            'email' => 'E-posta', 'password' => 'Parola', 'password2' => 'Parola (tekrar)', 'submit' => 'Hesabı oluştur',
            'change_lang' => 'Dili değiştir', 'back' => 'Web sitesine dön',
            'err_csrf' => 'Güvenlik doğrulaması zaman aşımına uğradı, lütfen tekrar deneyin.',
            'err_user' => 'Kullanıcı adı 3-50 karakter olmalı (harf, rakam, _ . -).',
            'err_pw' => 'Parola en az 10 karakter olmalı.', 'err_match' => 'Parolalar eşleşmiyor.',
            'err_email' => 'Geçerli bir e-posta adresi girin.', 'err_exists' => 'Yönetici hesabı zaten oluşturulmuş. Lütfen giriş yapın.',
        ],
        'en' => [
            'choose' => 'Choose the setup language', 'choose_sub' => 'The admin panel will be set up in this language',
            'welcome' => 'Welcome', 'sub' => 'Create your first administrator account', 'title' => 'Setup',
            'username' => 'Username', 'owner' => 'Full name / Site owner', 'owner_ph' => 'Shown in copyright and legal notices',
            'email' => 'Email', 'password' => 'Password', 'password2' => 'Password (again)', 'submit' => 'Create account',
            'change_lang' => 'Change language', 'back' => 'Back to website',
            'err_csrf' => 'The security check expired, please try again.',
            'err_user' => 'Username must be 3-50 characters (letters, digits, _ . -).',
            'err_pw' => 'Password must be at least 10 characters.', 'err_match' => 'Passwords do not match.',
            'err_email' => 'Enter a valid email address.', 'err_exists' => 'An administrator account already exists. Please sign in.',
        ],
        'de' => [
            'choose' => 'Installationssprache wählen', 'choose_sub' => 'Das Admin-Panel wird in dieser Sprache eingerichtet',
            'welcome' => 'Willkommen', 'sub' => 'Erstellen Sie Ihr erstes Administratorkonto', 'title' => 'Einrichtung',
            'username' => 'Benutzername', 'owner' => 'Vollständiger Name / Websiteinhaber', 'owner_ph' => 'Erscheint in Urheberrechts- und Impressumshinweisen',
            'email' => 'E-Mail', 'password' => 'Passwort', 'password2' => 'Passwort (wiederholen)', 'submit' => 'Konto erstellen',
            'change_lang' => 'Sprache ändern', 'back' => 'Zurück zur Website',
            'err_csrf' => 'Die Sicherheitsprüfung ist abgelaufen, bitte erneut versuchen.',
            'err_user' => 'Der Benutzername muss 3-50 Zeichen lang sein (Buchstaben, Ziffern, _ . -).',
            'err_pw' => 'Das Passwort muss mindestens 10 Zeichen lang sein.', 'err_match' => 'Die Passwörter stimmen nicht überein.',
            'err_email' => 'Geben Sie eine gültige E-Mail-Adresse ein.', 'err_exists' => 'Es gibt bereits ein Administratorkonto. Bitte melden Sie sich an.',
        ],
        'fr' => [
            'choose' => 'Choisissez la langue d’installation', 'choose_sub' => 'Le panneau d’administration sera configuré dans cette langue',
            'welcome' => 'Bienvenue', 'sub' => 'Créez votre premier compte administrateur', 'title' => 'Installation',
            'username' => 'Nom d’utilisateur', 'owner' => 'Nom complet / Propriétaire du site', 'owner_ph' => 'Affiché dans les mentions de droits et légales',
            'email' => 'E-mail', 'password' => 'Mot de passe', 'password2' => 'Mot de passe (confirmation)', 'submit' => 'Créer le compte',
            'change_lang' => 'Changer de langue', 'back' => 'Retour au site',
            'err_csrf' => 'La vérification de sécurité a expiré, veuillez réessayer.',
            'err_user' => 'Le nom d’utilisateur doit comporter 3 à 50 caractères (lettres, chiffres, _ . -).',
            'err_pw' => 'Le mot de passe doit comporter au moins 10 caractères.', 'err_match' => 'Les mots de passe ne correspondent pas.',
            'err_email' => 'Saisissez une adresse e-mail valide.', 'err_exists' => 'Un compte administrateur existe déjà. Veuillez vous connecter.',
        ],
        'es' => [
            'choose' => 'Elige el idioma de instalación', 'choose_sub' => 'El panel de administración se configurará en este idioma',
            'welcome' => 'Bienvenido', 'sub' => 'Crea tu primera cuenta de administrador', 'title' => 'Instalación',
            'username' => 'Nombre de usuario', 'owner' => 'Nombre completo / Propietario del sitio', 'owner_ph' => 'Aparece en los avisos de derechos y legales',
            'email' => 'Correo electrónico', 'password' => 'Contraseña', 'password2' => 'Contraseña (repetir)', 'submit' => 'Crear cuenta',
            'change_lang' => 'Cambiar idioma', 'back' => 'Volver al sitio web',
            'err_csrf' => 'La verificación de seguridad caducó, inténtalo de nuevo.',
            'err_user' => 'El nombre de usuario debe tener entre 3 y 50 caracteres (letras, números, _ . -).',
            'err_pw' => 'La contraseña debe tener al menos 10 caracteres.', 'err_match' => 'Las contraseñas no coinciden.',
            'err_email' => 'Introduce un correo electrónico válido.', 'err_exists' => 'Ya existe una cuenta de administrador. Inicia sesión.',
        ],
        'it' => [
            'choose' => 'Scegli la lingua di installazione', 'choose_sub' => 'Il pannello di amministrazione verrà configurato in questa lingua',
            'welcome' => 'Benvenuto', 'sub' => 'Crea il tuo primo account amministratore', 'title' => 'Installazione',
            'username' => 'Nome utente', 'owner' => 'Nome completo / Proprietario del sito', 'owner_ph' => 'Mostrato nelle note di copyright e legali',
            'email' => 'E-mail', 'password' => 'Password', 'password2' => 'Password (di nuovo)', 'submit' => 'Crea account',
            'change_lang' => 'Cambia lingua', 'back' => 'Torna al sito',
            'err_csrf' => 'Il controllo di sicurezza è scaduto, riprova.',
            'err_user' => 'Il nome utente deve avere 3-50 caratteri (lettere, cifre, _ . -).',
            'err_pw' => 'La password deve avere almeno 10 caratteri.', 'err_match' => 'Le password non coincidono.',
            'err_email' => 'Inserisci un indirizzo e-mail valido.', 'err_exists' => 'Esiste già un account amministratore. Accedi.',
        ],
        'pt' => [
            'choose' => 'Escolha o idioma da instalação', 'choose_sub' => 'O painel de administração será configurado neste idioma',
            'welcome' => 'Bem-vindo', 'sub' => 'Crie a sua primeira conta de administrador', 'title' => 'Instalação',
            'username' => 'Nome de utilizador', 'owner' => 'Nome completo / Proprietário do site', 'owner_ph' => 'Aparece nos avisos de direitos e legais',
            'email' => 'E-mail', 'password' => 'Palavra-passe', 'password2' => 'Palavra-passe (repetir)', 'submit' => 'Criar conta',
            'change_lang' => 'Mudar idioma', 'back' => 'Voltar ao site',
            'err_csrf' => 'A verificação de segurança expirou, tente novamente.',
            'err_user' => 'O nome de utilizador deve ter 3 a 50 caracteres (letras, números, _ . -).',
            'err_pw' => 'A palavra-passe deve ter pelo menos 10 caracteres.', 'err_match' => 'As palavras-passe não coincidem.',
            'err_email' => 'Introduza um e-mail válido.', 'err_exists' => 'Já existe uma conta de administrador. Inicie sessão.',
        ],
        'ru' => [
            'choose' => 'Выберите язык установки', 'choose_sub' => 'Панель управления будет настроена на этом языке',
            'welcome' => 'Добро пожаловать', 'sub' => 'Создайте первую учётную запись администратора', 'title' => 'Установка',
            'username' => 'Имя пользователя', 'owner' => 'Полное имя / Владелец сайта', 'owner_ph' => 'Отображается в уведомлениях об авторских правах',
            'email' => 'Эл. почта', 'password' => 'Пароль', 'password2' => 'Пароль (ещё раз)', 'submit' => 'Создать учётную запись',
            'change_lang' => 'Сменить язык', 'back' => 'Вернуться на сайт',
            'err_csrf' => 'Срок проверки безопасности истёк, попробуйте ещё раз.',
            'err_user' => 'Имя пользователя: 3–50 символов (буквы, цифры, _ . -).',
            'err_pw' => 'Пароль должен содержать не менее 10 символов.', 'err_match' => 'Пароли не совпадают.',
            'err_email' => 'Введите корректный адрес эл. почты.', 'err_exists' => 'Учётная запись администратора уже существует. Войдите.',
        ],
        'el' => [
            'choose' => 'Επιλέξτε γλώσσα εγκατάστασης', 'choose_sub' => 'Ο πίνακας διαχείρισης θα ρυθμιστεί σε αυτή τη γλώσσα',
            'welcome' => 'Καλώς ήρθατε', 'sub' => 'Δημιουργήστε τον πρώτο λογαριασμό διαχειριστή', 'title' => 'Εγκατάσταση',
            'username' => 'Όνομα χρήστη', 'owner' => 'Ονοματεπώνυμο / Κάτοχος ιστότοπου', 'owner_ph' => 'Εμφανίζεται στις σημειώσεις πνευματικών δικαιωμάτων',
            'email' => 'Email', 'password' => 'Κωδικός', 'password2' => 'Κωδικός (ξανά)', 'submit' => 'Δημιουργία λογαριασμού',
            'change_lang' => 'Αλλαγή γλώσσας', 'back' => 'Επιστροφή στον ιστότοπο',
            'err_csrf' => 'Ο έλεγχος ασφαλείας έληξε, δοκιμάστε ξανά.',
            'err_user' => 'Το όνομα χρήστη πρέπει να έχει 3-50 χαρακτήρες (γράμματα, ψηφία, _ . -).',
            'err_pw' => 'Ο κωδικός πρέπει να έχει τουλάχιστον 10 χαρακτήρες.', 'err_match' => 'Οι κωδικοί δεν ταιριάζουν.',
            'err_email' => 'Εισαγάγετε έγκυρο email.', 'err_exists' => 'Υπάρχει ήδη λογαριασμός διαχειριστή. Συνδεθείτε.',
        ],
        'hy' => [
            'choose' => 'Ընտրեք տեղադրման լեզուն', 'choose_sub' => 'Կառավարման վահանակը կկարգավորվի այս լեզվով',
            'welcome' => 'Բարի գալուստ', 'sub' => 'Ստեղծեք ադմինիստրատորի առաջին հաշիվը', 'title' => 'Տեղադրում',
            'username' => 'Օգտանուն', 'owner' => 'Անուն ազգանուն / Կայքի սեփականատեր', 'owner_ph' => 'Ցուցադրվում է հեղինակային իրավունքի նշումներում',
            'email' => 'Էլ. փոստ', 'password' => 'Գաղտնաբառ', 'password2' => 'Գաղտնաբառ (կրկին)', 'submit' => 'Ստեղծել հաշիվ',
            'change_lang' => 'Փոխել լեզուն', 'back' => 'Վերադառնալ կայք',
            'err_csrf' => 'Անվտանգության ստուգման ժամկետը լրացել է, փորձեք կրկին։',
            'err_user' => 'Օգտանունը պետք է լինի 3-50 նիշ (տառեր, թվեր, _ . -)։',
            'err_pw' => 'Գաղտնաբառը պետք է լինի առնվազն 10 նիշ։', 'err_match' => 'Գաղտնաբառերը չեն համընկնում։',
            'err_email' => 'Մուտքագրեք վավեր էլ. հասցե։', 'err_exists' => 'Ադմինիստրատորի հաշիվն արդեն կա։ Խնդրում ենք մուտք գործել։',
        ],
        'ar' => [
            'choose' => 'اختر لغة التثبيت', 'choose_sub' => 'سيتم إعداد لوحة التحكم بهذه اللغة',
            'welcome' => 'مرحبًا', 'sub' => 'أنشئ أول حساب مسؤول', 'title' => 'التثبيت',
            'username' => 'اسم المستخدم', 'owner' => 'الاسم الكامل / مالك الموقع', 'owner_ph' => 'يظهر في إشعارات حقوق النشر والبيانات القانونية',
            'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور', 'password2' => 'كلمة المرور (مرة أخرى)', 'submit' => 'إنشاء الحساب',
            'change_lang' => 'تغيير اللغة', 'back' => 'العودة إلى الموقع',
            'err_csrf' => 'انتهت صلاحية التحقق الأمني، يرجى المحاولة مرة أخرى.',
            'err_user' => 'يجب أن يتكون اسم المستخدم من 3 إلى 50 حرفًا (أحرف، أرقام، _ . -).',
            'err_pw' => 'يجب ألا تقل كلمة المرور عن 10 أحرف.', 'err_match' => 'كلمتا المرور غير متطابقتين.',
            'err_email' => 'أدخل بريدًا إلكترونيًا صالحًا.', 'err_exists' => 'يوجد حساب مسؤول بالفعل. يرجى تسجيل الدخول.',
        ],
        'ur' => [
            'choose' => 'تنصیب کی زبان منتخب کریں', 'choose_sub' => 'ایڈمن پینل اسی زبان میں ترتیب دیا جائے گا',
            'welcome' => 'خوش آمدید', 'sub' => 'اپنا پہلا ایڈمن اکاؤنٹ بنائیں', 'title' => 'تنصیب',
            'username' => 'صارف نام', 'owner' => 'پورا نام / سائٹ کا مالک', 'owner_ph' => 'کاپی رائٹ اور قانونی نوٹس میں دکھایا جاتا ہے',
            'email' => 'ای میل', 'password' => 'پاس ورڈ', 'password2' => 'پاس ورڈ (دوبارہ)', 'submit' => 'اکاؤنٹ بنائیں',
            'change_lang' => 'زبان تبدیل کریں', 'back' => 'ویب سائٹ پر واپس جائیں',
            'err_csrf' => 'سیکیورٹی تصدیق کی مدت ختم ہو گئی، دوبارہ کوشش کریں۔',
            'err_user' => 'صارف نام 3 سے 50 حروف کا ہونا چاہیے (حروف، ہندسے، _ . -)۔',
            'err_pw' => 'پاس ورڈ کم از کم 10 حروف کا ہونا چاہیے۔', 'err_match' => 'پاس ورڈ مماثل نہیں ہیں۔',
            'err_email' => 'درست ای میل درج کریں۔', 'err_exists' => 'ایڈمن اکاؤنٹ پہلے سے موجود ہے۔ براہ کرم لاگ ان کریں۔',
        ],
        'hi' => [
            'choose' => 'इंस्टॉलेशन की भाषा चुनें', 'choose_sub' => 'एडमिन पैनल इसी भाषा में सेट किया जाएगा',
            'welcome' => 'स्वागत है', 'sub' => 'अपना पहला एडमिन खाता बनाएँ', 'title' => 'इंस्टॉलेशन',
            'username' => 'उपयोगकर्ता नाम', 'owner' => 'पूरा नाम / साइट स्वामी', 'owner_ph' => 'कॉपीराइट और कानूनी सूचनाओं में दिखता है',
            'email' => 'ईमेल', 'password' => 'पासवर्ड', 'password2' => 'पासवर्ड (दोबारा)', 'submit' => 'खाता बनाएँ',
            'change_lang' => 'भाषा बदलें', 'back' => 'वेबसाइट पर लौटें',
            'err_csrf' => 'सुरक्षा जाँच की समय-सीमा समाप्त हो गई, कृपया फिर से प्रयास करें।',
            'err_user' => 'उपयोगकर्ता नाम 3-50 अक्षरों का होना चाहिए (अक्षर, अंक, _ . -)।',
            'err_pw' => 'पासवर्ड कम से कम 10 अक्षरों का होना चाहिए।', 'err_match' => 'पासवर्ड मेल नहीं खाते।',
            'err_email' => 'मान्य ईमेल पता दर्ज करें।', 'err_exists' => 'एडमिन खाता पहले से मौजूद है। कृपया लॉग इन करें।',
        ],
        'ms' => [
            'choose' => 'Pilih bahasa pemasangan', 'choose_sub' => 'Panel pentadbir akan disediakan dalam bahasa ini',
            'welcome' => 'Selamat datang', 'sub' => 'Cipta akaun pentadbir pertama anda', 'title' => 'Pemasangan',
            'username' => 'Nama pengguna', 'owner' => 'Nama penuh / Pemilik laman', 'owner_ph' => 'Dipaparkan dalam notis hak cipta dan undang-undang',
            'email' => 'E-mel', 'password' => 'Kata laluan', 'password2' => 'Kata laluan (sekali lagi)', 'submit' => 'Cipta akaun',
            'change_lang' => 'Tukar bahasa', 'back' => 'Kembali ke laman web',
            'err_csrf' => 'Semakan keselamatan telah tamat, sila cuba lagi.',
            'err_user' => 'Nama pengguna mesti 3-50 aksara (huruf, nombor, _ . -).',
            'err_pw' => 'Kata laluan mesti sekurang-kurangnya 10 aksara.', 'err_match' => 'Kata laluan tidak sepadan.',
            'err_email' => 'Masukkan alamat e-mel yang sah.', 'err_exists' => 'Akaun pentadbir sudah wujud. Sila log masuk.',
        ],
        'zh' => [
            'choose' => '选择安装语言', 'choose_sub' => '管理面板将以此语言设置',
            'welcome' => '欢迎', 'sub' => '创建您的第一个管理员账户', 'title' => '安装',
            'username' => '用户名', 'owner' => '姓名 / 网站所有者', 'owner_ph' => '显示在版权和法律声明中',
            'email' => '电子邮件', 'password' => '密码', 'password2' => '密码（再次输入）', 'submit' => '创建账户',
            'change_lang' => '更改语言', 'back' => '返回网站',
            'err_csrf' => '安全验证已过期，请重试。',
            'err_user' => '用户名须为 3-50 个字符（字母、数字、_ . -）。',
            'err_pw' => '密码至少需要 10 个字符。', 'err_match' => '两次输入的密码不一致。',
            'err_email' => '请输入有效的电子邮件地址。', 'err_exists' => '管理员账户已存在，请登录。',
        ],
        'ja' => [
            'choose' => 'インストール言語を選択', 'choose_sub' => '管理パネルはこの言語で設定されます',
            'welcome' => 'ようこそ', 'sub' => '最初の管理者アカウントを作成してください', 'title' => 'インストール',
            'username' => 'ユーザー名', 'owner' => '氏名 / サイト所有者', 'owner_ph' => '著作権表示や法的表記に表示されます',
            'email' => 'メールアドレス', 'password' => 'パスワード', 'password2' => 'パスワード（確認）', 'submit' => 'アカウントを作成',
            'change_lang' => '言語を変更', 'back' => 'ウェブサイトに戻る',
            'err_csrf' => 'セキュリティ確認の有効期限が切れました。もう一度お試しください。',
            'err_user' => 'ユーザー名は3〜50文字（英字、数字、_ . -）にしてください。',
            'err_pw' => 'パスワードは10文字以上にしてください。', 'err_match' => 'パスワードが一致しません。',
            'err_email' => '有効なメールアドレスを入力してください。', 'err_exists' => '管理者アカウントは既に存在します。ログインしてください。',
        ],
        'ko' => [
            'choose' => '설치 언어 선택', 'choose_sub' => '관리자 패널이 이 언어로 설정됩니다',
            'welcome' => '환영합니다', 'sub' => '첫 관리자 계정을 만드세요', 'title' => '설치',
            'username' => '사용자 이름', 'owner' => '이름 / 사이트 소유자', 'owner_ph' => '저작권 및 법적 고지에 표시됩니다',
            'email' => '이메일', 'password' => '비밀번호', 'password2' => '비밀번호 (다시 입력)', 'submit' => '계정 만들기',
            'change_lang' => '언어 변경', 'back' => '웹사이트로 돌아가기',
            'err_csrf' => '보안 확인이 만료되었습니다. 다시 시도해 주세요.',
            'err_user' => '사용자 이름은 3~50자여야 합니다 (문자, 숫자, _ . -).',
            'err_pw' => '비밀번호는 10자 이상이어야 합니다.', 'err_match' => '비밀번호가 일치하지 않습니다.',
            'err_email' => '올바른 이메일 주소를 입력하세요.', 'err_exists' => '관리자 계정이 이미 있습니다. 로그인하세요.',
        ],
    ];
}

/** Kurulum metni: seçilen dil → İngilizce */
function setup_t(string $key, string $lang): string {
    $all = setup_strings();
    return $all[$lang][$key] ?? $all['en'][$key] ?? $key;
}

/** Kurulumda seçilebilecek diller: hazır panel sözlüğü olanlar (tr, en ve admin/includes/i18n_langs/*.php) */
function setup_languages(): array {
    $out = [];
    foreach (get_preset_language_catalog() as $code => $l) {
        if (in_array($code, ['tr', 'en'], true) || is_file(__DIR__ . '/../admin/includes/i18n_langs/' . $code . '.php')) {
            $out[$code] = $l;
        }
    }
    return $out;
}
