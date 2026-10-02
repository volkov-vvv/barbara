# План разработки: Платформа для запоминания иностранных слов (SM-2)
**Стек:** Laravel 13, Vue.js 3 (Inertia.js), Spatie Laravel-Permission, SM-2 Алгоритм.

## [x] Статус проекта: Этапы 1–4 завершены

### Доработки UI
- [x] Создание словаря через отдельную кнопку + Dialog (как у пользователей)
- [x] Детализация последней сессии повторения на странице результатов студента
- [x] График регулярности: даты повторений + подписи осей
- [x] График по фактическим датам повторений; сессия не переносится через сутки

### Публичная заявка на обучение
- [x] Страница `/apply`: форма заявки (Inertia + useForm + файл), валидация, mock-сохранение
- [x] Уникальность email в заявках (таблица `applications`)
- [x] Админка: таблица заявок, просмотр полей, настройка колонок, фильтры
- [x] Главная в стиле формы заявки + кнопка «Подать заявку»
- [x] Login в том же публичном стиле
- [x] Админка: редактирование и удаление заявок
- [x] Экспорт заявок в Excel и PDF с учётом фильтров
- [x] Сортировка в таблице заявок + увеличенная шапка
- [x] Favicon: логотип с книгой
- [x] Дашборд: сводка по пользователям, заявкам, словарям и прогрессу
- [x] Ресурсные роуты (Route::resource)

---

## 🛠 ЭТАП 1: Схема базы данных и RBAC (Спринт 1)

### 1.1. Базовые таблицы и Модели
- [x] **`users`**: `id`, `name`, `email`, `password`, `role` (enum: 'admin', 'student', 'parent').
- [x] **`parent_student`**: `parent_id` (FK `users`), `student_id` (FK `users`), `relation` (varchar для связей: отец/мать/опекун).
- [x] **`languages`**: `id`, `code` (string: en, de, es), `name` (string).
- [x] **`word_sets`**: `id`, `language_id` (FK), `title`, `description`, `created_by` (FK `users`, nullable для системных наборов).
- [x] **`words`**: `id`, `word_set_id` (FK), `text`, `translation`, `example_sentence`, `audio_url` (nullable).

### 1.2. Таблицы алгоритма SM-2 и аналитики
- [x] **`user_word_progress`**:
  - `user_id` (FK `users`)
  - `word_id` (FK `words`)
  - `repetitions` (int, default: 0) — успешные повторения подряд.
  - `ease_factor` (float, default: 2.5) — коэффициент легкости.
  - `interval_days` (int, default: 0) — интервал в днях до следующего показа.
  - `next_review_at` (datetime) — дата следующего показа карточки.
  - `last_reviewed_at` (datetime, nullable) — дата последнего ответа.
  - `total_correct` (int, default: 0), `total_wrong` (int, default: 0).
  - *Индексы:* Составной индекс на `[user_id, next_review_at]`.
- [x] **`review_sessions`**: `id`, `user_id` (FK), `started_at`, `finished_at`, `correct_count`, `wrong_count` (для аналитики родителя).

---

## 💻 ЭТАП 2: Бэкенд и Бизнес-логика (Спринт 2)

### 2.1. Интеграция прав (Spatie + Inertia Shared Data)
- [x] Установка `spatie/laravel-permission` и создание базовых ролей (`admin`, `student`, `parent`).
- [x] Настройка `HandleInertiaRequests.php` для сквозного проброса ролей и разрешений авторизованного пользователя (`auth.user.roles`, `auth.user.permissions`).

### 2.2. Сервис интервального повторения SM-2
- [x] Создать `App\Services\SpacedRepetitionService.php` с логикой:
  - Вход: `quality` от 0 до 5.
  - Ошибка (`quality < 3`): `repetitions = 0`, `interval_days = 1`.
  - Успех (`quality >= 3`):
    - `repetitions == 0` -> `interval_days = 1`
    - `repetitions == 1` -> `interval_days = 6`
    - `repetitions > 1` -> `interval_days = round(previous_interval * ease_factor)`
    - `repetitions++`
  - Пересчет EF: `EF = EF + (0.1 - (5 - quality) * (0.08 + (5 - quality) * 0.02))`. Нижний порог `EF = 1.3`.
  - Расчет даты: `next_review_at = now()->addDays($interval_days)`.
- [x] Написать Pest/PHPUnit тесты на проверку формулы SM-2.

### 2.3. Маршруты и Контроллеры (Защита Middleware)
- [x] **Admin Space (`role:admin`)**: CRUD пользователей, привязка родитель-ребенок, CRUD системных языков/словарей.
- [x] **Student Space (`role:student`)**: Получение пула слов на сегодня, метод сохранения ответа тренировки, CRUD личных наборов слов.
- [x] **Parent Space (`role:parent`)**: Эндпоинты для выгрузки аналитики привязанных детей (успеваемость, слабые темы, лимиты/дневные цели).

---

## 🎨 ЭТАП 3: Фронтенд на Vue 3 + Inertia (Спринт 3)

### 3.1. Глобальная авторизация в UI
- [x] Создать composable-функцию `useAuth.js` (или `.ts`) для проверки прав (`can(permission)`, `hasRole(role)`) внутри Vue компонентов.

### 3.2. Страницы и Компоненты Vue
- [x] **`Student/ReviewCards.vue`**: Интерфейс флеш-карточек с анимацией переворота, выводом контекста и кнопками оценки (0-5). Поддержка воспроизведения аудио (TTS).
- [x] **`Parent/Dashboard.vue`**: Страница мониторинга успеваемости детей с графиками регулярности (Chart.js/ApexCharts), прогрессом по темам и настройкой целей.
- [x] **`Admin/UserManagement.vue`** & **`Admin/DictionaryEditor.vue`**: Формы модерации и пакетного импорта слов.

---

## 🚀 ЭТАП 4: Оптимизация и Тестирование (Спринт 4)
- [x] Покрытие Feature-тестами безопасности (проверка политик доступа к чужим данным).
- [x] Кэширование публичных словарей в Redis.
- [x] Оптимизация SQL-запросов (Eager Loading отношений в аналитике).

