<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\ReviewSession;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo accounts and sample learning data.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@barbara.test'],
            [
                'name' => 'Admin Barbara',
                'password' => $password,
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
                'daily_word_goal' => 20,
            ],
        );
        $admin->syncRoles([UserRole::Admin->value]);

        $student = User::query()->updateOrCreate(
            ['email' => 'student@barbara.test'],
            [
                'name' => 'Alex Student',
                'password' => $password,
                'role' => UserRole::Student,
                'email_verified_at' => now(),
                'daily_word_goal' => 15,
            ],
        );
        $student->syncRoles([UserRole::Student->value]);

        $studentTwo = User::query()->updateOrCreate(
            ['email' => 'maria@barbara.test'],
            [
                'name' => 'Maria Student',
                'password' => $password,
                'role' => UserRole::Student,
                'email_verified_at' => now(),
                'daily_word_goal' => 25,
            ],
        );
        $studentTwo->syncRoles([UserRole::Student->value]);

        $parent = User::query()->updateOrCreate(
            ['email' => 'parent@barbara.test'],
            [
                'name' => 'Parent Barbara',
                'password' => $password,
                'role' => UserRole::Parent,
                'email_verified_at' => now(),
                'daily_word_goal' => 20,
            ],
        );
        $parent->syncRoles([UserRole::Parent->value]);

        $parent->students()->syncWithoutDetaching([
            $student->id => ['relation' => 'mother'],
            $studentTwo->id => ['relation' => 'guardian'],
        ]);

        $english = Language::query()->updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English'],
        );
        $german = Language::query()->updateOrCreate(
            ['code' => 'de'],
            ['name' => 'German'],
        );
        $spanish = Language::query()->updateOrCreate(
            ['code' => 'es'],
            ['name' => 'Spanish'],
        );

        $basics = $this->seedSystemWordSet($english, 'English Basics', 'Everyday starter vocabulary.', [
            ['hello', 'привет', 'Hello, how are you?'],
            ['goodbye', 'до свидания', 'Goodbye, see you tomorrow.'],
            ['please', 'пожалуйста', 'Please help me.'],
            ['thanks', 'спасибо', 'Thanks for your help.'],
            ['water', 'вода', 'I need a glass of water.'],
            ['book', 'книга', 'This book is interesting.'],
            ['school', 'школа', 'She goes to school every day.'],
            ['friend', 'друг', 'He is my best friend.'],
        ]);

        $travel = $this->seedSystemWordSet($english, 'Travel English', 'Useful phrases for trips.', [
            ['ticket', 'билет', 'I bought a train ticket.'],
            ['airport', 'аэропорт', 'We arrive at the airport at noon.'],
            ['hotel', 'отель', 'The hotel is near the center.'],
            ['map', 'карта', 'Do you have a city map?'],
            ['passport', 'паспорт', 'Show your passport, please.'],
        ]);

        $this->seedSystemWordSet($german, 'German Starter', 'First German words.', [
            ['Hallo', 'привет', 'Hallo, wie geht es dir?'],
            ['Danke', 'спасибо', 'Danke für deine Hilfe.'],
            ['Haus', 'дом', 'Das Haus ist groß.'],
            ['Apfel', 'яблоко', 'Ich esse einen Apfel.'],
        ]);

        $this->seedSystemWordSet($spanish, 'Spanish Essentials', 'Core Spanish vocabulary.', [
            ['hola', 'привет', '¡Hola! ¿Cómo estás?'],
            ['gracias', 'спасибо', 'Muchas gracias.'],
            ['agua', 'вода', 'Quiero agua, por favor.'],
            ['amigo', 'друг', 'Él es mi amigo.'],
        ]);

        $personalSet = WordSet::query()->updateOrCreate(
            [
                'created_by' => $student->id,
                'title' => 'My homework list',
            ],
            [
                'language_id' => $english->id,
                'description' => 'Personal words Alex is learning this week.',
            ],
        );

        $this->syncWords($personalSet, [
            ['focus', 'фокус', 'Stay in focus during the lesson.'],
            ['practice', 'практика', 'Practice makes progress.'],
            ['memory', 'память', 'Spaced repetition trains memory.'],
        ]);

        $this->seedProgressForStudent($student, $basics, $travel);
        $this->seedProgressForStudent($studentTwo, $basics, null, lighter: true);
        $this->seedReviewSessions($student);
        $this->seedReviewSessions($studentTwo, days: 5);

        app(PublicDictionaryCache::class)->flush();
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $words
     */
    private function seedSystemWordSet(Language $language, string $title, string $description, array $words): WordSet
    {
        $wordSet = WordSet::query()->updateOrCreate(
            [
                'language_id' => $language->id,
                'title' => $title,
                'created_by' => null,
            ],
            [
                'description' => $description,
            ],
        );

        $this->syncWords($wordSet, $words);

        return $wordSet;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $words
     */
    private function syncWords(WordSet $wordSet, array $words): void
    {
        foreach ($words as [$text, $translation, $example]) {
            Word::query()->updateOrCreate(
                [
                    'word_set_id' => $wordSet->id,
                    'text' => $text,
                ],
                [
                    'translation' => $translation,
                    'example_sentence' => $example,
                    'audio_url' => null,
                ],
            );
        }
    }

    private function seedProgressForStudent(
        User $student,
        WordSet $primary,
        ?WordSet $secondary,
        bool $lighter = false,
    ): void {
        $now = Carbon::now();
        $words = $primary->words()->orderBy('id')->get();

        foreach ($words as $index => $word) {
            $isDue = $index < ($lighter ? 2 : 4);

            UserWordProgress::query()->updateOrCreate(
                [
                    'user_id' => $student->id,
                    'word_id' => $word->id,
                ],
                [
                    'repetitions' => $isDue ? 0 : ($lighter ? 1 : 2),
                    'ease_factor' => $lighter ? 2.4 : 2.5,
                    'interval_days' => $isDue ? 1 : ($lighter ? 3 : 6),
                    'next_review_at' => $isDue ? $now->copy()->subHour() : $now->copy()->addDays($index + 1),
                    'last_reviewed_at' => $isDue ? $now->copy()->subDay() : $now->copy()->subDays(2),
                    'total_correct' => $lighter ? 2 : 5 + $index,
                    'total_wrong' => $lighter ? 1 : max(0, 3 - $index),
                ],
            );
        }

        if ($secondary !== null) {
            foreach ($secondary->words()->orderBy('id')->limit(3)->get() as $index => $word) {
                UserWordProgress::query()->updateOrCreate(
                    [
                        'user_id' => $student->id,
                        'word_id' => $word->id,
                    ],
                    [
                        'repetitions' => 1,
                        'ease_factor' => 2.3,
                        'interval_days' => 1,
                        'next_review_at' => $now->copy()->subMinutes(30 + $index),
                        'last_reviewed_at' => $now->copy()->subDays(1),
                        'total_correct' => 2,
                        'total_wrong' => 2,
                    ],
                );
            }
        }
    }

    private function seedReviewSessions(User $student, int $days = 10): void
    {
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $startedAt = Carbon::now()->subDays($offset)->setTime(17, 0);

            ReviewSession::query()->updateOrCreate(
                [
                    'user_id' => $student->id,
                    'started_at' => $startedAt,
                ],
                [
                    'finished_at' => $startedAt->copy()->addMinutes(12 + $offset),
                    'correct_count' => 6 + ($offset % 4),
                    'wrong_count' => 1 + ($offset % 3),
                ],
            );
        }

        ReviewSession::query()
            ->where('user_id', $student->id)
            ->whereNull('finished_at')
            ->delete();

        ReviewSession::query()->create([
            'user_id' => $student->id,
            'started_at' => Carbon::now()->subHour(),
            'finished_at' => null,
            'correct_count' => 3,
            'wrong_count' => 1,
        ]);
    }
}
