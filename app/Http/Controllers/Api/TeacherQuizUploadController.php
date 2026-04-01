<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class TeacherQuizUploadController extends Controller
{
	/**
	 * Upload a quiz (metadata + CSV of questions).
	 *
	 * Expected multipart/form-data fields:
	 * - campus (string, required)
	 * - quiz_name (string, required)
	 * - description (string, optional)
	 * - for_class (string, required)
	 * - section (string, required)
	 * - start_date_time (datetime, required)
	 * - duration_minutes (int, required)
	 * - questions_file (file, required; CSV)
	 *
	 * CSV headers (case-insensitive):
	 * question_number,question,answer1,marks1,answer2,marks2,answer3,marks3
	 */
	public function upload(\Illuminate\Http\Request $request): JsonResponse
	{
		$validator = Validator::make($request->all(), [
			'campus' => ['required', 'string', 'max:255'],
			'quiz_name' => ['required', 'string', 'max:255'],
			'description' => ['nullable', 'string'],
			'for_class' => ['required', 'string', 'max:255'],
			'section' => ['required', 'string', 'max:255'],
			'start_date_time' => ['required', 'date'],
			'duration_minutes' => ['required', 'integer', 'min:1'],
			'questions_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $validator->errors(),
			], 422);
		}

		try {
			// Store the uploaded file temporarily
			$path = $request->file('questions_file')->store('tmp/quiz_uploads');
			$absolutePath = Storage::path($path);

			// Parse CSV
			$file = new \SplFileObject($absolutePath);
			$file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
			$file->setCsvControl(",");

			$headers = null;
			$rows = [];
			foreach ($file as $row) {
				if ($row === [null] || $row === false) {
					continue;
				}
				if ($headers === null) {
					$headers = array_map(function ($h) {
						return strtolower(trim((string)$h));
					}, $row);
					continue;
				}
				if (count($row) < count($headers)) {
					$row = array_pad($row, count($headers), '');
				}
				$assoc = [];
				foreach ($headers as $idx => $key) {
					$assoc[$key] = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
				}
				if (($assoc['question'] ?? '') === '') {
					continue;
				}
				$rows[] = $assoc;
			}

			if (empty($rows)) {
				return response()->json([
					'success' => false,
					'message' => 'CSV contains no questions.',
				], 422);
			}

			$quiz = Quiz::create([
				'campus' => (string)$request->input('campus'),
				'quiz_name' => (string)$request->input('quiz_name'),
				'description' => (string)$request->input('description', ''),
				'for_class' => (string)$request->input('for_class'),
				'section' => (string)$request->input('section'),
				'total_questions' => count($rows),
				'start_date_time' => $request->input('start_date_time'),
				'duration_minutes' => (int)$request->input('duration_minutes'),
			]);

			$questionInserts = [];
			foreach ($rows as $row) {
				$questionInserts[] = [
					'quiz_id' => $quiz->id,
					'question_number' => (int)($row['question_number'] ?? 0) ?: null,
					'question' => $row['question'] ?? '',
					'answer1' => $row['answer1'] ?? '',
					'marks1' => (int)($row['marks1'] ?? 0),
					'answer2' => $row['answer2'] ?? '',
					'marks2' => (int)($row['marks2'] ?? 0),
					'answer3' => $row['answer3'] ?? '',
					'marks3' => (int)($row['marks3'] ?? 0),
				];
			}

			usort($questionInserts, function ($a, $b) {
				return ($a['question_number'] ?? PHP_INT_MAX) <=> ($b['question_number'] ?? PHP_INT_MAX);
			});

			$counter = 1;
			foreach ($questionInserts as &$qi) {
				if (empty($qi['question_number']) || $qi['question_number'] <= 0) {
					$qi['question_number'] = $counter;
				}
				$counter++;
			}
			unset($qi);

			QuizQuestion::insert($questionInserts);

			Storage::delete($path);

			return response()->json([
				'success' => true,
				'message' => 'Quiz uploaded successfully.',
				'quiz' => [
					'id' => $quiz->id,
					'quiz_name' => $quiz->quiz_name,
					'for_class' => $quiz->for_class,
					'section' => $quiz->section,
					'total_questions' => $quiz->total_questions,
					'start_date_time' => $quiz->start_date_time,
					'duration_minutes' => $quiz->duration_minutes,
				],
			], 201);
		} catch (\Throwable $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to upload quiz.',
				'error' => config('app.debug') ? $e->getMessage() : null,
			], 500);
		}
	}

	/**
	 * Return a CSV template description for quiz questions.
	 */
	public function template(): JsonResponse
	{
		$headers = [
			'question_number',
			'question',
			'answer1',
			'marks1',
			'answer2',
			'marks2',
			'answer3',
			'marks3',
		];

		return response()->json([
			'success' => true,
			'filename' => 'quiz_questions_template.csv',
			'headers' => $headers,
			'sample_row' => [
				'1',
				'What is 2 + 2?',
				'4',
				'5',
				'3',
				'0',
				'5',
				'0',
			],
		]);
	}
}

