# ATM API

ATM REST API. PHP 8.3 / Laravel 12 / MySQL 8.

## İnstall

    composer install
    cp .env.example .env
    php artisan key:generate

`.env`-də MySQL məlumatlarını yazın:

    php artisan migrate --seed
    php artisan serve

## Test

Test üçün ayrı MySQL DB lazımdır — SQLite `lockForUpdate` dəstəkləmir:

    cp .env .env.testing
    # .env.testing -> DB_DATABASE=atm_api_testing
    mysql -uroot -e "CREATE DATABASE atm_api_testing"
    php artisan migrate --env=testing
    ./vendor/bin/pest

## API

Endpointlər `routes/api.php`-də. Base URL: `http://localhost:8000/api/v1`.

Auth: Sanctum token. Test istifadəçiləri:
- `admin@atm.test` / `password` (admin)
- `supervisor@atm.test` / `password` (supervisor)
- `customer@atm.test` / `password` (customer)

Token almaq üçün:

    php artisan tinker
    >>> $u = User::where('email', 'customer@atm.test')->first();
    >>> $u->createToken('test')->plainTextToken;

## curl ilə test

Aşağıdakı dəyişənləri öncədən təyin edin:

    BASE=http://localhost:8000/api/v1
    TOKEN=<customer-token>
    ADMIN=<admin-token>

### Public endpointlər

Hesablar siyahısı:

    curl $BASE/accounts

Tək hesab:

    curl $BASE/accounts/1

Hesab əməliyyatları:

    curl $BASE/accounts/1/transactions

Tək əməliyyat:

    curl $BASE/transactions/1

ATM denominasiyaları:

    curl $BASE/atm/denominations

### Withdraw

100 AZN çək (10000 minor unit):

    curl -X POST $BASE/accounts/1/withdraw \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -H "Idempotency-Key: $(uuidgen)" \
      -d '{"amount": 10000}'

Eyni `Idempotency-Key` təkrar göndərilsə eyni cavab qayıdır:

    KEY=$(uuidgen)
    curl -X POST $BASE/accounts/1/withdraw \
      -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
      -H "Idempotency-Key: $KEY" -d '{"amount": 10000}'
    curl -X POST $BASE/accounts/1/withdraw \
      -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
      -H "Idempotency-Key: $KEY" -d '{"amount": 10000}'

`Idempotency-Key` olmasa 400 qayıdır:

    curl -X POST $BASE/accounts/1/withdraw \
      -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
      -d '{"amount": 10000}'

Balans çatmasa 422:

    curl -X POST $BASE/accounts/1/withdraw \
      -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
      -H "Idempotency-Key: $(uuidgen)" -d '{"amount": 999999999}'

### Reverse (yalnız supervisor/admin)

    curl -X POST $BASE/transactions/1/reverse \
      -H "Authorization: Bearer $ADMIN" \
      -H "Content-Type: application/json" \
      -H "Idempotency-Key: $(uuidgen)"

İkinci dəfə eyni transaction-a reverse → 422:

    curl -X POST $BASE/transactions/1/reverse \
      -H "Authorization: Bearer $ADMIN" -H "Content-Type: application/json" \
      -H "Idempotency-Key: $(uuidgen)"

### Denomination update (yalnız admin)

100 AZN-lik kupürün sayını 50 et:

    curl -X PUT $BASE/atm/denominations/1 \
      -H "Authorization: Bearer $ADMIN" \
      -H "Content-Type: application/json" \
      -d '{"quantity": 50}'

### Race / concurrency

Eyni hesaba 50 paralel withdraw atmaq üçün:

    for i in $(seq 1 50); do
      curl -s -X POST $BASE/accounts/1/withdraw \
        -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
        -H "Idempotency-Key: $(uuidgen)" -d '{"amount": 100}' &
    done; wait

Sonra DB-də invariantı yoxla. MySQL-ə qoşul:

    mysql -uroot atm_api

Cari balans və əməliyyatların cəmi:

    SELECT balance FROM accounts WHERE id = 1;

    SELECT
      COALESCE(SUM(CASE WHEN type='withdraw' AND status='success' THEN amount END), 0) AS withdrawn,
      COALESCE(SUM(CASE WHEN type='reverse'  AND status='success' THEN amount END), 0) AS reversed
    FROM transactions WHERE account_id = 1;

İnvariant: `balance + withdrawn - reversed == initial_balance`. Tək sorğuda yoxlamaq üçün (`@start` ilkin balansdır):

    SET @start := 100000000;
    SELECT
      a.balance
      + COALESCE(SUM(CASE WHEN t.type='withdraw' AND t.status='success' THEN t.amount END), 0)
      - COALESCE(SUM(CASE WHEN t.type='reverse'  AND t.status='success' THEN t.amount END), 0)
      AS reconstructed,
      @start AS expected
    FROM accounts a
    LEFT JOIN transactions t ON t.account_id = a.id
    WHERE a.id = 1
    GROUP BY a.id, a.balance;

