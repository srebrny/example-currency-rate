# example-currency-rate


Uruchom 

`make rebuild` po czym sprawdź docker compose logs -f prestashop czy instancja wstała. 

`make install` aby zainstalować composera wewnątrz modułu

Po tym wejdź w panel administracyujny i dokonfiguruj memcached aby został włączony. 

Tutaj jest strona modułu : http://localhost:8080/module/currencyrate/display

Dla wygody dodałem link w menu na stronie głównej. 




## Do zrobienia w pełnej wersji:
Z racji ograniczenia czasowego do wersji pełnej
[] Automatyczna aktualizacja danych poprzez zadanie cron (np. raz dziennie). - Tutaj szedłbym w wersję symfony command
[] Tabela z kursami historycznymi powinna posiadać paginacje oraz sortowanie.
[] Ikonki modułu + opisy
[] cały pakiet testów e2e, unit
[] przygotowanie aby ide ( PHPStorm ) podpowiadał klasy z prestashop

[] envy
# Module configurations
CURRENCY_RATE_DEFAULT_CURRENCIES="USD,PLN,EUR,GBP"
CURRENCY_RATE_AUTO_UPDATE=1
CURRENCY_RATE_AUTO_UPDATE_HOURS_INTERVAL=24
CURRENCY_RATE_HISTORY_DAYS=30
