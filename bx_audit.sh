#!/bin/bash

echo "==================================================="
echo "  Аудит сервера перед обновлением Bitrix / ОС      "
echo "==================================================="

# 1. Определение ОС
echo -e "\n[1. ОПЕРАЦИОННАЯ СИСТЕМА]"
if [ -f /etc/os-release ]; then
    source /etc/os-release
    echo "Дистрибутив: $PRETTY_NAME"
else
    echo "Дистрибутив: $(uname -snrvm)"
fi

# 2. Проверка доступных обновлений ОС
echo -e "\n[2. ПАКЕТЫ ОС ДЛЯ ОБНОВЛЕНИЯ]"
if command -v apt-get &> /dev/null; then
    apt-get update -qq &> /dev/null
    UPDATES=$(apt-get -s upgrade | grep -oP '^\d+(?=\s+upgraded)')
    echo "Доступно пакетов (APT): ${UPDATES:-0}"
elif command -v dnf &> /dev/null; then
    UPDATES=$(dnf check-update -q | grep -v '^$' | wc -l)
    echo "Доступно пакетов (DNF): ${UPDATES:-0}"
elif command -v yum &> /dev/null; then
    UPDATES=$(yum check-update -q | grep -v '^$' | wc -l)
    echo "Доступно пакетов (YUM): ${UPDATES:-0}"
else
    echo "Пакетный менеджер не определен (не apt/dnf/yum)."
fi

# 3. Версия PHP
echo -e "\n[3. СТЕК PHP]"
if command -v php &> /dev/null; then
    php -v | head -n 1
else
    echo "PHP CLI не установлен или не в PATH!"
fi

# 4. Дисковое пространство (только реальные разделы)
echo -e "\n[4. СОСТОЯНИЕ ДИСКОВ (Свободно)]"
df -hT | grep -vE 'tmpfs|udev|loop|overlay' | awk '{printf "%-20s %-10s %-10s %-10s %-10s\n", $1, $2, $3, $4, $6}' | head -n 1
df -hT | grep -vE 'tmpfs|udev|loop|overlay' | awk '{printf "%-20s %-10s %-10s %-10s %-10s\n", $1, $3, $4, $5, $7}' | grep -v 'Size'

# 5. Поиск корня сайта
echo -e "\n[5. АНАЛИЗ ПОРТАЛА BITRIX]"
# Массив частых путей. Скрипт проверит их по очереди.
SITE_PATHS=("/home/bitrix/www" "/home/bitrix/ext_www" "/var/www/site1/public_html/" "/var/www/html/site1" "/var/www/html" "/var/www/bitrix")
DOC_ROOT=""

for p in "${SITE_PATHS[@]}"; do
    if [ -d "$p/bitrix" ]; then
        DOC_ROOT=$p
        break
    fi
done

# Если не нашли автоматически, просим ввести ручками
if [ -z "$DOC_ROOT" ]; then
    read -p "Стандартные пути не найдены. Введите полный путь до корня сайта (где папка bitrix): " DOC_ROOT
fi

if [ -d "$DOC_ROOT/bitrix" ]; then
    echo "Корень сайта найден: $DOC_ROOT"
    
    # Дата ядра (косвенный признак свежести портала)
    if [ -f "$DOC_ROOT/bitrix/modules/main/classes/general/version.php" ]; then
        BX_DATE=$(grep "SM_VERSION_DATE" "$DOC_ROOT/bitrix/modules/main/classes/general/version.php" | cut -d'"' -f2)
        echo "Дата ядра (Главный модуль): $BX_DATE"
    fi

    echo "Считаем объем файлов (без кэша)..."
    # ionice -c 3 и nice -n 19 защищают от 100% I/O Wait, о котором вы говорили ранее
    ionice -c 3 nice -n 19 du -sh --exclude='bitrix/cache' --exclude='bitrix/managed_cache' --exclude='bitrix/html_pages' "$DOC_ROOT" 2>/dev/null
    
    echo "Из них весят тяжелые директории:"
    ionice -c 3 du -sh "$DOC_ROOT/upload" "$DOC_ROOT/local" "$DOC_ROOT/bitrix/backup" 2>/dev/null | sort -rh
else
    echo "Внимание: по пути $DOC_ROOT не найдена папка /bitrix."
fi

echo "==================================================="