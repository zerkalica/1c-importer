<?php
$config = array(
  'db' => array(
    'dsn'=>'mysql:dbname=magaz;host=127.0.0.1',
    'user' => 'root',
    'password' => '1231',
    'charset' => 'utf8',
    'options' => NULL,
  ),
  
  'path' => array(
    'filesRootReal' => '..',
    'bitrixRootReal' => '../files/1cbitrix',
  ),
  'drupalPath' => array(
    'images' => 'files/pimages',
    'licences' => 'files/licenses',
    'currencyFile' => 'files/curs.txt',
  ),
  
  'default' => array(
  	'templates' => 'templates/default',
    'sqlTemplates' => 'sql',
  	'charset' => 'utf-8',
  	'lang' => 'ru',
  	'debug' => cLog::SHOW_SYSTEM_ERRORS | cLog::SHOW_DEBUG_ERRORS | cLog::CLEAR_LOG | cLog::MESSAGE_BRIEF,
    'logfile' => 'log/log',
  ),

  'tagmap' => array(
'ХЗ' => 'Другое',
'АВДП' => 'Антивирусы для домашних пользователей',
'АВПС' => 'Антивирусы для почтовых серверов',
'АВРС' => 'Антивирусы для рабочих станций',
'АВФС' => 'Антивирусы для файловых серверов',
'АВКП' => 'Комплексные антивирусные продукты',
'АД' => 'Администрирование',
'АЗО' => 'Анимированные заставки и обои',
'АЛФ' => 'Анализ лог-файлов',
'АНВ' => 'Анимация и видео',
'АОДМ' => 'Альтернативные оболочки и десктоп менеджеры',
'АОН' => 'АОН, автоответчики',
'АРХ' => 'Архиваторы',
'АРК' => 'Аркады',
'АСП' => 'Анти-спам',
'АТС' => 'Программы для АТС',
'БАНК' => 'БАНК',
'БИ' => 'Библиотеки',
'БИК' => 'Библиотеки,компоненты',
'ВВВ' => 'WWW',
'ВД' => 'Виртуальные десктопы',
'ВЕБ' => 'Web-дизайн',
'ВМ' => 'Windows mobile',
'ВМС' => 'Windows mobile for smartphone',
'ВП' => 'Восстановление паролей',
'ВСД' => 'восстановление данных',
'ВЦЕ' => 'Windows СE',
'ГК' => 'Географические карты',
'ГОН' => 'Гонки',
'ГР' => 'Графические редакторы',
'ДБУХ' => 'Домашняя бухгалтерия',
'ДЛП' => 'Делопроизводство',
'ДОК' => 'Документация',
'ДПД' => 'Другие программы для дома',
'ДПКПК' => 'Другие программы для карманных ПК',
'ДРОП' => 'Другие офисные программы',
'ДФУ' => 'Дисковые и файловые утилиты',
'ЗАПО' => 'Защита и активация ПО',
'ЗМ' => 'Звук и музыка',
'ЗП' => 'Заработная плата',
'ЗПК' => 'Защита ПК, ОС и сетей',
'ИКП' => 'Издательские пакеты',
'ИНС' => 'Инструментальные средства и редакторы для разработчиков',
'ИПП' => 'Интегрированные программные пакеты от Microsoft',
'ИЯ' => 'Иностранные языки',
'КД' => 'Кадры',
'КГ' => 'Каталогизаторы',
'КМП' => 'Компиляторы',
'КМУ' => 'Для кпк и мобильных устройств',
'КР' => 'Курсы',
'ЛОГ' => 'Логические',
'МЭИЛ' => 'Электронная почта',
'МЕД' => 'Медицина',
'ММ' => 'Математика',
'МС' => 'Firewall (Межсетевые экраны)',
'МТ' => 'Мобильные телефоны',
'НГ' => 'Налоги',
'НЗБ' => 'Нормативные и законодательные базы',
'НТП' => 'Научно-технические программы',
'ОГ' => 'Обработка графики',
'ОЕМ' => 'ОЕМ',
'ОП' => 'Обучающие программы',
'ОСЛУ' => 'Операционные системы Linux и Unix',
'ОСМИ' => 'Операционные системы Microsoft',
'ОФМ' => 'Оболочки и файл-менеджеры',
'ПАЛМ' => 'Palm OS',
'ПВС' => 'Поисковые системы',
'ПМО' => 'Приложения Microsoft Office',
'ПДА' => 'Программы для автобизнеса',
'ПДБУ' => 'Другие задачи бухучета',
'ПЕ' => 'Переводчики',
'ПЕЧ' => 'Печать',
'ПЖС' => 'Продвижение сайтов',
'ПЛ' => 'ПО для Linux',
'ПОА' => 'ПО Adobe',
'ПОМ' => 'ПО для Mac OS',
'ПОС' => 'ПО Corel',
'ПОТ' => 'Психологические,образовательные тесты',
'ПП' => 'Проверка правописания',
'ППС' => 'PocketPС',
'ПР' => 'Производство',
'ПРК' => 'Перекодировщики',
'ПРОС' => 'Просмотрщики',
'ПРОЧ' => 'Прочие программы',
'ПРС' => 'Proxy-сервера',
'ПРСИМ' => 'Программы распознавания символов',
'РИЕО' => 'Расширения для MS Internet Explorer и MS Outlook',
'РК' => 'Резервное копирование',
'РПГ' => 'RPG',
'САПР' => 'САПР',
'САР' => 'Строительство, архитектура',
'САРКМ' => 'Средства администрирования,резервного копирования и мониторинга',
'СВ' => 'Строительство',
'СЕРВ' => 'ПО для серверов',
'СИМ' => 'Symbian OS',
'СИМУ' => 'Симуляторы',
'СИСУ' => 'Системные утилиты',
'СКР' => 'Скрипты',
'СЛ' => 'Словари',
'СМВ' => 'Семейство Microsoft Visio',
'СОСМ' => 'Серверные операционные системы Microsoft',
'СП' => 'Создание презентаций',
'СПМ' => 'Серверные приложения Microsoft',
'СПОРТ' => 'Спортивные',
'СР' => 'Средства разработки',
'СРМ' => 'Средства разработки от Microsoft',
'СС' => 'Сетевые средства',
'ССМП' => 'Словари и системы машинного перевода',
'ССУС' => 'Системы создания и управления сайтами (СMS)',
'СТР' => 'Специализированные текстовые редакторы',
'СТРА' => 'Стратегии',
'СУ' => 'Складской учет',
'СУБДМ' => 'СУБД Microsoft',
'СУБДО' => 'СУБД ORAСLE',
'СФУ' => 'Сфера услуг',
'ТПБМ' => 'Типовые пакеты для малого и среднего бизнеса от Microsoft',
'ТР' => 'Текстовые редакторы',
'ТРГ' => 'Торговля',
'УВК' => 'Управление взаимоотношениями с клиентами (СRM)',
'УД' => 'Утилиты для десктопа',
'УМ' => 'Утилиты для мультимедиа',
'УПСР' => 'Управления проектами и совместной работой',
'УП' => 'Установочные программы',
'ФПА' => 'Финансовое планирование и анализ',
'ФП' => 'Факс-программы',
'ФТП' => 'FTP',
'ЦВ' => 'Цифровое видео',
'ЦФ' => 'Цифровое фото',
'ЧАТ' => 'Chat',
'ШИФ' => 'Шифрование',
'ШР' => 'Шрифты',
'ЭК' => 'Электронные книги',
'ЭПИС' => 'Экспертные и прочие интеллектуальные системы',
'ЭС' => 'Энциклопедии и справочники',
'ЮНЗБ' => 'Юриспруденция, нормативные и законодательные базы',
'ЮР' => 'Для юристов',
'1С' => '1С',
'3ДГ' => '3D-графика',
)
);
