.PHONY: all down up rebuild
all:
	@echo "Wybierz akcję"

rebuild: down up

down:
	docker compose down -v

up:
	docker compose build --no-cache
	docker compose up -d  --force-recreate

# ... existing commands ...

# Configure Memcached
configure-memcached:
	@echo "🔧 Configuring Memcached for PrestaShop..."
	docker compose exec prestashop php -r " \
		define('_PS_ADMIN_DIR_', '/var/www/html/admin-dev'); \
		define('PS_ADMIN_DIR', '/var/www/html/admin-dev'); \
		require_once '/var/www/html/config/config.inc.php'; \
		Configuration::updateValue('PS_CACHING_SYSTEM', 'CacheMemcache'); \
		Configuration::updateValue('PS_CACHE_ENABLE', 1); \
		Configuration::updateValue('_MEDIA_SERVER_1_', ''); \
		echo 'Memcached configured!' . PHP_EOL; \
	"

# Test Memcached connection
test-memcached:
	@echo "🧪 Testing Memcached connection..."
	docker compose exec prestashop php -r " \
		if (class_exists('Memcached')) { \
			\$$m = new Memcached(); \
			\$$m->addServer('memcached', 11211); \
			\$$m->set('test_key', 'test_value', 10); \
			\$$result = \$$m->get('test_key'); \
			if (\$$result === 'test_value') { \
				echo '✅ Memcached is working!' . PHP_EOL; \
				echo 'Stats: ' . print_r(\$$m->getStats(), true); \
			} else { \
				echo '❌ Memcached test failed!' . PHP_EOL; \
			} \
		} else { \
			echo '❌ Memcached extension not installed!' . PHP_EOL; \
		} \
	"

# Test cache through module
test-cache:
	@echo "🧪 Testing module cache..."
	docker compose exec prestashop php -r " \
		require_once '/var/www/html/modules/currencyrate/vendor/autoload.php'; \
		use Example\Module\CurrencyRate\Service\CacheService; \
		\$$cache = new CacheService(); \
		echo 'Cache Stats: ' . print_r(\$$cache->getStats(), true) . PHP_EOL; \
		\$$cache->set('test_currency', ['USD' => 4.05], 60); \
		\$$value = \$$cache->get('test_currency'); \
		echo 'Test Value: ' . print_r(\$$value, true) . PHP_EOL; \
		echo (\$$cache->has('test_currency') ? '✅ Cache is working!' : '❌ Cache failed!') . PHP_EOL; \
	"

# Full rebuild with Memcached
rebuild-with-cache: rebuild configure-memcached test-memcached
	@echo "✅ PrestaShop with Memcached is ready!"
