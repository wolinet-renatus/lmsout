<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\QuestionBankItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Question bank controller — handles instructor studio workflows and cohort delivery.
 *
 * @author Renatus Bernardo
 */
class QuestionBankController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $subject = trim((string) $request->input('subject', ''));
        $search = trim((string) $request->input('search', ''));

        $query = QuestionBank::query()->withCount('items');
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        if ($subject !== '') {
            $query->where('subject', $subject);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $banks = $query->orderByDesc('updated_at')->paginate(12)->withQueryString();
        $subjects = QuestionBank::query()
            ->when($user->role !== 'admin', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('subject')
            ->distinct()
            ->pluck('subject');

        if ($request->wantsJson()) {
            return response()->json(['banks' => $banks->items(), 'subjects' => $subjects]);
        }

        return view('instructor.question-banks.index', [
            'user' => $user,
            'banks' => $banks,
            'subjects' => $subjects,
            'subjectFilter' => $subject,
            'searchTerm' => $search,
        ]);
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $bank = QuestionBank::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'subject' => $data['subject'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'bank' => $bank]);
        }

        return redirect()->route('instructor.question-banks.show', $bank)->with('success', 'Question bank created.');
    }

    /**
     * Display the specified resource details.
     */
    public function show(Request $request, QuestionBank $questionBank): View|JsonResponse
    {
        $this->authorizeBank($questionBank, $request->user());

        $difficulty = $request->input('difficulty', '');
        $subject = trim((string) $request->input('subject', ''));

        $itemsQuery = $questionBank->items();
        if (in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $itemsQuery->where('difficulty', $difficulty);
        }
        if ($subject !== '') {
            $itemsQuery->where('subject', $subject);
        }

        $items = $itemsQuery->orderByDesc('id')->paginate(25)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['bank' => $questionBank, 'items' => $items->items()]);
        }

        return view('instructor.question-banks.show', [
            'user' => $request->user(),
            'bank' => $questionBank,
            'items' => $items,
            'difficultyFilter' => $difficulty,
            'subjectFilter' => $subject,
        ]);
    }

    /**
     * Store item.
     */
    public function storeItem(Request $request, QuestionBank $questionBank): RedirectResponse|JsonResponse
    {
        $this->authorizeBank($questionBank, $request->user());

        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'type' => ['nullable', 'in:multiple_choice,true_false,short_answer'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['required', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'subject' => ['nullable', 'string', 'max:120'],
            'points' => ['nullable', 'integer', 'min:1', 'max:100'],
            'explanation' => ['nullable', 'string'],
        ]);

        $item = $questionBank->items()->create([
            'question' => $data['question_text'],
            'type' => $data['type'] ?? 'multiple_choice',
            'options' => array_values(array_filter($data['options'] ?? [], fn ($v) => trim((string) $v) !== '')),
            'correct_answer' => $data['correct_answer'],
            'difficulty' => $data['difficulty'],
            'subject' => $data['subject'] ?? $questionBank->subject,
            'marks' => $data['points'] ?? 1,
            'explanation' => $data['explanation'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'item' => $item]);
        }

        return back()->with('success', 'Question added to bank.');
    }

    /**
     * Import csv.
     */
    public function importCsv(Request $request, QuestionBank $questionBank): RedirectResponse|JsonResponse
    {
        $this->authorizeBank($questionBank, $request->user());

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');
        $imported = 0;

        if ($handle !== false) {
            $header = fgetcsv($handle);
            $normalizedHeader = $header ? array_map(fn ($h) => strtolower(trim((string) $h)), $header) : [];
            $hasHeader = in_array('question_text', $normalizedHeader, true) || in_array('question', $normalizedHeader, true);

            if (!$hasHeader && $header) {
                $imported += $this->createItemFromCsvRow($questionBank, $header) ? 1 : 0;
            }

            while (($row = fgetcsv($handle)) !== false) {
                $imported += $this->createItemFromCsvRow($questionBank, $row) ? 1 : 0;
            }
            fclose($handle);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'imported' => $imported]);
        }

        return back()->with('success', "Imported {$imported} question(s) from CSV.");
    }

    /**
     * Random paper.
     */
    public function randomPaper(Request $request, QuestionBank $questionBank): JsonResponse
    {
        $this->authorizeBank($questionBank, $request->user());

        $count = max(1, min(100, $request->integer('count', 10)));
        $difficulty = $request->input('difficulty');

        $query = $questionBank->items();
        if (in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $query->where('difficulty', $difficulty);
        }

        $questions = $query->inRandomOrder()->limit($count)->get();

        return response()->json([
            'bank_id' => $questionBank->id,
            'count' => $questions->count(),
            'questions' => $questions,
        ]);
    }

    /**
     * Destroy item.
     */
    public function destroyItem(Request $request, QuestionBank $questionBank, QuestionBankItem $item): RedirectResponse
    {
        $this->authorizeBank($questionBank, $request->user());
        if ($item->question_bank_id === $questionBank->id) {
            $item->delete();
        }
        return back()->with('success', 'Question removed from bank.');
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeBank($questionBank, $request->user());
        $questionBank->delete();
        return redirect()->route('instructor.question-banks.index')->with('success', 'Question bank deleted.');
    }

    /**
     * Create item from csv row.
     */
    private function createItemFromCsvRow(QuestionBank $bank, array $row): bool
    {
        $questionText = trim((string) ($row[0] ?? ''));
        if ($questionText === '') {
            return false;
        }
        $options = array_values(array_filter([
            trim((string) ($row[1] ?? '')),
            trim((string) ($row[2] ?? '')),
            trim((string) ($row[3] ?? '')),
            trim((string) ($row[4] ?? '')),
        ], fn ($v) => $v !== ''));

        $correct = trim((string) ($row[5] ?? ($options[0] ?? '')));
        // Allow letter index (A/B/C/D) or 0-3 or literal text
        $letterMap = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3];
        $lowerCorrect = strtolower($correct);
        if (isset($letterMap[$lowerCorrect]) && isset($options[$letterMap[$lowerCorrect]])) {
            $correct = $options[$letterMap[$lowerCorrect]];
        } elseif (is_numeric($correct) && isset($options[(int) $correct])) {
            $correct = $options[(int) $correct];
        }

        $difficulty = strtolower(trim((string) ($row[6] ?? 'medium')));
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }
        $subject = trim((string) ($row[7] ?? '')) ?: $bank->subject;
        $explanation = trim((string) ($row[8] ?? '')) ?: null;

        $bank->items()->create([
            'question' => $questionText,
            'type' => 'multiple_choice',
            'options' => $options,
            'correct_answer' => $correct,
            'difficulty' => $difficulty,
            'subject' => $subject,
            'marks' => 1,
            'explanation' => $explanation,
        ]);

        return true;
    }

    /**
     * Authorize bank.
     */
    private function authorizeBank(QuestionBank $bank, $user): void
    {
        if ($user->role !== 'admin' && (int) $bank->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized question bank access.');
        }
    }
}