`reconstructed == expected` olmalıdır.

> Qeyd: rate limit `5,1` olduğu üçün dəqiqədə 5-dən artığı keçməyəcək. Race üçün limit-i müvəqqəti artırın.

## Əsas qərarlar

- Pul: integer minor units (100 AZN = 10000). Float yoxdur.
- Withdraw: bounded coin change DP. Greedy non-canonical denomination-larda işləmir.
- Concurrency: `DB::transaction` + `lockForUpdate`. Lock sırası: account → denomination (ORDER BY id), deadlock olmasın deyə.
- Idempotency: `Idempotency-Key` header (UUID), 24 saat saxlanılır.
- Audit: hər maliyyə əməliyyatında sync log, transaction içində.
- Reversal: yeni `type=reverse` qeyd, `related_transaction_id` ilə bağlanır. Soft delete yoxdur.
- Transaction `type` və `status`: enum + Eloquent cast.
- Currency: AZN və USD seed olunur, hər ikisi üçün denominasiya inventarı qurulur.
- Rate limit: `throttle:5,1`.

---

# Qeydlər

Bəzi yerlərdə daha sadə yol seçdim. 3 günlük task üçün artıq mürəkkəblik gətirəcəyini düşündüm.

## 1. Money VO əvəzinə helper

`format_money()` helperi var, `Money` class yoxdur. Balans müqayisəsi tək yerdədir, riyazi əməl yoxdur — VO əlavə fayda gətirmir.

## 2. DispensePlan VO əvəzinə array

`BanknoteDispenser::dispense()` sadəcə `[denom => count]` array qaytarır. İki yerdə oxunur, wrapping-ə ehtiyac görmədim.

## 3. DTO əvəzinə metod parametrləri

Action-larda 2-4 parametr var, hələ oxunaqlıdır. DTO + mapping kodu hazırda artıqlıq olar.

## 4. Action içində idempotency, ayrı manager deyil

Yalnız `WithdrawAction` key saxlayır. Tək yerdə işlənən kodu ayrı class-a çıxarmaq mənasız.

## 5. Inline `AuditLog::create()`

3 Action-da birbaşa çağırılır. Service eləsəm IP-ni hər çağırışda ötürməli olacam (request-scoped). 3 təkrar normaldır.

## 6. `User.role` string sütunu, Spatie/permission deyil

3 role var, tək-tək yoxlanılır. Spatie 4 cədvəl gətirər — bir sətirlik işin əvəzi deyil.

## 7. Custom rate limiter əvəzinə `throttle:5,1`

Default `throttle` user-ə görə işləyir, spec ödənir. Multi-key limiter teorik problem həll edir.

## 8. `RuntimeException` extend, custom base class deyil

3 exception var, render 9 sətirdir. Base class + enum refactor 30+ sətirdir, nəticə eyni.

## 9. Idempotency-də body fingerprint yoxdur

İdeal halda body-nin SHA-256-sı saxlanır ki, eyni key + fərqli body → 409 versin. Edge case-dir, spec-də tələb yoxdur.

## 10. `dispensed_notes` JSON, ayrı cədvəl deyil

Hesabat endpoint-i yoxdur. Tək consumer reversaldır — JSON-u foreach-ləmək kifayətdir.

## 11. Cache yox

`GET /atm/denominations` hər sorğuda DB-yə düşür. Yüksək QPS almır, invalidasiya logic-i qazancsızdır.

## 12. Idempotency cleanup manual

`expires_at` var, cleanup command yoxdur. Lokal scheduler işləmədiyi üçün yazsam da fayda verməz — production deploy addımıdır.

## 13. `CASE WHEN` raw SQL

Eloquent-də təmiz syntax yoxdur, foreach + N `decrement()` isə N+1 olardı. Integer cast var, SQL injection riski yoxdur.

## 14. `Api/V1/` namespace yoxdur

Tək versiya var. v2 gələndə mass move edilər.

## 15. Test scenarios tək AZN

USD seed olunur amma testlər AZN üzərindədir. Eyni kod yolu sınandığı üçün ikiqat scenario yazmadım.

## 16. `duration_ms` ölçümü Action içində

Yalnız withdraw timing ölçür. 3 sətri service-ə çıxartmaq artıqlıqdır.

## 17. Validation messages default

Laravel default mesajları onsuz da anlaşılandır. Custom mesajlar yazsam minor unit izahı hər rule-da təkrarlanardı.
